<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventOrder;
use App\Services\Events\EventPaymentSettler;
use App\Services\Events\EventSales;
use App\Services\Events\EventSalesException;
use App\Support\QrCode;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Página aislada de un evento (landing, carrito, pago de invitado, gracias, políticas). Sin menú ni enlaces al resto del sitio.
 */
class EventLandingController extends Controller
{
    public function __construct(private EventSales $sales)
    {
    }

    /** El evento publicado de la URL; cualquier otra cosa responde con el 404 propio de la sección. */
    private function resolve(string $slug): Event
    {
        $event = Event::where('slug', $slug)->first();
        $isAdmin = auth()->check() && auth()->user()->hasRole('admin');
        if (!$event || (!$event->isPublic() && !$isAdmin)) {
            $this->notFound($event);
        }

        return $event;
    }

    private function notFound(?Event $event = null): never
    {
        throw new HttpResponseException(response()->view('events.404', ['event' => $event && $event->isPublic() ? $event : Event::where('status', '!=', Event::DRAFT)->orderBy('start_at')->first()], 404));
    }

    public function missing(string $slug)
    {
        $this->notFound(Event::where('slug', $slug)->first());
    }

    public function show(Request $request, string $slug)
    {
        $event = $this->resolve($slug);
        $this->rememberUtm($request, $event);

        return view('events.show', $this->shared($event) + ['noindex' => !$event->indexable(), 'quote' => $this->sales->quote($event, 1)]);
    }

    public function policies(string $slug)
    {
        $event = $this->resolve($slug);

        return view('events.policies', $this->shared($event) + ['noindex' => !$event->policiesReady() || !$event->indexable()]);
    }

    // ---- Carrito ----

    public function cart(string $slug)
    {
        $event = $this->resolve($slug);
        $items = $this->cartItems($event);
        $quantity = $this->sales->persons($event, $items);

        return view('events.cart', $this->shared($event) + ['noindex' => true, 'items' => $items, 'quantity' => $quantity, 'quote' => $items ? $this->sales->quote($event, $items) : null]);
    }

    public function cartAdd(Request $request, string $slug)
    {
        $event = $this->resolve($slug);
        if (!$this->sales->isOpen($event)) {
            return redirect($event->url())->with('event_error', $this->closedMessage($this->sales->state($event)));
        }
        $max = $this->sales->maxQuantity($event);
        if (is_array($request->input('items'))) {
            $items = $this->sales->selection($event, array_map('intval', $request->input('items')));
            if (!$items) {
                return redirect($event->url() . '#entrada')->with('event_error', 'Elige al menos un paquete.');
            }
            if ($this->sales->persons($event, $items) > $max) {
                return redirect($event->url() . '#entrada')->with('event_error', $this->tooManyMessage($max));
            }
        } else {
            $items = $this->sales->selection($event, min($max, max(1, (int) $request->input('quantity', 1))));
        }
        session(['event_cart.' . $event->id => $items]);

        return redirect($event->url('carrito'))->with('event_pixel', 'add_to_cart');
    }

    /** Un cambio a la vez: quitar todo, fijar la cantidad de un paquete («p2|3»; 0 lo quita) o agregar uno más («add»). */
    public function cartUpdate(Request $request, string $slug)
    {
        $event = $this->resolve($slug);
        if ($request->input('action') === 'remove') {
            session()->forget('event_cart.' . $event->id);

            return redirect($event->url('carrito'));
        }
        $items = $this->cartItems($event);
        if ($request->filled('line')) {
            [$key, $qty] = array_pad(explode('|', (string) $request->input('line'), 2), 2, 0);
            $items = $this->sales->selection($event, array_merge($items, [$key => max(0, (int) $qty)]));
        } elseif ($request->filled('add')) {
            $key = (string) $request->input('add');
            $items = $this->sales->selection($event, array_merge($items, [$key => ($items[$key] ?? 0) + 1]));
        } elseif ($request->has('quantity')) {   // formularios antiguos: una sola entrada
            $items = $this->sales->selection($event, min(max(1, $this->sales->maxQuantity($event)), max(1, (int) $request->input('quantity', 1))));
        }
        if ($this->sales->persons($event, $items) > $this->sales->maxQuantity($event)) {
            return redirect($event->url('carrito'))->with('event_error', $this->tooManyMessage($this->sales->maxQuantity($event)));
        }
        $items ? session(['event_cart.' . $event->id => $items]) : session()->forget('event_cart.' . $event->id);

        return redirect($event->url('carrito'));
    }

    // ---- Datos y pago ----

    public function checkout(string $slug)
    {
        $event = $this->resolve($slug);
        $items = $this->cartItems($event);
        if (!$items) {
            return redirect($event->url('carrito'));
        }
        if (!$this->sales->isOpen($event)) {
            return redirect($event->url())->with('event_error', $this->closedMessage($this->sales->state($event)));
        }
        $quote = $this->sales->quote($event, $items);

        return view('events.checkout', $this->shared($event) + ['noindex' => true, 'items' => $items, 'quantity' => $quote['persons'], 'quote' => $quote]);
    }

