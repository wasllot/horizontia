<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center gap-4 p-4 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:shadow-soft transition-all duration-200']) }}>
    @if(isset($avatar))
        <div class="flex-shrink-0">
            {{ $avatar }}
        </div>
    @endif
    
    <div class="flex-1 min-w-0">
        @if(isset($title))
            <h4 class="text-base font-semibold text-gray-900 truncate">
                {{ $title }}
            </h4>
        @endif
        
        @if(isset($subtitle))
            <p class="text-sm text-gray-500 truncate">
                {{ $subtitle }}
            </p>
        @endif
    </div>
    
    @if(isset($action))
        <div class="flex-shrink-0">
            {{ $action }}
        </div>
    @endif
    
    @if($slot->isNotEmpty())
        {{ $slot }}
    @endif
</div>
