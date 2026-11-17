{{--
    One pop-up form for adding and changing (docs/DESIGN.md). Opened by a button with
    data-modal-form="#id" (and data-fill, data-action, data-method to change a row). It posts the
    ordinary way; after a validation error the page comes back and this form opens again as it
    was, with the errors. Fields: <x-field> or plain Bootstrap fields named like the request.
--}}
@props(['id', 'title', 'titleEdit' => null, 'action', 'size' => null, 'files' => false, 'submit' => null])
@php
    $again = old('_modal') === $id;
    $method = $again ? old('_method', 'POST') : 'POST';
    $target = $again ? old('_action', $action) : $action;
    $editing = $again && $method !== 'POST';
@endphp
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Title" aria-hidden="true" @if ($again) data-reopen @endif>
    <div @class(['modal-dialog modal-dialog-scrollable', 'modal-'.$size => $size])>
        <form class="modal-content" method="POST" action="{{ $target }}" data-store="{{ $action }}" @if ($files) enctype="multipart/form-data" @endif novalidate>
            @csrf
            <input type="hidden" name="_method" value="{{ $method }}">
            <input type="hidden" name="_modal" value="{{ $id }}">
            <input type="hidden" name="_action" value="{{ $target }}">
            <div class="modal-header">
                <h2 class="modal-title h5" id="{{ $id }}Title" data-title-new="{{ $title }}" data-title-edit="{{ $titleEdit ?? $title }}">{{ $editing ? ($titleEdit ?? $title) : $title }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                @if ($again && $errors->any())
                    <div class="alert alert-danger py-2" role="alert" data-form-errors>
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                {{ $slot }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ $submit ?? __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