    public function placeOrder(Request $request, string $slug)
    {
        $event = $this->resolve($slug);
        $items = $this->cartItems($event);
        $quantity = $this->sales->persons($event, $items);
        if (!$quantity) {
            return redirect($event->url('carrito'));
        }
        if ($request->filled('website')) {   // señuelo anti-spam: una persona nunca lo llena
            return redirect($event->url());
        }

        $data = $request->validate([
            'name' => 'required|string|max:160',
            'email' => 'required|email:rfc|max:190',
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+()\-\s]{8,}$/'],
            'attendees' => 'required|array|size:' . $quantity,
            'attendees.*' => 'required|string|max:160',
            'minors' => 'nullable|integer|min:0|max:20',
            'has_telescope' => 'nullable|in:si,no',
            'heard_from' => 'nullable|string|max:120',
            'accept_policies' => 'accepted',
            'newsletter' => 'nullable|boolean',
            'wants_invoice' => 'nullable|boolean',
            'invoice.rfc' => 'required_if:wants_invoice,1|nullable|string|max:13',
            'invoice.razon_social' => 'required_if:wants_invoice,1|nullable|string|max:190',
            'invoice.cp' => 'required_if:wants_invoice,1|nullable|string|max:10',
            'invoice.regimen' => 'nullable|string|max:120',
            'invoice.uso_cfdi' => 'nullable|string|max:120',
            'invoice.correo' => 'nullable|email|max:190',
        ], [
            'name.required' => 'Escribe tu nombre completo.', 'email.required' => 'Escribe tu correo.', 'email.email' => 'Revisa que el correo esté bien escrito.',
            'phone.required' => 'Escribe un teléfono o WhatsApp.', 'phone.regex' => 'Escribe un teléfono válido, con lada.',
            'attendees.*.required' => 'Escribe el nombre de cada asistente.', 'attendees.size' => 'Escribe el nombre de las ' . $quantity . ' personas de tu compra.', 'accept_policies.accepted' => 'Para continuar acepta las políticas del evento.',
            'invoice.rfc.required_if' => 'Indica el RFC para la factura.', 'invoice.razon_social.required_if' => 'Indica el nombre o razón social.', 'invoice.cp.required_if' => 'Indica el código postal fiscal.',
        ]);

        $driver = getGatewayObject('mercadopago');
        if (empty($driver) || empty($driver->getKeys()['mp_access_token'] ?? null)) {
            Log::error('Evento: Mercado Pago sin configurar al intentar vender entradas', ['event' => $event->id]);

            return back()->withInput()->with('event_error', 'El pago en línea no está disponible en este momento. Escríbenos para apartar tu lugar.');
        }

        try {
            $order = $this->sales->createOrder($event, [
                'name' => trim($data['name']), 'email' => $data['email'], 'phone' => trim($data['phone']),
                'items' => $items, 'quantity' => $quantity, 'attendees' => array_map('trim', $data['attendees']),
                'minors' => $data['minors'] ?? 0,
                'has_telescope' => isset($data['has_telescope']) ? $data['has_telescope'] === 'si' : null,
                'heard_from' => $data['heard_from'] ?? null,
                'newsletter' => (bool) ($data['newsletter'] ?? false),
                'wants_invoice' => (bool) ($data['wants_invoice'] ?? false),
                'invoice' => $data['invoice'] ?? null,
                'utm' => session('event_utm.' . $event->id),
            ]);
        } catch (EventSalesException $e) {
            return redirect($event->url('carrito'))->with('event_error', $this->closedMessage($e->getMessage()));
        }

        try {
            $result = $driver->createReferencedCheckout(
                $order->reference(),
                mb_substr($event->name . ' · ' . $order->packagesLabel(), 0, 250),
                (float) $order->total,
                ['email' => $order->email, 'name' => $order->name],
                $event->url('gracias') . '?order=' . $order->code,
                secure_url(route('mercadopago.webhook', [], false)),
                max(10, (int) $event->hold_minutes),
            );
        } catch (\Throwable $e) {
            Log::error('Evento: no se pudo crear el pago', ['order' => $order->id, 'error' => $e->getMessage()]);
            $result = ['url' => null];
        }

        if (empty($result['url'])) {
            $order->update(['status' => EventOrder::EXPIRED]);   // libera el lugar de inmediato

            return back()->withInput()->with('event_error', 'No pudimos iniciar el pago. Intenta de nuevo en unos minutos.');
        }

        session(['event_order.' . $event->id => $order->code]);

