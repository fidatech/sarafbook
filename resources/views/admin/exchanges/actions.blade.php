<div class="d-flex justify-content-center gap-2">

<button class="btn btn-sm btn-light action-btn view-btn" title="{{ __('exchanges.view') }}" aria-label="{{ __('exchanges.view') }}"
        data-id="{{ $row->id }}">
<i class="bi bi-eye text-primary"></i>
</button>

<a href="{{ route('admin.exchanges.edit',$row->id) }}"
   class="btn btn-sm btn-light action-btn" title="{{ __('exchanges.edit') }}" aria-label="{{ __('exchanges.edit') }}">
<i class="bi bi-pencil-square text-dark"></i>
</a>

<button class="btn btn-sm btn-light action-btn delete-btn" title="{{ __('exchanges.delete') }}" aria-label="{{ __('exchanges.delete') }}"
        data-id="{{ $row->id }}">
<i class="bi bi-trash text-danger"></i>
</button>

</div>
