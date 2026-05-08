<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center justify-center gap-2 px-6 py-3 font-semibold rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed'
]) }}>
    @if(isset($icon) && !$attributes->get('disabled'))
        {{ $icon }}
    @endif
    {{ $slot }}
</button>
