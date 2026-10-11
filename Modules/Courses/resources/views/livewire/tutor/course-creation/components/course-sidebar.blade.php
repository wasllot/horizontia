@php
    $total = count($tabs);
    $current = $currentTabNumber;
    $progress = $total > 1 ? (int) round($current / ($total - 1) * 100) : 0;
    $keys = array_keys($tabs);
@endphp
<nav class="cr-stepper" aria-label="{{ __('Pasos de creación del curso') }}">
    <div class="cr-stepper__top">
        <span class="cr-stepper__count">{{ __('Paso :n de :t', ['n' => $current + 1, 't' => $total]) }}</span>
        <strong class="cr-stepper__title">{{ $tabs[$activeTab]['title'] ?? '' }}</strong>
        <div class="cr-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}"><span style="width: {{ $progress }}%"></span></div>
    </div>
    <ol class="cr-steps">
        @foreach ($tabs as $tab => $value)
            @php
                $index = array_search($tab, $keys);
                $state = $tab === $activeTab ? 'current' : ($index < $current ? 'done' : 'todo');
                $clickable = $id && $index <= $current;
            @endphp
            <li class="cr-step cr-step--{{ $state }}">
                @if ($clickable && $tab !== $activeTab)
                    <button type="button" class="cr-step__btn" wire:click="navigateToRoute('{{ $tab }}')" title="{{ __('courses::courses.edit_again') }}">
                @else
                    <span class="cr-step__btn" @if ($tab === $activeTab) aria-current="step" @endif>
                @endif
                    <span class="cr-step__dot">
                        @if ($state === 'done')<x-courses::icon name="check" :size="15" />@else{{ $value['step'] }}@endif
                    </span>
                    <span class="cr-step__label">{{ $value['title'] }}@if (!empty($value['required']))<i class="cr-step__req" title="{{ __('Obligatorio') }}">*</i>@endif</span>
                @if ($clickable && $tab !== $activeTab)
                    </button>
                @else
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
