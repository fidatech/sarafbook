@props(['name', 'set' => 'solid'])
<i {{ $attributes->class('fi') }}><svg aria-hidden="true" focusable="false"><use href="{{ asset_v('icons.svg') }}#{{ $set === 'regular' ? 'r' : 's' }}-{{ $name }}"/></svg></i>
