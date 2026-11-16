<style>
    /* Keep branding outside the scrolling menu and above its scroll shadow. */
    #layout-menu {
        --sidebar-brand-height: 76px;
    }

    #layout-menu > .app-brand {
        position: relative;
        z-index: 3;
        flex: 0 0 var(--sidebar-brand-height);
        block-size: var(--sidebar-brand-height);
        min-block-size: var(--sidebar-brand-height);
        padding: 10px 12px;
        gap: 8px;
        background-color: inherit;
        box-sizing: border-box;
    }

    #layout-menu .brand-center-wrapper {
        flex: 1 1 0;
        min-inline-size: 0;
    }

    #layout-menu .brand-center-link {
        display: flex;
        align-items: center;
        justify-content: center;
        inline-size: 100%;
        block-size: 50px;
    }

    #layout-menu .brand-center-logo {
        display: block;
        max-inline-size: 100%;
        inline-size: auto;
        block-size: 50px;
        object-fit: contain;
    }

    #layout-menu > .app-brand .layout-menu-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 28px;
        inline-size: 28px;
        block-size: 32px;
        margin: 0 !important;
        padding: 0;
    }

    #layout-menu > .menu-inner-shadow {
        inset-block-start: var(--sidebar-brand-height);
    }

    /* ===== SUBMENU SIZE REDUCTION ===== */
    .menu-sub .menu-link {
        padding: 6px 1px !important;
        font-size: 16px !important;
    }

    .menu-sub .menu-link div {
        font-size: 16px !important;
        line-height: 1.2;
    }

    .menu-sub .menu-icon {
        font-size: 16px !important;
        margin-inline: 0.5rem !important;
    }

    /* Optional: tighter spacing */
    .menu-sub .menu-item {
        margin: 2px 0 !important;
    }
