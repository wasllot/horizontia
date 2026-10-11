<div class="cr-course cr-create-course">
    <div class="container cr-wizard">
        <livewire:courses::course-sidebar :tab="$tab" :id="$id" :tabs="$tabs" />
        <livewire:dynamic-component :component="'courses::course-'.$tab" :tab="$tab" :id="$id" />
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/course-wizard.css') }}?v={{ @filemtime(public_path('css/course-wizard.css')) }}">
@endpush
