@extends('layouts.admin.base')

@section('title', __('administration.audit_logs'))
@section('css')
<link rel="stylesheet" href="{{ asset('assets/offline/ab1e55b05f962f8e.css') }}">
<link rel="stylesheet" href="{{ asset('assets/offline/ad2e98fea92964b2.css') }}">
<link href="{{ asset('assets/offline/febd1387d851569a.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="card rounded-4 border-0 shadow">
    <div class="card-header">
        <h4 class="card-title">{{ __('ui.audit_logs') }}</h4>
    </div>

    <div class="card-body">

        <div class="row g-3 mb-4 mt-2">
            <div class="col-md-3">
                <label class="form-label">🧑 {{ __('ui.user') }}</label>
                <select id="user_filter" class="form-select select2">
                    <option value="">{{ __('ui.all_users') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">🧾 {{ __('ui.model') }}</label>
                <select id="model_filter" class="form-select">
                    <option value="">{{ __('ui.all_models') }}</option>
                    <option value="expenses">{{ __('ui.expenses') }}</option>
                    <option value="account">{{ __('ui.account') }}</option>
                    <option value="transaction">{{ __('ui.transaction') }}</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">📅 {{ __('ui.date') }}</label>
                <input type="date" id="date_filter" class="form-control">
            </div>
        </div>

        <table class="table table-bordered w-100" id="auditTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('ui.user') }}</th>
                    <th>{{ __('ui.model') }}</th>
                    <th>{{ __('ui.event') }}</th>
                    <th>{{ __('ui.description') }}</th>
                    <th>{{ __('ui.data') }}</th>
                    <th>{{ __('ui.time') }}</th>
                </tr>
            </thead>
        </table>

    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="propertiesModal" tabindex="-1" aria-labelledby="propertiesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="propertiesModalLabel">{{ __('ui.log_properties') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <pre id="propertiesContent" class="bg-light p-3 rounded text-break" style="white-space: pre-wrap;"></pre>
      </div>

    </div>
  </div>
</div>
@endsection


@section('js')
<script src="{{ asset('assets/offline/77055c2f37cc45eb.js') }}"></script>
<script src="{{ asset('assets/offline/56701b6c377f817a.js') }}"></script>
<script src="{{ asset('assets/offline/33859893654b9de2.js') }}"></script>
<script>
    $('.select2').select2({ language: @include('admin.settings.partials.select-language') });

    let table = $('#auditTable').DataTable({
                language: @include('admin.settings.partials.table-language'),
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route('admin.audit.data') }}',
            data: function(d) {
                d.user_id = $('#user_filter').val();
                d.model = $('#model_filter').val();
                d.date = $('#date_filter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'user', name: 'user' },
            { data: 'log_name', name: 'log_name' },
            { data: 'event', name: 'event' },
            { data: 'description', name: 'description' },
            {
                data: 'properties',
                name: 'properties',
                render: function(data, type, row) {
                    const id = row.id;
                    const props = JSON.stringify(data, null, 2).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    return `<button class="btn btn-sm btn-primary view-properties" data-id="${id}" data-properties='${props}'>{{ __('ui.blade_view') }}</button>`;
                },
                orderable: false,
                searchable: false
            },
            { data: 'time', name: 'created_at' },
        ]
    });

    $('#user_filter, #model_filter, #date_filter').on('change', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.view-properties', function () {
        const props = $(this).data('properties');
        $('#propertiesContent').text(props);
        $('#propertiesModal').modal('show');
    });
</script>
@endsection
