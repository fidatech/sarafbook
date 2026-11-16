@extends('layouts.admin.base')
@section('title', __('administration.expense_categories'))

@section('css')
<link rel="stylesheet"
      href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
<style>
    .offcanvas-header .btn-close { margin: 0; }
</style>
@endsection

@section('content')

<div class="card shadow-sm border">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('ui.expense_categories') }}</h5>
        @can('create expense categories')
        <button class="btn btn-sm btn-primary" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasExpenseCategoryForm">
            <i class="bi bi-plus-circle me-2"></i> {{ __('ui.add_category') }}
        </button>
        @endcan
    </div>

    <div class="card-body p-4">
        <table id="expenseCategoryTable" class="table table-bordered table-hover table-striped w-100">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">#</th>
                    <th>{{ __('ui.name') }}</th>
                    <th>{{ __('ui.description') }}</th>
                    <th style="width:120px;">{{ __('ui.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Offcanvas Form -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExpenseCategoryForm">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="expenseCategoryFormTitle">{{ __('ui.new_expense_category') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                aria-label="{{ __('ui.close') }}"></button>
    </div>

    <div class="offcanvas-body">
        <form id="expenseCategoryForm">

            @csrf
            <input type="hidden" id="formMethod" value="POST">
            <input type="hidden" id="expenseCategoryId">

            <div class="mb-3">
                <label class="form-label">{{ __('ui.name') }} *</label>
                <input type="text" id="name" name="name"
                       class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('ui.description') }}</label>
                <textarea id="description" name="description"
                          class="form-control" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                {{ __('ui.save') }}
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

    // ===============================
    // DATATABLE
    // ===============================
    let table = $('#expenseCategoryTable').DataTable({
                language: @include('admin.settings.partials.table-language'),
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.expense-categories.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'name', name:'name' },
            { data: 'description', name:'description' },
            { data: 'actions', orderable:false, searchable:false }
        ]
    });


    // ===============================
    // OPEN CREATE FORM
    // ===============================
    const offcanvas = new bootstrap.Offcanvas('#offcanvasExpenseCategoryForm');

    function resetForm() {
        $('#expenseCategoryForm')[0].reset();
        $('#formMethod').val('POST');
        $('#expenseCategoryId').val('');
        $('#expenseCategoryFormTitle').text("{{ __('ui.add_expense_category') }}");
    }

    $('#offcanvasExpenseCategoryForm').on('hidden.bs.offcanvas', resetForm);


    // ===============================
    // EDIT
    // ===============================
    $(document).on('click', '.edit-btn', function () {

        $('#expenseCategoryFormTitle').text("{{ __('ui.edit_expense_category') }}");

        $('#formMethod').val('PUT');
        $('#expenseCategoryId').val($(this).data('id'));

        $('#name').val($(this).data('name'));
        $('#description').val($(this).data('description'));

        offcanvas.show();
    });


    // ===============================
    // CREATE / UPDATE SUBMIT
    // ===============================
    $('#expenseCategoryForm').submit(function (e) {
        e.preventDefault();

        let id = $('#expenseCategoryId').val();
        let method = $('#formMethod').val();
        let url = id
            ? `/admin/expense-categories/${id}`
            : `{{ route('admin.expense-categories.store') }}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: $(this).serialize() + '&_method=' + method,
            success: function (res) {
                administrationSwal.fire({
                    icon: 'success',
                    title: res.message,
                    timer: 1400,
                    showConfirmButton: false
                });

                offcanvas.hide();
                table.ajax.reload();
            }
        });
    });


    // ===============================
    // DELETE
    // ===============================
    $(document).on('click', '.delete-btn', function () {
        let id = $(this).data('id');

        administrationSwal.fire({
            title: "{{ __('ui.delete_title') }}",
            text: "{{ __('ui.delete_confirm') }}",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "{{ __('ui.delete') }}"
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: `/admin/expense-categories/${id}`,
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "DELETE"
                },
                success: function (res) {
                    administrationSwal.fire({
                        icon: 'success',
                        title: res.message,
                        timer: 1400,
                        showConfirmButton: false
                    });

                    table.ajax.reload();
                }
            });
        });
    });

});
</script>
@endsection
