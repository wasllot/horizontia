<?php

namespace App\Livewire\Pages\Admin\Events;

use App\Models\Event;
use App\Models\EventOrder;
use App\Services\Events\EventSales;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Admin > Eventos: precio, cupo, descuentos, textos, políticas, archivos y compras de cada evento con página propia. */
class Events extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;
    use WithFileUploads;

    public const ASSETS = [
        'logo' => 'Logotipo del evento (encabezado)', 'hero_desktop' => 'Imagen principal · escritorio (1920×800)', 'hero_mobile' => 'Imagen principal · móvil (1080×1350)',
        'og' => 'Imagen para redes (1200×630)', 'galileo' => 'Galileo (PNG transparente)', 'telescopio' => 'Telescopio (PNG transparente)',
        'luz' => 'Foto de Luz Calderón', 'fama' => 'Logotipo de FAMA', 'club' => 'Logotipo del Club Campestre Teotihuacán',
    ];

    public bool $formOpen = false;
    public ?int $editingId = null;
    public array $f = [];
    public array $d = [];
    public array $t = [];
    public array $hide = [];
    public array $assets = [];
    public array $uploads = [];
    public string $orderStatus = 'paid';

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: $type === 'success' ? __('general.success_title') : 'Atención', message: $message);
    }

    private function localInput(?Carbon $date, string $format = 'Y-m-d\TH:i'): string
    {
        return $date ? $date->copy()->timezone(Event::timezone())->format($format) : '';
    }

    private function fromInput(?string $value): ?Carbon
    {
        return filled($value) ? Carbon::parse($value, Event::timezone())->timezone(config('app.timezone')) : null;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->f = ['name' => '', 'slug' => '', 'status' => Event::DRAFT, 'price' => '', 'iva_rate' => 16, 'max_per_order' => 10, 'hold_minutes' => 45, 'offline_hold_hours' => 72, 'sales_enabled' => false, 'redirect_events_index' => false, 'minors_age' => 6];
        $this->d = $this->emptyDiscounts();
        $this->formOpen = true;
    }

    private function emptyDiscounts(): array
    {
        return ['early_bird' => ['enabled' => false, 'label' => 'Preventa', 'percent' => '', 'starts_at' => '', 'ends_at' => ''], 'group' => ['enabled' => false, 'label' => 'Descuento por grupo', 'percent' => '', 'min_qty' => 4, 'starts_at' => '', 'ends_at' => '']];
    }

    public function edit(int $id): void
    {
        $e = Event::findOrFail($id);
        $this->resetForm();
        $this->editingId = $e->id;
        $this->f = [
            'name' => $e->name, 'slug' => $e->slug, 'status' => $e->status,
            'start_at' => $this->localInput($e->start_at), 'end_at' => $this->localInput($e->end_at), 'countdown_from' => $this->localInput($e->countdown_from),
            'venue_name' => (string) $e->venue_name, 'venue_city' => (string) $e->venue_city, 'venue_address' => (string) $e->venue_address, 'venue_map_url' => (string) $e->venue_map_url,
            'hotel_booking_url' => (string) $e->hotel_booking_url, 'hotel_phone' => (string) $e->hotel_phone, 'hotel_whatsapp' => (string) $e->hotel_whatsapp,
            'contact_email' => (string) $e->contact_email, 'contact_whatsapp' => (string) $e->contact_whatsapp,
            'price' => (string) (float) $e->price, 'iva_rate' => (string) (float) $e->iva_rate, 'capacity' => $e->capacity === null ? '' : (string) $e->capacity, 'max_per_order' => $e->max_per_order,
            'sales_enabled' => (bool) $e->sales_enabled, 'sales_open_at' => $this->localInput($e->sales_open_at), 'sales_close_at' => $this->localInput($e->sales_close_at),
            'hold_minutes' => $e->hold_minutes, 'offline_hold_hours' => $e->offline_hold_hours, 'redirect_events_index' => (bool) $e->redirect_events_index,
            'minors_age' => (int) (data_get($e->content, 'minors_age') ?: 6), 'policies_html' => (string) $e->policies_html,
        ];
        $d = $this->emptyDiscounts();
        foreach (['early_bird', 'group'] as $k) {
            $r = (array) data_get($e->discounts, $k, []);
            $d[$k] = array_merge($d[$k], array_intersect_key($r, $d[$k]));
            $d[$k]['enabled'] = !empty($r['enabled']);
        }
        $this->d = $d;
        foreach (['hero.title', 'hero.subtitle', 'hero.cta', 'seo.title', 'seo.description', 'seo.h1', 'closing.title', 'closing.text', 'rooms.text', 'transport'] as $k) {
            data_set($this->t, $k, (string) data_get($e->content, 'text.' . $k, ''));
        }
        $this->hide = ['rooms' => data_get($e->content, 'hide.rooms') === true, 'map' => data_get($e->content, 'hide.map') === true, 'partners' => data_get($e->content, 'hide.partners') === true];
        $this->assets = (array) $e->assets;
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset('formOpen', 'editingId', 'f', 'd', 't', 'hide', 'assets', 'uploads');
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->f['slug'] = Str::slug((string) ($this->f['slug'] ?: ($this->f['name'] ?? '')));
        $this->validate([
            'f.name' => 'required|string|max:160',
            'f.slug' => ['required', 'string', 'max:120', \Illuminate\Validation\Rule::unique('events', 'slug')->ignore($this->editingId)],
            'f.status' => 'required|in:draft,published,finished',
            'f.start_at' => 'nullable|date', 'f.end_at' => 'nullable|date|after_or_equal:f.start_at', 'f.countdown_from' => 'nullable|date',
            'f.venue_map_url' => 'nullable|url|max:500', 'f.hotel_booking_url' => 'nullable|url|max:500',
            'f.contact_email' => 'nullable|email|max:160',
            'f.price' => 'required|numeric|min:0|max:1000000', 'f.iva_rate' => 'required|numeric|min:0|max:100',
            'f.capacity' => 'nullable|integer|min:1|max:100000', 'f.max_per_order' => 'required|integer|min:1|max:50',
            'f.sales_open_at' => 'nullable|date', 'f.sales_close_at' => 'nullable|date',
            'f.hold_minutes' => 'required|integer|min:5|max:1440', 'f.offline_hold_hours' => 'required|integer|min:1|max:240',
            'f.minors_age' => 'required|integer|min:1|max:18',
            'd.*.percent' => 'nullable|numeric|min:0|max:100', 'd.group.min_qty' => 'nullable|integer|min:2|max:50',
            'uploads.*' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:4096',
        ], [
            'f.name.required' => 'Escribe el nombre del evento.', 'f.slug.unique' => 'Ya existe un evento con esa dirección.', 'f.price.required' => 'Indica el precio (con IVA incluido).',
            'f.end_at.after_or_equal' => 'El fin no puede ser anterior al inicio.', 'f.venue_map_url.url' => 'Pega un enlace completo (https://…).', 'f.hotel_booking_url.url' => 'Pega un enlace completo (https://…).',
            'uploads.*.mimes' => 'Sube una imagen PNG, JPG, WebP o SVG.', 'uploads.*.max' => 'Cada archivo puede pesar hasta 4 MB.',
        ]);

        $assets = $this->storeUploads();
        $text = array_filter(array_map(fn ($v) => trim(strip_tags((string) $v)), \Illuminate\Support\Arr::dot($this->t)), fn ($v) => $v !== '');
        $nested = [];
        foreach ($text as $k => $v) {
            data_set($nested, $k, $v);
        }
        $existing = $this->editingId ? Event::findOrFail($this->editingId)->content : [];
        $content = array_merge((array) $existing, ['minors_age' => (int) $this->f['minors_age'], 'text' => $nested, 'hide' => array_filter($this->hide)]);

        $discounts = [];
        foreach ($this->d as $k => $r) {
            $discounts[$k] = ['enabled' => (bool) ($r['enabled'] ?? false), 'label' => trim((string) ($r['label'] ?? '')) ?: ($k === 'group' ? 'Descuento por grupo' : 'Preventa'), 'percent' => (float) ($r['percent'] ?: 0),
                'min_qty' => (int) ($r['min_qty'] ?? 4), 'starts_at' => $r['starts_at'] ?: null, 'ends_at' => $r['ends_at'] ?: null];
        }

        $data = [
            'name' => trim($this->f['name']), 'slug' => $this->f['slug'], 'status' => $this->f['status'],
            'start_at' => $this->fromInput($this->f['start_at'] ?? null), 'end_at' => $this->fromInput($this->f['end_at'] ?? null), 'countdown_from' => $this->fromInput($this->f['countdown_from'] ?? null),
            'venue_name' => $this->clean('venue_name'), 'venue_city' => $this->clean('venue_city'), 'venue_address' => $this->clean('venue_address'), 'venue_map_url' => $this->clean('venue_map_url'),
            'hotel_booking_url' => $this->clean('hotel_booking_url'), 'hotel_phone' => $this->clean('hotel_phone'), 'hotel_whatsapp' => $this->clean('hotel_whatsapp'),
            'contact_email' => $this->clean('contact_email'), 'contact_whatsapp' => $this->clean('contact_whatsapp'),
            'price' => round((float) $this->f['price'], 2), 'iva_rate' => round((float) $this->f['iva_rate'], 2),
            'capacity' => filled($this->f['capacity'] ?? null) ? (int) $this->f['capacity'] : null, 'max_per_order' => (int) $this->f['max_per_order'],
            'sales_enabled' => (bool) ($this->f['sales_enabled'] ?? false), 'sales_open_at' => $this->fromInput($this->f['sales_open_at'] ?? null), 'sales_close_at' => $this->fromInput($this->f['sales_close_at'] ?? null),
            'hold_minutes' => (int) $this->f['hold_minutes'], 'offline_hold_hours' => (int) $this->f['offline_hold_hours'],
            'redirect_events_index' => (bool) ($this->f['redirect_events_index'] ?? false),
            'discounts' => $discounts, 'content' => $content, 'assets' => $assets ?: null,
            'policies_html' => trim((string) ($this->f['policies_html'] ?? '')) ?: null,
        ];

        $event = $this->editingId ? tap(Event::findOrFail($this->editingId))->update($data) : Event::create($data);
        $this->toast('Evento guardado.');
        $this->edit($event->id);
    }

    private function clean(string $key): ?string
    {
        $v = trim(strip_tags((string) ($this->f[$key] ?? '')));

        return $v === '' ? null : $v;
    }

    /** Mueve las imágenes nuevas a public/uploads/events (sirven igual con o sin enlace simbólico de storage). */
    private function storeUploads(): array
    {
        $assets = $this->assets;
        foreach ($this->uploads as $key => $file) {
            if (!$file || !array_key_exists($key, self::ASSETS)) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            if ($ext === 'svg' && preg_match('/<script|onload\s*=|onerror\s*=|javascript:/i', (string) file_get_contents($file->getRealPath()))) {
                $this->addError('uploads.' . $key, 'Ese SVG contiene código no permitido.');
                continue;
            }
            $dir = public_path('uploads/events');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = ($this->f['slug'] ?: 'evento') . '-' . $key . '-' . Str::random(6) . '.' . $ext;
            copy($file->getRealPath(), $dir . '/' . $name);
            $assets[$key] = 'uploads/events/' . $name;
        }
        $this->uploads = [];

        return $assets;
    }

    public function removeAsset(string $key): void
    {
        unset($this->assets[$key]);
        if ($this->editingId) {
            Event::whereKey($this->editingId)->update(['assets' => $this->assets ?: null]);
            $this->toast('Archivo quitado.');
        }
    }

    public function resend(int $orderId): void
    {
        $order = EventOrder::where('event_id', $this->editingId)->findOrFail($orderId);
        if (!$order->isPaid()) {
            $this->toast('Solo se reenvía la confirmación de compras pagadas.', 'error');

            return;
        }
        $order->update(['confirmation_sent_at' => null]);
        app(EventSales::class)->sendConfirmation($order->fresh());
        $this->toast('Confirmación reenviada a ' . $order->email . '.');
    }

    public function delete(int $id): void
    {
        $e = Event::findOrFail($id);
        if ($e->orders()->exists()) {
            $this->toast('Este evento ya tiene compras: no se puede eliminar. Cámbialo a «Borrador» para ocultarlo.', 'error');

            return;
        }
        $e->delete();
        $this->toast('Evento eliminado.');
    }

    /** Qué falta para poder vender (se muestra como lista de verificación). */
    private function readiness(?Event $e): array
    {
        $gw = getGatewayObject('mercadopago');

        return [
            ['Precio definido', (float) ($this->f['price'] ?? 0) > 0],
            ['Políticas del evento redactadas', trim(strip_tags((string) ($this->f['policies_html'] ?? ''))) !== ''],
            ['Mercado Pago configurado (Ajustes → Pagos)', !empty($gw) && !empty($gw->getKeys()['mp_access_token'] ?? null)],
            ['Evento publicado', ($this->f['status'] ?? '') === Event::PUBLISHED],
            ['Ventas activadas', !empty($this->f['sales_enabled'])],
        ];
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $current = $this->editingId ? Event::find($this->editingId) : null;
        $sales = app(EventSales::class);

        return view('livewire.pages.admin.events.events', [
            'events' => Event::orderByDesc('start_at')->get(),
            'sales' => $sales,
            'current' => $current,
            'ready' => $this->formOpen ? $this->readiness($current) : [],
            'orders' => $current ? EventOrder::where('event_id', $current->id)->when($this->orderStatus !== 'all', fn ($q) => $q->where('status', $this->orderStatus))->with('tickets')->latest('id')->limit(100)->get() : collect(),
            'stats' => $current ? [
                'paid' => (int) EventOrder::where('event_id', $current->id)->where('status', EventOrder::PAID)->sum('quantity'),
                'revenue' => (float) EventOrder::where('event_id', $current->id)->where('status', EventOrder::PAID)->sum('total'),
                'pending' => (int) EventOrder::where('event_id', $current->id)->where('status', EventOrder::PENDING)->where('expires_at', '>', now())->sum('quantity'),
            ] : null,
            'assetLabels' => self::ASSETS,
        ]);
    }
}
