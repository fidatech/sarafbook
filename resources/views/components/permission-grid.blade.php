{{--
    Permissions as a grid: one row per part of the system, one column per action, a box where the
    action exists. Names stay the system's ("update transactions"); labels come from AccessLabels.
    <x-permission-grid :permissions="$permissions" :selected="['view accounts', …]" name="permissions[]" />
--}}
@props(['permissions', 'selected' => [], 'name' => 'permissions[]', 'id' => 'grid', 'disabled' => false])
@php
    $order = ['view', 'create', 'update', 'delete', 'export', 'print', 'send-whatsapp'];
    $groups = [];
    foreach ($permissions as $permission) {
        [$action, $module] = array_pad(explode(' ', $permission->name, 2), 2, 'general');
        $groups[$module][$action] = $permission->name;
    }
    $actions = collect($groups)->flatMap(fn ($g) => array_keys($g))->unique()->sortBy(fn ($a) => ($i = array_search($a, $order, true)) === false ? 99 : $i)->values();
    $selected = array_flip(array_map('strval', is_array($selected) ? $selected : collect($selected)->all()));
    $actionLabel = fn ($a) => ['view' => __('View'), 'create' => __('Add'), 'update' => __('Change'), 'delete' => __('Delete'), 'export' => __('Export'), 'print' => __('Print'), 'send-whatsapp' => __('WhatsApp')][$a] ?? ucfirst($a);
@endphp
<div class="table-responsive data-wrap perm-grid" data-perm-grid>
    <table class="table w-100 mb-0 data-table keep-table">
        <thead>
            <tr>
                <th scope="col">{{ __('Part of the system') }}</th>
                @foreach ($actions as $action)
                    <th scope="col" class="text-center">{{ $actionLabel($action) }}</th>
                @endforeach
                <th scope="col" class="text-center">{{ __('All') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $module => $row)
                <tr>
                    <th scope="row" class="fw-semibold">{{ \App\Support\AccessLabels::group($module) }}</th>
                    @foreach ($actions as $action)
                        <td class="text-center">
                            @isset($row[$action])
                                <input class="form-check-input" type="checkbox" name="{{ $name }}" value="{{ $row[$action] }}" id="{{ $id }}_{{ md5($row[$action]) }}"
                                       aria-label="{{ \App\Support\AccessLabels::permission($row[$action]) }}" @checked(isset($selected[$row[$action]])) @disabled($disabled)>
                            @else
                                <span class="text-muted" aria-hidden="true">·</span>
                            @endisset
                        </td>
                    @endforeach
                    <td class="text-center"><input class="form-check-input" type="checkbox" data-perm-row aria-label="{{ __('All') }} — {{ \App\Support\AccessLabels::group($module) }}" @disabled($disabled)></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@pushOnce('scripts', 'permission-grid')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var syncRow = function (tr) {
        var boxes = tr.querySelectorAll('input[type=checkbox]:not([data-perm-row])'), all = tr.querySelector('[data-perm-row]');
        if (!all) return;
        var on = Array.prototype.filter.call(boxes, function (b) { return b.checked; }).length;
        all.checked = on === boxes.length && on > 0; all.indeterminate = on > 0 && on < boxes.length;
    };
    var syncAll = function (root) { (root || document).querySelectorAll('[data-perm-grid] tbody tr').forEach(syncRow); };
    document.addEventListener('change', function (e) {
        var grid = e.target.closest && e.target.closest('[data-perm-grid]');
        if (!grid) return;
        var tr = e.target.closest('tr');
        if (e.target.hasAttribute('data-perm-row')) tr.querySelectorAll('input[type=checkbox]:not([data-perm-row])').forEach(function (b) { b.checked = e.target.checked; });
        syncRow(tr);
    });
    document.addEventListener('erp:form-open', function (e) { syncAll(e.target); });
    syncAll();
});
</script>
@endPushOnce
