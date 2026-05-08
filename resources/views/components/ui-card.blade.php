<div {{ $attributes->merge(['class' => 'card-border bg-white rounded-2xl overflow-hidden shadow-soft hover:shadow-large transition-all duration-300']) }}>
    @if(isset($image))
        <figure class="m-0 overflow-hidden">
            <img src="{{ $image }}" alt="{{ $title ?? 'Image' }}" class="w-full h-48 object-cover transition-transform duration-500 hover:scale-105">
        </figure>
    @endif
    
    <div class="p-5 {{ isset($image) ? '' : 'pt-5' }}">
        @if(isset($badge))
            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-700 mb-3">
                {{ $badge }}
            </span>
        @endif
        
        @if(isset($title))
            <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2">
                {{ $title }}
            </h3>
        @endif
        
        @if(isset($description))
            <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                {{ $description }}
            </p>
        @endif
        
        @if(isset($meta))
            <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                {{ $meta }}
            </div>
        @endif
        
        @if(isset($footer))
            <div class="pt-4 border-t border-gray-100">
                {{ $footer }}
            </div>
        @endif
        
        @if($slot->isNotEmpty())
            {{ $slot }}
        @endif
    </div>
</div>
