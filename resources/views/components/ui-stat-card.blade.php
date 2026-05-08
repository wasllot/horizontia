<div {{ $attributes->merge(['class' => 'bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-6 text-white']) }}>
    <div class="flex items-center gap-3 mb-4">
        @if(isset($icon))
            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                {{ $icon }}
            </div>
        @endif
        
        @if(isset($title))
            <h3 class="text-lg font-semibold">
                {{ $title }}
            </h3>
        @endif
    </div>
    
    @if(isset($description))
        <p class="text-gray-300 text-sm mb-4">
            {{ $description }}
        </p>
    @endif
    
    @if($slot->isNotEmpty())
        {{ $slot }}
    @endif
</div>
