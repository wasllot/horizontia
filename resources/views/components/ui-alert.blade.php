<div {{ $attributes->merge(['class' => 'bg-primary-50 border border-primary-100 rounded-xl p-4 flex items-start gap-3']) }}>
    @if(!isset($dismissible) || $dismissible !== false)
        <button type="button" class="flex-shrink-0 text-primary-400 hover:text-primary-600 transition-colors" data-dismiss="alert">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
    
    <div class="flex-1">
        @if(isset($title))
            <h4 class="text-sm font-semibold text-primary-900 mb-1">
                {{ $title }}
            </h4>
        @endif
        
        <div class="text-sm text-primary-700">
            {{ $slot }}
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-dismiss="alert"]').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('[class*="bg-primary-50"]').remove();
        });
    });
});
</script>
@endpush
@endonce
