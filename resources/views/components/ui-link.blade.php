<a {{ $attributes->merge(['class' => 'group inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200']) }}>
    {{ $slot }}
    <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
</a>
