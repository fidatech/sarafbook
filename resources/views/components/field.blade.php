{{--
    One field of a form: label, input, hint and its error. Inside <x-form-modal id="…"> pass
    :modal="'id'" so an old value is used only when that form came back with an error.
    type: text (default), number, email, tel, password, date, textarea, select, switch.
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'hint' => null, 'required' => false, 'modal' => null, 'placeholder' => null, 'step' => null, 'dir' => null, 'col' => null, 'empty' => null])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $useOld = $modal === null || old('_modal') === $modal;
    $current = $useOld ? old($key, $value) : $value;
    $fid = ($modal ? $modal.'_' : 'f_').preg_replace('/[^a-z0-9_]/i', '_', $name);
    $bad = $useOld && $errors->has($key);
@endphp
<div @class([$col ?? 'mb-3'])>
    @if ($type === 'switch')
        <div class="form-check form-switch">
            <input type="hidden" name="{{ $name }}" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="{{ $fid }}" name="{{ $name }}" value="1" @checked((bool) $current) {{ $attributes }}>
            <label class="form-check-label" for="{{ $fid }}">{{ $label }}</label>
        </div>
    @else
        <label class="form-label" for="{{ $fid }}">{{ $label }} @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif</label>
        @if ($type === 'select')
            <select @class(['form-select', 'is-invalid' => $bad]) id="{{ $fid }}" name="{{ $name }}" @required($required) {{ $attributes }}>
                @if ($empty !== null)<option value="">{{ $empty }}</option>@endif
                @foreach ($options as $optionValue => $optionText)
                    <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionText }}</option>
                @endforeach
            </select>
        @elseif ($type === 'textarea')
            <textarea @class(['form-control', 'is-invalid' => $bad]) id="{{ $fid }}" name="{{ $name }}" rows="3" @required($required) @if ($placeholder) placeholder="{{ $placeholder }}" @endif {{ $attributes }}>{{ $current }}</textarea>
        @else
            <input @class(['form-control', 'is-invalid' => $bad]) type="{{ $type }}" id="{{ $fid }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : $current }}"
                   @required($required) @if ($placeholder) placeholder="{{ $placeholder }}" @endif @if ($step) step="{{ $step }}" @endif @if ($dir) dir="{{ $dir }}" @endif {{ $attributes }}>
        @endif
        @if ($bad)
            <div class="invalid-feedback">{{ $errors->first($key) }}</div>
        @elseif ($hint)
            <div class="form-text">{{ $hint }}</div>
        @endif
    @endif
</div>
