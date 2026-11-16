{{-- Messages set with ->with('success', '...') and validation errors are shown as toasts of the template. --}}
<div id="app-flashes" hidden>
    @foreach (['success' => 'success', 'warning' => 'warning', 'danger' => 'danger', 'error' => 'danger', 'info' => 'info'] as $key => $type)
        @if (session($key))
            <div data-toast="{{ $type }}">{{ session($key) }}</div>
        @endif
    @endforeach
    @if (isset($errors) && $errors->any())
        <div data-toast="danger">{{ implode(' · ', $errors->all()) }}</div>
    @endif
</div>
