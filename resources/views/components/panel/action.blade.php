@props(['icon', 'label', 'tone' => null])
<button type="button" {{ $attributes->class(['hz-act', 'hz-act--' . $tone => $tone]) }} title="{{ $label }}" aria-label="{{ $label }}"><i class="{{ $icon }}" aria-hidden="true"></i></button>
