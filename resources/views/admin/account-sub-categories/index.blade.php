@extends('layouts.admin.base')

@section('title', __('administration.account_sub_categories'))
@section('css')
<link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
<style>
  .datatable-header {
    margin-bottom: 1.5rem;
  }
  .table th,
  .table td {
    vertical-align: middle;
  }
</style>
@endsection

@section('content')
<div class="card shadow-sm border-0">
  <div class="card-header d-flex justify-content-between align-items-center py-3">
    <div>
      <h4 class="mb-0">{{ __('ui.account_sub_categories') }}</h4>
    </div>
    @can('create account sub categories')
    <button class="btn btn-primary d-flex align-items-center gap-2"
            data-bs-toggle="offcanvas" data-bs-target="#offcanvasForm">
      <i class="bi bi-plus-circle"></i>
      <span>{{ __('ui.add_sub_category') }}</span>
    </button>
    @endcan
  </div>

  <div class="card-body pt-2">
    <div class="row mb-3">
      <div class="col-md-4">
        <select id="category_filter" class="form-select">
          <option value="">{{ __('ui.filter_by_category') }}</option>
          @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <table class="table table-striped table-bordered table-hover border align-middle" id="subcategories-table">
      <thead class="table-light">
        <tr>
          <th style="width: 5%">#</th>
          <th>{{ __('ui.name') }}</th>
          <th>{{ __('ui.category') }}</th>
          <th style="width: 15%">{{ __('ui.actions') }}</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Offcanvas Form -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasForm">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">{{ __('ui.sub_category_form') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>

  <div class="offcanvas-body">
    <form id="subcategoryForm">
      @csrf

      <input type="hidden" name="_method" id="formMethod" value="POST">
      <input type="hidden" name="id" id="subcategoryId">

      <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('ui.name') }}</label>
        <input type="text" name="name" id="name" class="form-control"
               placeholder="{{ __('ui.enter_sub_category_name') }}" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('ui.account_category') }}</label>
        <select name="account_category_id" id="account_category_id"
                class="form-select" required>
          <option value="">{{ __('ui.select_parent_category') }}</option>
          @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </div>

      <button class="btn btn-success w-100 mt-4">
        <i class="bi bi-save me-1"></i> {{ __('ui.save_sub_category') }}
      </button>
    </form>
  </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
<script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>
<script>
const administrationSwal = Swal.mixin({confirmButtonText: @json(__('ui.blade_ok')), cancelButtonText: @json(__('ui.cancel'))});
$(function () {
  const table = $('#subcategories-table').DataTable({
                language: @include('admin.settings.partials.table-language'),
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route('admin.account-sub-categories.index') }}',
      data: function (d) {
        d.category_id = $('#category_filter').val();
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {
        data: 'name',
        name: 'name',
        render: (data) => `<span class="badge bg-primary badge-label">${data}</span>`
      },
      {
        data: 'category',
        name: 'category',
        render: (data) => `<span class="badge bg-info badge-label">${data}</span>`
      },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    order: [[0, 'asc']],
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    searching: false,
    lengthChange: false,
    info: false
  });

  $('#category_filter').on('change', function () {
    table.ajax.reload();
  });

  $('#subcategoryForm').on('submit', function (e) {
    e.preventDefault();
    const method = $('#formMethod').val();
    const id = $('#subcategoryId').val();
    const url = id ? `/admin/account-sub-categories/${id}` : '{{ route('admin.account-sub-categories.store') }}';

    $.ajax({
      url: url,
      method: 'POST',
      data: $(this).serialize(),
      success: function () {
        $('#offcanvasForm').offcanvas('hide');
        table.ajax.reload();
        administrationSwal.fire({ icon: 'success', title: '{{ __('ui.blade_saved') }}', text: '{{ __('ui.blade_sub_category_saved_successfully') }}', timer: 1500, showConfirmButton: false });
      },
      error: function () {
        administrationSwal.fire(@json(__('administration.error')), @json(__('administration.error_message')), 'error');
      }
    });
  });

  $(document).on('click', '.edit-btn', function () {
    const data = $(this).data();
    $('#subcategoryId').val(data.id);
    $('#name').val(data.name);
    $('#account_category_id').val(data.category);
    $('#formMethod').val('PUT');
    $('#subcategoryForm').attr('action', `/admin/account-sub-categories/${data.id}`);
    new bootstrap.Offcanvas('#offcanvasForm').show();
  });

  $(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    administrationSwal.fire({
      title: '{{ __('ui.are_you_sure') }}',
      text: '{{ __('ui.blade_this_will_delete_the_subcategory') }}',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '{{ __('ui.blade_yes_delete_it_2') }}'
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `/admin/account-sub-categories/${id}`,
          type: 'DELETE',
          data: { _token: '{{ csrf_token() }}' },
          success: function () {
            table.ajax.reload();
            administrationSwal.fire({ icon: 'success', title: '{{ __('ui.blade_deleted') }}', text: '{{ __('ui.blade_sub_category_deleted') }}', timer: 1500, showConfirmButton: false });
          },
          error: function () {
            administrationSwal.fire(@json(__('administration.error')), @json(__('administration.delete_failed')), 'error');
          }
        });
      }
    });
  });
});
</script>
@endsection
