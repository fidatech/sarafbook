{{--
    The menu of the panel. $shell is "tenant" (a Sarafi) or "platform".
    An entry is [label, icon, url, current, permission] or a group ['group' => label, 'icon' => ..., 'items' => [...]].
    An entry without permission is shown to everyone; a group without a visible entry is left out.
--}}
@php
    $is = fn (string ...$patterns) => request()->is(...$patterns);

    if ($shell === 'platform') {
        $user = auth('platform')->user();
        $role = __('Platform administrator');
        $brandName = config('app.name');
        $logo = null;
        $menu = [
            ['heading' => __('Platform')],
            [__('Dashboard'), 'gauge-high', route('platform.dashboard'), request()->routeIs('platform.dashboard'), null],
            [__('Sarafis'), 'building-columns', route('platform.tenants.index'), request()->routeIs('platform.tenants.*'), null],
            [__('Plans'), 'layer-group', route('platform.plans.index'), request()->routeIs('platform.plans.*'), null],
            [__('Audit log'), 'clipboard-check', route('platform.audit'), request()->routeIs('platform.audit'), null],
        ];
    } else {
        $user = auth()->user();
        $role = $user?->getRoleNames()->first() ?? $user?->account_type;
        $brandName = $setting?->company_name ?: config('app.name');
        $logo = $setting?->logo ? asset('images/'.$setting->logo) : null;
        $menu = [
            ['heading' => __('ui.dashboard')],
            [__('ui.dashboard'), 'gauge-high', url('admin/dashboard'), $is('admin/dashboard*'), 'view dashboard'],
            [__('ui.accounts'), 'users', url('admin/accounts'), $is('admin/accounts*'), 'view customers'],
            [__('ui.safe'), 'vault', url('admin/safe'), $is('admin/safe*'), 'view safe'],
            [__('ui.transactions'), 'right-left', url('admin/transactions'), $is('admin/transactions*'), 'view transactions'],
            [__('ui.journal'), 'book', url('admin/journal'), $is('admin/journal*'), 'view journal'],
            ['group' => __('ui.exchanges'), 'icon' => 'coins', 'id' => 'exchanges', 'can' => 'view exchanges', 'items' => [
                [__('ui.new_exchange'), route('admin.exchanges.create'), request()->routeIs('admin.exchanges.create'), null],
                [__('ui.exchange_ledger'), route('admin.exchanges.index'), request()->routeIs('admin.exchanges.index'), null],
                [__('ui.exchange_rates'), route('admin.exchange-rates.index'), request()->routeIs('admin.exchange-rates.*'), 'view exchange rates'],
            ]],
            ['group' => __('ui.remittances'), 'icon' => 'paper-plane', 'id' => 'remittances', 'can' => 'view remittances', 'items' => [
                [__('ui.new_remittance'), route('admin.remittances.createNew'), request()->routeIs('admin.remittances.createNew'), null],
                [__('ui.outgoing'), route('admin.remittances.outgoing'), request()->routeIs('admin.remittances.outgoing'), null],
                [__('ui.incoming'), route('admin.remittances.incomingPaid'), request()->routeIs('admin.remittances.incomingPaid'), null],
                [__('ui.pending_remittances'), route('admin.remittances.pendingIncoming'), request()->routeIs('admin.remittances.pendingIncoming'), null],
            ]],
            [__('ui.expenses'), 'wallet', url('admin/expenses'), $is('admin/expenses*'), 'view expenses'],
            ['group' => __('ui.reports'), 'icon' => 'chart-column', 'id' => 'reports', 'can' => 'view reports', 'items' => [
                [__('ui.balance_report'), url('admin/reports/balance'), $is('admin/reports/balance*'), null],
                [__('ui.customer_exchange_report'), url('admin/reports/customer-exchange-report'), $is('admin/reports/customer-exchange-report*'), null],
                [__('ui.currency_exchange_report'), url('admin/reports/currency-exchange-report'), $is('admin/reports/currency-exchange-report*'), null],
                [__('ui.transactions_report'), url('admin/reports/transactions'), $is('admin/reports/transactions*'), 'view profit loss reports'],
                [__('ui.profit_loss_report'), url('admin/reports/profit-loss'), $is('admin/reports/profit-loss*'), 'view profit loss reports'],
                [__('ui.currency_report'), url('admin/reports/currency'), $is('admin/reports/currency') , 'view currency reports'],
                [__('aed.title'), route('admin.reports.aed.index'), request()->routeIs('admin.reports.aed.*'), 'view customers reports'],
                [__('ui.customer_reports'), url('admin/reports/customers'), $is('admin/reports/customers*'), 'view customers reports'],
                [__('ui.creditors'), url('admin/reports/creditors'), $is('admin/reports/creditors*'), 'view creditors'],
                [__('ui.debtors'), url('admin/reports/debtors'), $is('admin/reports/debtors*'), 'view debtors'],
            ]],
            ['group' => __('ui.user_management'), 'icon' => 'users-gear', 'id' => 'users', 'can' => null, 'items' => [
                [__('ui.users'), url('admin/users'), $is('admin/users*'), 'view users'],
                [__('ui.roles'), url('admin/roles'), $is('admin/roles*'), 'view roles'],
                [__('ui.permissions'), url('admin/permissions'), $is('admin/permissions*'), 'view permissions'],
            ]],
            ['group' => __('ui.configuration'), 'icon' => 'gear', 'id' => 'configuration', 'can' => null, 'items' => [
                [__('ui.currencies'), url('admin/currencies'), $is('admin/currencies*'), 'view currencies'],
                [__('ui.expense_categories'), url('admin/expense-categories'), $is('admin/expense-categories*'), 'view expense categories'],
                [__('ui.account_categories'), url('admin/account-categories'), $is('admin/account-categories*'), 'view account categories'],
                [__('ui.account_sub_categories'), url('admin/account-sub-categories'), $is('admin/account-sub-categories*'), 'view account sub categories'],
                [__('ui.audit_logs'), url('admin/audit-logs'), $is('admin/audit-logs*'), 'view audit logs'],
                [__('ui.system_settings'), url('admin/settings'), $is('admin/settings*'), 'view settings'],
            ]],
        ];
    }

    $allowed = fn (?string $permission) => $shell === 'platform' || $permission === null || ($user && $user->can($permission));
