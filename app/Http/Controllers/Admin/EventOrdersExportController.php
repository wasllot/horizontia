<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventOrder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV de compras de un evento (por fecha de compra). Las celdas que empiezan con = + - @ se neutralizan contra inyección de fórmulas. */
class EventOrdersExportController extends Controller
{
    public function __invoke(Request $request, Event $event): StreamedResponse
    {
        $status = (string) $request->query('estado', 'paid');
        $from = $request->query('desde');
        $to = $request->query('hasta');

        $orders = EventOrder::where('event_id', $event->id)->with('tickets')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($from && strtotime((string) $from), fn ($q) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($from, Event::timezone())->startOfDay()->timezone(config('app.timezone'))))
            ->when($to && strtotime((string) $to), fn ($q) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($to, Event::timezone())->endOfDay()->timezone(config('app.timezone'))))
            ->orderBy('created_at');

        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
        $tz = Event::timezone();

        return response()->streamDownload(function () use ($orders, $safe, $tz) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Compra', 'Fecha (hora de México)', 'Estado', 'Nombre', 'Correo', 'Teléfono', 'Entradas', 'Asistentes (clave)', 'Menores', 'Telescopio', 'Cómo se enteró', 'Precio unitario', 'Descuento', 'Subtotal sin IVA', 'IVA', 'Total', 'Método de pago', 'ID de pago', 'Factura', 'RFC', 'Razón social', 'CP fiscal', 'Origen (utm_source)', 'Medio (utm_medium)', 'Campaña (utm_campaign)']);
            $orders->chunk(200, function ($chunk) use ($out, $safe, $tz) {
                foreach ($chunk as $o) {
                    fputcsv($out, array_map($safe, [
                        $o->id, $o->created_at->timezone($tz)->format('Y-m-d H:i'), $o->status, $o->name, $o->email, $o->phone, $o->quantity,
                        $o->tickets->map(fn ($t) => $t->attendee_name . ' (' . $t->code . ')')->implode('; '),
                        $o->minors, $o->has_telescope === null ? '' : ($o->has_telescope ? 'Sí' : 'No'), $o->heard_from,
                        $o->unit_price, $o->discount, $o->subtotalBeforeIva(), $o->iva, $o->total, $o->payment_method, $o->payment_id,
                        $o->wants_invoice ? 'Sí' : 'No', data_get($o->invoice, 'rfc'), data_get($o->invoice, 'razon_social'), data_get($o->invoice, 'cp'),
                        data_get($o->utm, 'utm_source'), data_get($o->utm, 'utm_medium'), data_get($o->utm, 'utm_campaign'),
                    ]));
                }
            });
            fclose($out);
        }, 'compras-' . $event->slug . '-' . now(Event::timezone())->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
