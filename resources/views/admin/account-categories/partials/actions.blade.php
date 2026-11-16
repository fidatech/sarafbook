<div class="d-flex align-items-center gap-1">

    {{-- Edit Button --}}
    @can('update account categories')
        <button class="btn btn-sm btn-outline-warning edit-btn"
            data-id="{{ $row->id }}"
            data-name="{{ $row->name }}"
            data-prefix="{{ $row->code_prefix }}"
            data-icon="{{ $row->bi_icon }}"
            data-color="{{ $row->bi_icon_color }}"
            title="{{ __('ui.blade_edit') }}">
            <i class="bi bi-pencil"></i>
        </button>
    @endcan

    {{-- Delete Button --}}
    @can('delete account categories')
        <button class="btn btn-sm btn-outline-danger delete-btn"
            data-id="{{ $row->id }}"
            title="{{ __('ui.delete') }}">
            <i class="bi bi-trash"></i>
        </button>
    @endcan

</div>
