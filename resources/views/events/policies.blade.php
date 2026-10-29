@extends('events.layout', ['metaTitle' => 'Políticas del evento · ' . $event->name, 'canonical' => $event->url('politicas')])

@section('body')
<div class="ev-page"><div class="ev-narrow">
    <h1>Políticas del evento</h1>
    @if ($event->policiesReady())
        <div class="ev-policies">{!! strip_tags((string) $event->policies_html, '<p><br><ul><ol><li><strong><b><em><i><h2><h3><h4><a><blockquote>') !!}</div>
    @else
        <p class="ev-soon">Estamos terminando de redactar las políticas del evento (clima, cancelaciones y reglamento). Se publicarán antes de abrir la venta de entradas.</p>
    @endif
    <p style="margin-top:24px"><a class="ev-btn ev-btn--ghost" href="{{ $event->url() }}">Volver al evento</a></p>
</div></div>
<style>.ev-policies h2,.ev-policies h3{margin:24px 0 8px}.ev-policies p,.ev-policies li{max-width:70ch}.ev-policies ul,.ev-policies ol{padding-left:22px}</style>
@endsection
