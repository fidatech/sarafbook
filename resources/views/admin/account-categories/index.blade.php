@extends('layouts.admin.base')

@section('title', __('administration.account_categories'))
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
    <style>
        .badge-label {
            font-size: 0.75rem;
            padding: 0.35em 0.6em;
        }
    </style>
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('ui.account_categories') }}</h5>
            @can('create account categories')
            <button class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasAdd">
                <i class="bi bi-plus-circle"></i>
                <span>{{ __('ui.add_new') }}</span>
            </button>
            @endcan
        </div>

        <div class="card-body">
            <table class="table-hover table-striped table align-middle" id="categories-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.category') }}</th>
                        <th>{{ __('ui.code_prefix') }}</th>
                        <th>{{ __('ui.icon_preview') }}</th>
                        <th>{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Offcanvas Add/Edit Form -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdd">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">{{ __('ui.add_category') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body">
            <form method="POST" id="categoryForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="category_id" id="categoryId">

                <div class="mb-3">
                    <label class="form-label">
                        {{ __('ui.name') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        {{ __('ui.code_prefix') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="code_prefix" id="code_prefix"
                           class="form-control"
                           required maxlength="10"
                           pattern="^[\w\s]{1,10}$"
                           title="{{ __('ui.code_prefix_limit') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('ui.bootstrap_icon') }}</label>
                    <div class="input-group mb-2">
                        <input type="text" name="bi_icon" id="bi_icon" class="form-control"
                               placeholder="{{ __('ui.icon_placeholder') }}">
                        <button class="btn btn-outline-secondary" type="button"
                                data-bs-toggle="modal" data-bs-target="#iconPickerModal">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </button>
                    </div>

                    <div class="mb-3 text-center">
                        <i id="iconPreview" class="bi fs-1" style="display:none;"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('ui.icon_color') }}</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color"
                               id="colorPicker" value="#28a745"
                               title="{{ __('ui.choose_color') }}">
                        <input type="text" name="bi_icon_color" id="bi_icon_color"
                               class="form-control" value="#28a745">
                    </div>
                </div>

                <button class="btn btn-primary w-100">
                    {{ __('ui.save_category') }}
                </button>
            </form>
        </div>
    </div>

    <!-- Icon Picker Modal -->
    <div class="modal fade" id="iconPickerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ui.select_icon') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3" id="icon-grid">
                        <!-- Icons added by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
    <script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>
    <script>
const administrationSwal = Swal.mixin({confirmButtonText: @json(__('ui.blade_ok')), cancelButtonText: @json(__('ui.cancel'))});
        // Hardcoded Bootstrap Icons (reliable fallback)
        const allIcons = [
            'wallet', 'cash-coin', 'credit-card', 'currency-dollar', 'bank', 'building', 'shop',
            'gear', 'tools', 'person', 'people', 'person-circle', 'person-badge',
            'book', 'book-half', 'journal-text', 'clipboard',
            'calendar', 'clock', 'alarm',
            'envelope', 'chat', 'telephone', 'globe', 'geo-alt',
            'cloud', 'cloud-upload', 'cloud-download',
            'star', 'heart', 'shield', 'lock', 'unlock',
            'key', 'tag', 'tags', 'barcode',
            'image', 'camera', 'film', 'play-btn', 'pause-btn',
            'music-note', 'mic', 'volume-up',
            'search', 'filter', 'sliders', 'columns-gap',
            'folder', 'file-earmark', 'archive', 'trash', 'upload',
            'bell', 'flag', 'map', 'compass', 'location',
            'wifi', 'bluetooth', 'usb',
            'question-circle', 'exclamation-triangle', 'info-circle'
        ];

        $(document).ready(function() {
            const iconGrid = $('#icon-grid');
            const iconSearch = $('<input type="text" class="form-control mb-3" placeholder="{{ __('ui.blade_search_icons') }}">');
            iconGrid.before(iconSearch);

            function populateIconGrid(iconList) {
                iconGrid.empty();
                iconList.forEach(icon => {
                    const button = `<div class="col-2 text-center">
                <button type="button" class="btn btn-light w-100 pick-icon" data-icon="${icon}">
                    <i class="bi bi-${icon} fs-3"></i>
                </button>
            </div>`;
                    iconGrid.append(button);
                });
            }

            populateIconGrid(allIcons);

            iconSearch.on('input', function() {
                const query = $(this).val().toLowerCase();
                const filtered = allIcons.filter(icon => icon.includes(query));
                populateIconGrid(filtered);
            });

            function updateIconPreview() {
                const icon = $('#bi_icon').val();
                const color = $('#bi_icon_color').val();
                if (icon) {
                    $('#iconPreview')
                        .attr('class', 'bi bi-' + icon + ' fs-1')
                        .css('color', color || '#333')
                        .show();
                } else {
                    $('#iconPreview').hide();
                }
            }

            $('#bi_icon').on('input', updateIconPreview);
            $('#bi_icon_color').on('input', updateIconPreview);

            $('#colorPicker').on('input', function() {
                $('#bi_icon_color').val($(this).val()).trigger('input');
            });

            $(document).on('click', '.pick-icon', function() {
                const icon = $(this).data('icon');
                $('#bi_icon').val(icon).trigger('input');
                $('#iconPickerModal').modal('hide');
            });

            updateIconPreview();

            // Edit button support
            $(document).on('click', '.edit-btn', function() {
                const data = $(this).data();
                $('#categoryId').val(data.id);
                $('#name').val(data.name);
                $('#code_prefix').val(data.prefix);
                $('#bi_icon').val(data.icon);
                $('#bi_icon_color').val(data.color);
                $('#colorPicker').val(data.color);
                $('#formMethod').val('PUT');
                $('#categoryForm').attr('action', `/admin/account-categories/${data.id}`);
                updateIconPreview();
                new bootstrap.Offcanvas('#offcanvasAdd').show();
            });

            // Delete button with Swal
            $(document).on('click', '.delete-btn', function() {
                const id = $(this).data('id');
                administrationSwal.fire({
                    title: '{{ __('ui.are_you_sure') }}',
                    text: '{{ __('ui.blade_this_will_permanently_delete_the_category') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('ui.blade_yes_delete_it_2') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/account-categories/${id}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function() {
                                $('#categories-table').DataTable().ajax.reload();
                                administrationSwal.fire({
                                    icon: 'success',
                                    title: '{{ __('ui.blade_deleted_2') }}',
                                    text: '{{ __('ui.blade_category_has_been_deleted') }}',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            },
                            error: function() {
                                administrationSwal.fire(@json(__('administration.error')), @json(__('administration.delete_category_failed')), 'error');
                            }
                        });
                    }
                });
            });

            // AJAX form submit with toast
            $('#categoryForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const method = $('#formMethod').val();
                const action = form.attr('action');
                const formData = form.serialize();

                $.ajax({
                    url: action,
                    method: method === 'PUT' ? 'POST' : 'POST',
                    data: formData,
                    success: function(res) {
                        $('#offcanvasAdd').offcanvas('hide');
                        $('#categories-table').DataTable().ajax.reload(null, false);

                        administrationSwal.fire({
                            icon: 'success',
                            title: '{{ __('ui.blade_saved_2') }}',
                            text: '{{ __('ui.blade_category_saved_successfully') }}',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(err) {
                        alert(`{{ __('administration.error') }}: ${err.responseText}`);
                    }
                });
            });


            $('#categories-table').DataTable({
                language: @include('admin.settings.partials.table-language'),
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.account-categories.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name',
                        render: (data) => `<span class="badge bg-primary badge-label">${data}</span>`
                    },
                    {
                        data: 'code_prefix',
                        name: 'code_prefix',
                        render: (data) => `<span class="badge bg-info badge-label">${data}</span>`
                    },
                    {
                        data: 'bi_icon',
                        name: 'bi_icon',
                        render: (data, type, row) => `<span style="background-color: ${row.bi_icon_color}" class="badge d-inline-block">${data} <i class="bi bi-${data} ms-1"></i></span>`

                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });
    </script>
@endsection