@endphp
<aside class="app-sidebar" id="appSidebar" aria-label="{{ __('Menu') }}">
    <div class="sidebar-brand">
        <a href="{{ $home }}" class="brand-link">
            @if ($logo)
                <img src="{{ $logo }}" alt="" width="32" height="32" style="height:32px;width:32px;object-fit:cover;border-radius:9px">
            @else
                <x-brand-logo />
            @endif
            <span class="brand-text">{{ $brandName }}</span>
        </a>
    </div>
    <div class="sidebar-scroll" id="sidebarScroll">
        <div class="drawer-profile">
            <span class="avatar avatar-md avatar-soft-primary">{{ initial($user?->name) }}</span>
            <div class="min-w-0"><strong>{{ $user?->name }}</strong><small>{{ $role }}</small></div>
        </div>
        <div class="drawer-quick">
            <button type="button" data-theme-toggle><x-icon name="moon" /><span>{{ __('Theme') }}</span></button>
            <button type="button" data-bs-toggle="modal" data-bs-target="#searchModal"><x-icon name="magnifying-glass" /><span>{{ __('Search') }}</span></button>
            <button type="submit" form="logoutForm"><x-icon name="right-from-bracket" /><span>{{ __('Sign out') }}</span></button>
        </div>
        <ul class="sidebar-menu" id="sidebarMenu">
            @foreach ($menu as $item)
                @if (isset($item['heading']))
                    <li class="menu-heading"><span>{{ $item['heading'] }}</span></li>
                @elseif (isset($item['group']))
                    @php
                        $items = array_values(array_filter($item['items'], fn ($sub) => $allowed($sub[3])));
                        $open = (bool) array_filter($items, fn ($sub) => $sub[2]);
                    @endphp
                    @if ($items && $allowed($item['can']))
                        <li class="menu-item has-sub {{ $open ? 'active open' : '' }}">
                            <a class="menu-link {{ $open ? '' : 'collapsed' }}" href="#menu-{{ $item['id'] }}" role="button" data-bs-toggle="collapse" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="menu-{{ $item['id'] }}">
                                <span class="menu-icon"><x-icon :name="$item['icon']" /></span><span class="menu-text">{{ $item['group'] }}</span><x-icon name="chevron-right" class="menu-arrow ms-auto" />
                            </a>
                            <ul class="menu-sub collapse {{ $open ? 'show' : '' }}" id="menu-{{ $item['id'] }}" data-bs-parent="#sidebarMenu">
                                @foreach ($items as $sub)
                                    <li><a class="menu-sublink {{ $sub[2] ? 'active' : '' }}" href="{{ $sub[1] }}" @if ($sub[2]) aria-current="page" @endif><span class="menu-text">{{ $sub[0] }}</span></a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @elseif ($allowed($item[4]))
                    <li class="menu-item {{ $item[3] ? 'active' : '' }}">
                        <a class="menu-link" href="{{ $item[2] }}" @if ($item[3]) aria-current="page" @endif><span class="menu-icon"><x-icon :name="$item[1]" /></span><span class="menu-text">{{ $item[0] }}</span></a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
