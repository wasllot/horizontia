@props(['tone' => 'neutral', 'dot' => true])
<span {{ $attributes->class(['hz-badge', 'hz-badge--' . $tone, 'hz-badge--dot' => $dot]) }}>{{ $slot }}</span>
