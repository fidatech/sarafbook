{{-- One figure of a page: an icon, a number and a short note (the "kpi" tile of the template). --}}
@props(['label', 'value', 'icon' => 'chart-column', 'tone' => 'blue', 'note' => null, 'href' => null])
@if ($href)
<a class="kpi kpi-{{ $tone }}" href="{{ $href }}">
@else
<div class="kpi kpi-{{ $tone }}">
@endif
    <span class="kpi-icon"><x-icon :name="$icon" /></span>
    <span class="min-w-0">
        <span class="kpi-value d-block">{{ $value }}</span>
        <span class="kpi-label d-block">{{ $label }}</span>
        @if ($note)<span class="kpi-note d-block">{{ $note }}</span>@endif
    </span>
@if ($href)
</a>
@else
</div>
@endif