</style>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-white py-0">
    <div class="app-brand demo d-flex align-items-center">

        <div class="brand-center-wrapper">
            <a href="{{ url('/') }}" class="brand-center-link">
                <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}" height="50" class="brand-center-logo">
            </a>
        </div>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="menu-toggle-icon d-xl-inline-block align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-3">

        @can('view dashboard')
            <li class="menu-item {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                <a href="{{ url('admin/dashboard') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-house-door"></i>
                    <div>{{ __('ui.dashboard') }}</div>
                </a>
            </li>
        @endcan

        @can('view customers')
            <li class="menu-item {{ request()->is('admin/accounts*') ? 'active' : '' }}">
                <a href="{{ url('admin/accounts') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-people-fill"></i>
                    <div>{{ __('ui.accounts') }}</div>
                </a>
            </li>
        @endcan

        @can('view safe')
            <li class="menu-item {{ request()->is('admin/safe*') ? 'active' : '' }}">
                <a href="{{ url('admin/safe') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-safe"></i>
                    <div>{{ __('ui.safe') }}</div>
                </a>
            </li>
        @endcan

        @can('view transactions')
            <li class="menu-item {{ request()->is('admin/transactions*') ? 'active' : '' }}">
                <a href="{{ url('admin/transactions') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-arrow-left-right"></i>
                    <div>{{ __('ui.transactions') }}</div>
                </a>
            </li>
        @endcan

        @can('view journal')
            <li class="menu-item {{ request()->is('admin/journal*') ? 'active' : '' }}">
                <a href="{{ url('admin/journal') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-journal-text"></i>
                    <div>{{ __('ui.journal') }}</div>
                </a>
            </li>
        @endcan

        @can('view exchanges')
            <li class="menu-item {{ request()->is('admin/exchanges*') || request()->is('admin/exchange-rates*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-currency-exchange"></i>
                    <div>{{ __('ui.exchanges') }}</div>
                </a>

                <ul class="menu-sub">

                    {{-- CREATE EXCHANGE --}}
                    <li class="menu-item {{ request()->is('admin/exchanges/create') ? 'active' : '' }}">
                        <a href="{{ route('admin.exchanges.create') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-plus-circle"></i>
                            <div>{{ __('ui.new_exchange') }}</div>
                        </a>
                    </li>

                    {{-- EXCHANGE LIST --}}
                    <li class="menu-item {{ request()->is('admin/exchanges/index') ? 'active' : '' }}">
                        <a href="{{ route('admin.exchanges.index') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-list"></i>
                            <div>{{ __('ui.exchange_ledger') }}</div>
                        </a>
                    </li>

                    @can('view exchange rates')
                        {{-- EXCHANGE RATES --}}
                        <li class="menu-item {{ request()->is('admin/exchange-rates/create') ? 'active' : '' }}">
                            <a href="{{ route('admin.exchange-rates.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-graph-up-arrow"></i>
                                <div>{{ __('ui.exchange_rates') }}</div>
                            </a>
                        </li>
                    @endcan

                </ul>
            </li>
        @endcan

        @can('view remittances')
            {{-- Remittances Menu --}}
            @php
                $rmActive = request()->is('admin/remittances*');
            @endphp

            <li class="menu-item {{ $rmActive ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-receipt"></i>
                    <div>{{ __('ui.remittances') }}</div>
                </a>

                <ul class="menu-sub">
                    <li class="menu-item {{ request()->is('admin/remittances/create-new') ? 'active' : '' }}">
                        <a href="{{ route('admin.remittances.createNew') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-plus-circle"></i>
                            <div>{{ __('ui.new_remittance') }}</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->is('admin/remittances/outgoing') ? 'active' : '' }}">
                        <a href="{{ route('admin.remittances.outgoing') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-arrow-left"></i>
                            <div>{{ __('ui.outgoing') }}</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->is('admin/remittances/incoming-paid') ? 'active' : '' }}">
                        <a href="{{ route('admin.remittances.incomingPaid') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-check-circle"></i>
                            <div>{{ __('ui.incoming') }}</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->is('admin/remittances/pending-incoming') ? 'active' : '' }}">
                        <a href="{{ route('admin.remittances.pendingIncoming') }}" class="menu-link">
                            <i class="menu-icon icon-base bi bi-clock"></i>
                            <div>{{ __('ui.pending_remittances') }}</div>
                        </a>
                    </li>
                </ul>
            </li>
        @endcan
        @can('view expenses')
            <li class="menu-item {{ request()->is('admin/expenses*') ? 'active' : '' }}">
                <a href="{{ url('admin/expenses') }}" class="menu-link">
                    <i class="menu-icon icon-base bi bi-wallet2"></i>
                    <div>{{ __('ui.expenses') }}</div>
                </a>
            </li>
        @endcan

        @can('view reports')
            <li class="menu-item {{ request()->is('admin/reports*') ? 'active open' : '' }}">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-file-bar-graph"></i>
                    <div>{{ __('ui.reports') }}</div>
                </a>

                <ul class="menu-sub">
                    <li class="menu-item {{ request()->is('admin/reports/balance') ? 'active' : '' }}">
                        <a class="menu-link" href="{{ url('admin/reports/balance') }}">
                            <i class="menu-icon icon-base bi bi-currency-exchange"></i>
                            <span>{{ __('ui.balance_report') }}</span>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->is('admin/reports/customer-exchange-report') ? 'active' : '' }}">
                        <a class="menu-link" href="{{ url('admin/reports/customer-exchange-report') }}">
                            <i class="menu-icon icon-base bi bi-people"></i>
                            <span>{{ __('ui.customer_exchange_report') }}</span>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->is('admin/reports/currency-exchange-report') ? 'active' : '' }}">
                        <a class="menu-link" href="{{ url('admin/reports/currency-exchange-report') }}">
                            <i class="menu-icon icon-base bi bi-cash-coin"></i>
                            <span>{{ __('ui.currency_exchange_report') }}</span>
                        </a>
                    </li>

                    @can('view profit loss reports')
                        <li class="menu-item {{ request()->is('admin/reports/transactions') ? 'active' : '' }}">
                            <a href="{{ url('admin/reports/transactions') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-graph-up-arrow"></i>
                                <div>{{ __('ui.transactions_report') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view profit loss reports')
                        <li class="menu-item {{ request()->is('admin/reports/profit-loss') ? 'active' : '' }}">
                            <a href="{{ url('admin/reports/profit-loss') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-graph-up-arrow"></i>
                                <div>{{ __('ui.profit_loss_report') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view currency reports')
                        <li class="menu-item {{ request()->is('admin/reports/currency') ? 'active' : '' }}">
                            <a href="{{ url('admin/reports/currency') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-currency-dollar"></i>
                                <div>{{ __('ui.currency_report') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view customers reports')
                        <li class="menu-item {{ request()->is('admin/reports/aed*') ? 'active' : '' }}">
                            <a href="{{ route('admin.reports.aed.index') }}" class="menu-link">
                                <i class="menu-icon bi bi-currency-exchange"></i>
                                <div>{{ __('aed.title') }}</div>
                            </a>
                        </li>

                        <li class="menu-item {{ request()->is('admin/reports/customers') ? 'active' : '' }}">
                            <a href="{{ url('admin/reports/customers') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-person-vcard"></i>
                                <div>{{ __('ui.customer_reports') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view creditors')
                        <li class="menu-item {{ request()->is('admin/reports/creditors') ? 'active' : '' }}">
                            <a href="{{ url('admin/reports/creditors') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-arrow-up-right-circle"></i>
                                <div>{{ __('ui.creditors') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view debtors')
                        <li class="menu-item {{ request()->is('admin/reports/debtors') ? 'active' : '' }}">
                            <a href="{{ url('admin/reports/debtors') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-arrow-down-right-circle"></i>
                                <div>{{ __('ui.debtors') }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcan

        @if (auth()->user()->can('view users') || auth()->user()->can('view roles') || auth()->user()->can('view permissions'))
            <li class="menu-item {{ request()->is('admin/users*') || request()->is('admin/roles*') || request()->is('admin/permissions*') ? 'active open' : '' }}">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-people"></i>
                    <div>{{ __('ui.user_management') }}</div>
                </a>

                <ul class="menu-sub">

                    @can('view users')
                        <li class="menu-item {{ request()->is('admin/users*') ? 'active' : '' }}">
                            <a href="{{ url('admin/users') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-person-badge"></i>
                                <div>{{ __('ui.users') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view roles')
                        <li class="menu-item {{ request()->is('admin/roles*') ? 'active' : '' }}">
                            <a href="{{ url('admin/roles') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-shield-lock"></i>
                                <div>{{ __('ui.roles') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view permissions')
                        <li class="menu-item {{ request()->is('admin/permissions*') ? 'active' : '' }}">
                            <a href="{{ url('admin/permissions') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-ui-checks-grid"></i>
                                <div>{{ __('ui.permissions') }}</div>
                            </a>
                        </li>
                    @endcan

                </ul>
            </li>
        @endif

        @if (auth()->user()->can('view settings') ||
                auth()->user()->can('view expense categories') ||
                auth()->user()->can('view currencies') ||
                auth()->user()->can('view account categories') ||
                auth()->user()->can('view account sub categories') ||
                auth()->user()->can('view audit logs'))
            <li
                class="menu-item {{ request()->is('admin/settings*') ||
                request()->is('admin/expense-categories*') ||
                request()->is('admin/currencies*') ||
                request()->is('admin/account-categories*') ||
                request()->is('admin/account-sub-categories*') ||
                request()->is('admin/audit-logs*')
                    ? 'active open'
                    : '' }}">

                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base bi bi-sliders"></i>
                    <div>{{ __('ui.configuration') }}</div>
                </a>

                <ul class="menu-sub">

                    @can('view currencies')
                        <li class="menu-item {{ request()->is('admin/currencies*') ? 'active' : '' }}">
                            <a href="{{ url('admin/currencies') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-currency-dollar"></i>
                                <div>{{ __('ui.currencies') }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('view expense categories')
                        <li class="menu-item {{ request()->is('admin/expense-categories*') ? 'active' : '' }}">
                            <a href="{{ url('admin/expense-categories') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-grid-3x3"></i>
                                <div>{{ __('ui.expense_categories') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view account categories')
                        <li class="menu-item {{ request()->is('admin/account-categories*') ? 'active' : '' }}">
                            <a href="{{ url('admin/account-categories') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-menu-button-wide"></i>
                                <div>{{ __('ui.account_categories') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view account sub categories')
                        <li class="menu-item {{ request()->is('admin/account-sub-categories*') ? 'active' : '' }}">
                            <a href="{{ url('admin/account-sub-categories') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-menu-button-wide"></i>
                                <div>{{ __('ui.account_sub_categories') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view audit logs')
                        <li class="menu-item {{ request()->is('admin/audit-logs*') ? 'active' : '' }}">
                            <a href="{{ url('admin/audit-logs') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-list-check"></i>
                                <div>{{ __('ui.audit_logs') }}</div>
                            </a>
                        </li>
                    @endcan

                    @can('view settings')
                        <li class="menu-item {{ request()->is('admin/settings*') ? 'active' : '' }}">
                            <a href="{{ url('admin/settings') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-gear"></i>
                                <div>{{ __('ui.system_settings') }}</div>
                            </a>
                        </li>
                    @endcan

                </ul>
            </li>
        @endif

    </ul>

</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary rounded-1 p-2">
        <i class="ri ri-menu-line icon-base"></i>
        <i class="ri ri-arrow-right-s-line icon-base"></i>
    </a>
</div>