        return redirect()->away($result['url']);
    }

    // ---- Confirmación ----

    public function thanks(Request $request, string $slug)
    {
        $event = $this->resolve($slug);
        $order = EventOrder::with('tickets')->where('event_id', $event->id)->where('code', (string) $request->query('order'))->first();
        if (!$order) {
            return redirect($event->url());
        }

        // Nunca se confía en la URL: el estado se lee de Mercado Pago.
        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');
        if ($order->status !== EventOrder::PAID && is_scalar($paymentId) && $paymentId !== '' && ($driver = getGatewayObject('mercadopago'))) {
            try {
                $payment = $driver->getPayment($paymentId);
                if (!empty($payment) && (string) ($payment['external_reference'] ?? '') === $order->reference()) {
                    $outcome = app(EventPaymentSettler::class)->settle($driver, $payment);
                    // Volvió con el pago rechazado: la persona salió de Mercado Pago, así que el lugar se libera y se le ofrece reintentar.
                    if ($outcome === 'failed' && $order->fresh()->status === EventOrder::PENDING) {
                        $order->update(['status' => EventOrder::EXPIRED]);
                    }
                    $order = $order->fresh('tickets');
                }
            } catch (\Throwable $e) {
                Log::error('Evento: no se pudo leer el pago al volver', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        if ($order->isPaid()) {
            session()->forget('event_cart.' . $event->id);
        }

        return view('events.thanks', $this->shared($event) + ['noindex' => true, 'order' => $order, 'pixel' => $order->isPaid() ? 'purchase' : null]);
    }

    /** QR del boleto (PNG). El código del boleto no es adivinable y solo pertenece a una compra pagada. */
    public function qr(string $slug, string $code)
    {
        $event = $this->resolve($slug);
        $ticket = $event->tickets()->where('code', strtoupper($code))->whereHas('order', fn ($q) => $q->where('status', EventOrder::PAID))->first();
        abort_unless($ticket, 404);

        return response(QrCode::png($ticket->code, 8), 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, max-age=3600', 'X-Robots-Tag' => 'noindex']);
    }

    /** Archivo de calendario (.ics) con las fechas del evento. */
    public function ics(string $slug)
    {
        $event = $this->resolve($slug);
        abort_unless($event->start_at, 404);
        $esc = fn (string $t) => str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], $t);
        $fold = function (string $line) {
            $out = '';
            while (strlen($line) > 73) {
                $out .= substr($line, 0, 73) . "\r\n ";
                $line = substr($line, 73);
            }

            return $out . $line;
        };
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Horizontia//Eventos//ES', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT', 'UID:' . $event->slug . '@' . parse_url(url('/'), PHP_URL_HOST), 'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $fmt($event->start_at), 'DTEND:' . $fmt($event->end_at ?: $event->start_at->copy()->addHours(2)),
            'SUMMARY:' . $esc($event->name),
            'LOCATION:' . $esc(trim($event->venue_name . ', ' . ($event->venue_address ?: $event->venue_city), ', ')),
            'DESCRIPTION:' . $esc('Lleva tu clave de acceso o código QR. Más información: ' . $event->url()),
            'URL:' . $event->url(), 'END:VEVENT', 'END:VCALENDAR',
        ];

        return response(implode("\r\n", array_map($fold, $lines)) . "\r\n", 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => 'attachment; filename="' . $event->slug . '.ics"']);
    }

    // ---- Apoyo ----

    private function shared(Event $event): array
    {
        $state = $this->sales->state($event);

        return [
            'event' => $event, 'state' => $state, 'sales' => $this->sales,
            'available' => $this->sales->available($event), 'maxQuantity' => $this->sales->maxQuantity($event),
            'sold' => (int) EventOrder::where('event_id', $event->id)->where('status', EventOrder::PAID)->sum('quantity'),   // prueba social real, nunca inventada
            'cartCount' => $this->sales->persons($event, $this->cartItems($event)),   // personas en el carrito
            'packages' => $event->packageList(),
        ];
    }

    /** Lo que hay en el carrito: [clave de paquete => cantidad], solo con paquetes vigentes (la sesión de antes de los paquetes guardaba un entero). */
    private function cartItems(Event $event): array
    {
        $raw = session('event_cart.' . $event->id);

        return $this->sales->selection($event, is_array($raw) ? $raw : ((int) $raw > 0 ? (int) $raw : []));
    }

    private function tooManyMessage(int $max): string
    {
        return 'Cada compra admite hasta ' . $max . ' ' . ($max === 1 ? 'persona' : 'personas') . '. Si son más, haz otra compra.';
    }

    private function rememberUtm(Request $request, Event $event): void
    {
        $utm = array_filter($request->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']), fn ($v) => is_string($v) && $v !== '');
        if ($utm) {
            session(['event_utm.' . $event->id => array_map(fn ($v) => mb_substr($v, 0, 120), $utm)]);
        }
    }

    private function closedMessage(string $state): string
    {
        return match ($state) {
            EventSales::SOLD_OUT => 'Se agotaron las entradas.',
            EventSales::ENDED => 'Las ventas ya cerraron.',
            EventSales::NOT_YET => 'Las ventas todavía no abren.',
            'quantity' => 'Esa cantidad ya no está disponible. Elige otra.',
            default => 'Las entradas todavía no están a la venta.',
        };
    }
}
