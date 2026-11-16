<!-- DataTables -->
<script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
<script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>
<script src="{{ asset('assets/offline/33859893654b9de2.js') }}"></script>

<script>
    const accountsSwal = Swal.mixin({confirmButtonText: @json(__('accounts.ok')), cancelButtonText: @json(__('ui.cancel'))});
    $(function() {
        $('#create_account_sub_category_id').select2({
                language: @include('admin.accounts.partials.select-language'),
            dropdownParent: $('#offcanvasAddUser'),
            width: '100%',
            placeholder: '{{{ __('ui.select_account_type') }}}'
        });

        const table = $('#yajra-datatable').DataTable({
                language: @include('admin.accounts.partials.table-language'),
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.accounts.fetch-accounts') }}",
                data: function(d) {
                    d.account_type_id = $('#account_type').val();
                    d.account_sub_category_id = $('#account-sub-category').val();
                    d.search_term = $('#search').val();
                    d.length = $('#per-page').val();
                    d.balance_filter = $('#balance_filter').val();
                }
            },
            columns: [{
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + 1
                },
                {
                    data: 'name_with_profile',
                    name: 'name',
                    orderable: false
                },
                {
                    data: 'account_type_with_icon',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'address',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'balance',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [2, 'asc']
            ],
            pageLength: 10,
            searching: false,
            lengthChange: false,
            info: false
        });

        $(document).on('click', '.btn-edit-account', function(e) {
            e.preventDefault();
            const id = $(this).data('id');

            fetch(`/admin/accounts/${id}/edit`)
                .then(res => res.json())
                .then(data => {
                    $('#offcanvasAddUserLabel').text(@json(__('accounts.edit_account')));

                    $('#account-name').val(data.name);
                    $('#account-contact').val(data.contact);
                    $('#account-address').val(data.address);
                    $('#account-company').val(data.company);
                    $('#include_in_reports').prop('checked', Number(data.include_in_reports) === 1);
                    // ✅ REQUIRED
                    // split prefix + suffix
                    const code = data.code || '';
                    const prefixMatch = code.match(/^[^0-9A-Za-z]*/); // like "#"
                    const prefix = prefixMatch ? prefixMatch[0] : '';
                    const suffix = code.replace(prefix, '');

                    $('#account-code-prefix').text(prefix || '#');
                    $('#account-code-suffix').val(suffix);
                    $('#full-account-code').val(code);
                    $('#create_account_sub_category_id')
                        .val(data.account_sub_category_id)
                        .trigger('change');

                    $('#edit_id').val(data.id);
                    $('#edit_mode').val('1');

                    // ✅ IMPORTANT
                    $('#form_method').val('PUT');

                    const offcanvas = new bootstrap.Offcanvas('#offcanvasAddUser');
                    offcanvas.show();
                });
        });

        $(document).on('click', '.btn-preview-account', function() {
            const id = $(this).data('id');

            $('#accountPreviewContent').html('<div class="p-5 text-center">{{ __('ui.blade_loading_preview') }}</div>');

            $.get(`/admin/accounts/${id}/print`, function(html) {
                $('#accountPreviewContent').html(html);
                const modal = new bootstrap.Modal(document.getElementById('accountPreviewModal'));
                modal.show();
            }).fail(function() {
                $('#accountPreviewContent').html('<div class="p-5 text-danger text-center">{{ __('ui.blade_failed_to_load_preview') }}</div>');
            });
        });

        $(document).on('click', '.btn-send-whatsapp', function() {
            const url = $(this).data('url');

            accountsSwal.fire({
                title: '{{ __('ui.blade_send_whatsapp') }}',
                text: '{{ __('ui.blade_do_you_want_to_send_a_message_to_this_customer') }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '{{ __('ui.blade_yes_send_it') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get(url, function(res) {

                        accountsSwal.fire(@json(__('accounts.success')), res.message, 'success').then(() => {
                            // ✅ Open WhatsApp link after Swal success
                            window.open(res.url, '_blank');
                        });

                    }).fail(function(xhr) {
                        accountsSwal.fire(@json(__('accounts.error')), xhr.responseJSON?.error || @json(__('accounts.send_failed')), 'error');
                        console.log(xhr.responseJSON.error);
                    });
                }
            });
        });

        $('#addNewAccountForm').on('submit', function(e) {
            e.preventDefault();

            const isEdit = $('#edit_mode').val() === '1';
            const id = $('#edit_id').val();

            const url = isEdit ?
                `/admin/accounts/${id}` :
                "{{ route('admin.accounts.store') }}";

            $.ajax({
                url: url,
                type: 'POST',
                data: $(this).serialize(), // includes _method=PUT
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function() {

                    const offcanvasEl = document.getElementById('offcanvasAddUser');
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);

                    offcanvas.hide();

                    // ✅ RESET FORM
                    $('#addNewAccountForm')[0].reset();
                    $('#include_in_reports').prop('checked', true);
                    // reset select2
                    $('#create_account_sub_category_id').val(null).trigger('change');

                    // reset edit mode
                    $('#edit_mode').val('0');
                    $('#edit_id').val('');
                    $('#form_method').val('POST');

                    // reset code prefix/suffix UI
                    $('#account-code-prefix').text('#');
                    $('#account-code-suffix').val('');
                    $('#full-account-code').val('');
                    $('#code-feedback').html('');

                    accountsSwal.fire({
                        icon: 'success',
                        title: isEdit ? @json(__('accounts.account_updated')) : @json(__('accounts.account_created')),
                        timer: 1500,
                        showConfirmButton: false
                    });

                    $('#yajra-datatable').DataTable().ajax.reload(null, false);
                },
                error: function(xhr) {
                    console.error(xhr.responseJSON);
                    accountsSwal.fire(@json(__('accounts.error')), @json(__('accounts.validation_failed')), 'error');
                }
            });
        });

        $(document).on('click', '.btn-delete-account', function(e) {
            e.preventDefault();
            const url = $(this).data('url');

            accountsSwal.fire({
                title: '{{{ __('ui.are_you_sure') }}}',
                text: '{{ __('ui.blade_this_account_will_be_permanently_deleted') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __('ui.blade_yes_delete_it_2') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            accountsSwal.fire({
                                icon: 'success',
                                title: '{{ __('ui.blade_deleted_2') }}',
                                text: response.message || @json(__('accounts.account_deleted')),
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $('#yajra-datatable').DataTable().ajax.reload(null, false);
                        },
                        error: function() {
                            accountsSwal.fire({
                                icon: 'error',
                                title: '{{ __('ui.blade_failed_2') }}',
                                text: '{{ __('ui.blade_could_not_delete_account') }}'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btn-create-user', function() {
            const accountId = $(this).data('id');
            $('#account_id').val(accountId);

            $('#createUserForm input[name="name"]').val('');
            $('#createUserForm input[name="username"]').val('');

            $.ajax({
                url: `/admin/accounts/${accountId}/info`,
                method: 'GET',
                success: function(data) {
                    $('#createUserForm input[name="name"]').val(data.name);
                    $('#createUserForm input[name="username"]').val(data.code);
                    $('#createUserModal').modal('show');
                },
                error: function() {
                    accountsSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_error') }}',
                        text: '{{ __('ui.blade_could_not_fetch_account_information') }}'
                    });
                }
            });
        });

        $('#createUserForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('admin.accounts.create-user') }}",
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        accountsSwal.fire({
                            icon: 'success',
                            title: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#createUserModal').modal('hide');
                        $('#yajra-datatable').DataTable().ajax.reload(null, false);
                    } else {
                        accountsSwal.fire({
                            icon: 'error',
                            title: '{{ __('ui.blade_error') }}',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    accountsSwal.fire({
                        icon: 'error',
                        title: '{{ __('ui.blade_failed') }}',
                        text: xhr.responseJSON?.message || @json(__('accounts.something_wrong'))
                    });
                }
            });
        });

        $('#account_type, #currency_id, #per-page').on('change', () => table.draw());
        $('#account-sub-category').on('change', () => table.draw());
        $('#search-btn').on('click', () => table.draw());
        $('#search').on('keyup', e => e.key === 'Enter' && table.draw());
        $('#balance_filter, #currency_id').on('change', function() {
            $('#yajra-datatable').DataTable().ajax.reload();
        });

        $('#account_type').on('change', function() {
            let id = $(this).val();
            let sub = $('#account-sub-category');
            sub.empty().append('<option value="">{{ __('ui.category') }}</option>');
            if (!id) return;
            $.get(`/account/subcategories/${id}`, data => {
                data.forEach(cat => sub.append(`<option value="${cat.id}">${cat.name}</option>`));
                table.draw();
            });
        });

        function fetchStats() {
            $.get("{{ route('admin.accounts.update-stats') }}", {
                account_category_id: $('#account_type').val(),
                account_sub_category_id: $('#account-sub-category').val(),
                currency_id: $('#currency_id').val()
            }, function(data) {

                $('#total_accounts').text(data.total_accounts);

                let creditHtml = '';
                let debitHtml = '';
                let balanceHtml = '';

                if (data.currencies.length === 0) {
                    creditHtml = '0';
                    debitHtml = '0';
                    balanceHtml = '0';
                } else {

                    data.currencies.forEach(item => {

                        creditHtml += `
                    <div style="font-size:13px;">
                        ${item.currency} ${item.credit}
                    </div>
                `;

                        debitHtml += `
                    <div style="font-size:13px;">
                        ${item.currency} ${item.debit}
                    </div>
                `;

                        const color = parseFloat(item.balance.replace(/,/g, '')) < 0 ?
                            '#b23434' :
                            '#2b2bc7';

                        balanceHtml += `
                    <div style="font-size:13px; color:${color};">
                        ${item.currency} ${item.balance}
                    </div>
                `;
                    });
                }

                $('#total_credit').html(creditHtml);
                $('#total_debit').html(debitHtml);
                $('#balance').html(balanceHtml);

            });
        }

        fetchStats();
        $('#account_type, #account-sub-category, #currency_id').on('change', fetchStats);

        // Create Form interactions
        const subCategorySelect = document.getElementById('create_account_sub_category_id');
        const prefix = document.getElementById('account-code-prefix');
        const suffix = document.getElementById('account-code-suffix');
        const fullCode = document.getElementById('full-account-code');
        const feedback = document.getElementById('code-feedback');
        const submitBtn = document.querySelector('#addNewAccountForm button[type="submit"]');

        function updateFullCode() {
            const val = `${prefix.textContent.trim()}${suffix.value.trim().toUpperCase()}`;
            suffix.value = val.replace(prefix.textContent.trim(), '');
            fullCode.value = val;
        }

        function setFeedback(type) {

            if (type === 'loading') {
                feedback.innerHTML = `
            <span class="spinner-border spinner-border-sm text-secondary"></span>
            <span class="text-muted ms-1">{{ __('ui.blade_checking') }}</span>
        `;
            }

            if (type === 'available') {
                feedback.innerHTML = `
            <i class="bi bi-check-circle-fill text-success"></i>
            <span class="text-success ms-1 fw-semibold">{{ __('ui.blade_code_is_available') }}</span>
        `;
                suffix.classList.add('is-valid');
                suffix.classList.remove('is-invalid');
                submitBtn.disabled = false;
            }

            if (type === 'exists') {
                feedback.innerHTML = `
            <i class="bi bi-x-circle-fill text-danger"></i>
            <span class="text-danger ms-1 fw-semibold">{{ __('ui.blade_code_already_exists') }}</span>
        `;
                suffix.classList.add('is-invalid');
                suffix.classList.remove('is-valid');
                submitBtn.disabled = true;
            }
        }

        let checkTimeout;

        function checkCodeAvailability() {
            clearTimeout(checkTimeout);

            checkTimeout = setTimeout(() => {

                const code = fullCode.value;
                const editMode = $('#edit_mode').val() === '1';
                const editId = $('#edit_id').val();

                if (!code) {
                    feedback.innerHTML = '';
                    return;
                }

                setFeedback('loading');

                fetch(`{{ route('admin.accounts.checkCode') }}?code=${encodeURIComponent(code)}&ignore_id=${editMode ? editId : ''}`)
                    .then(res => res.json())
                    .then(data => {
                        data.available ? setFeedback('available') : setFeedback('exists');
                    })
                    .catch(err => {
                        console.error(err);
                        feedback.innerHTML = `<span class="text-danger">{{ __('ui.blade_server_error') }}</span>`;
                    });

            }, 300);
        }

        // ✅ FIXED: use subcategory instead of category
        subCategorySelect.addEventListener('change', function() {
            const id = this.value;

            if (!id) {
                prefix.textContent = '--';
                updateFullCode();
                return;
            }

            fetch(`/admin/accounts/category-prefix/${id}`)
                .then(res => res.json())
                .then(data => {
                    prefix.textContent = data.prefix || '--';
                    updateFullCode();
                    checkCodeAvailability();
                });
        });

        // typing
        suffix.addEventListener('input', () => {
            updateFullCode();
            checkCodeAvailability();
        });

        // submit protection
        $('#addNewAccountForm').on('submit', function(e) {

            if (suffix.classList.contains('is-invalid')) {
                e.preventDefault();
                accountsSwal.fire(@json(__('accounts.error')), @json(__('accounts.code_exists')), 'error');
                return;
            }

        });
    });
</script>
