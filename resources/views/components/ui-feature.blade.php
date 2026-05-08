<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl p-6 shadow-soft']) }}>
    <div class="flex items-start gap-4">
        @if(isset($icon))
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-primary-100 flex items-center justify-center text-primary-600">
                {{ $icon }}
            </div>
        @endif
        
        <div class="flex-1 min-w-0">
            @if(isset($title))
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                    {{ $title }}
                </h3>
            @endif
            
            @if(isset($description))
                <p class="text-gray-600 text-sm">
                    {{ $description }}
                </p>
            @endif
            
            @if($slot->isNotEmpty())
                {{ $slot }}
            @endif
        </div>
    </div>
</div>
