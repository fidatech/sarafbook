<div class="d-flex justify-content-center align-items-center gap-2">

    @if(strcasecmp((string) $row->table_name, 'Journal') === 0)
    <a class="btn btn-sm btn-success" href="{{ route('admin.transactions.print', $row) }}" target="_blank" title="{{ __('journal_receipt.print_receipt') }}" aria-label="{{ __('journal_receipt.print_receipt') }}">
        <i class="bi bi-printer"></i>
    </a>
    @endif

    <button class="btn btn-sm btn-primary btn-edit" title="{{ __('transactions.edit') }}" aria-label="{{ __('transactions.edit') }}" data-id="{{ $row->id }}">
<i class="bi bi-pencil"></i>
</button>

<button class="btn btn-sm btn-danger btn-delete" title="{{ __('transactions.delete') }}" aria-label="{{ __('transactions.delete') }}" data-id="{{ $row->id }}">
<i class="bi bi-trash"></i>
</button>

</div>
