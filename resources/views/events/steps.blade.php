@php $steps = ['Carrito', 'Tus datos', 'Pago', 'Listo']; @endphp
<ol class="ev-steps" aria-label="Pasos de la compra">
    @foreach ($steps as $i => $label)
        <li class="{{ $i + 1 < $step ? 'is-done' : ($i + 1 === $step ? 'is-now' : '') }}" @if ($i + 1 === $step) aria-current="step" @endif><span>{{ $label }}</span></li>
    @endforeach
</ol>
