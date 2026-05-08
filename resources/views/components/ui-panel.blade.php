<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-soft overflow-hidden']) }}>
    <div class="p-6">
        @if(isset($header))
            <div class="mb-4 pb-4 border-b border-gray-100">
                {{ $header }}
            </div>
        @endif
        
        {{ $slot }}
        
        @if(isset($footer))
            <div class="mt-4 pt-4 border-t border-gray-100">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
