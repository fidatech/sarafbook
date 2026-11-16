<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-xl-0 d-xl-none me-4">
        <a class="nav-item nav-link me-xl-6 px-0" href="javascript:void(0)">
            <i class="icon-base ri ri-menu-line icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

        <ul class="navbar-nav align-items-center ms-auto flex-row">

            <!-- Language -->
            <li class="nav-item dropdown">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" href="#" data-bs-toggle="dropdown" role="button"
                    aria-expanded="false">
                    <img src="{{ asset('assets/img/flags/' . app()->getLocale() . '.svg') }}" alt="{{ app()->getLocale() }}" class="w-px-20 h-auto" />
                    <span class="d-none d-sm-inline fw-medium ms-2"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end animate__animated animate__fadeIn">
                    <li>
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ route('lang.switch', 'en') }}">
                            <img src="{{ asset('assets/img/flags/en.svg') }}" alt="EN" class="w-px-20 me-2" />
                            <span>{{ __('ui.lang_en') }}</span>
                            @if (app()->getLocale() === 'en')
                                <i class="bi bi-check2 text-success ms-auto"></i>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'fa' ? 'active' : '' }}" href="{{ route('lang.switch', 'fa') }}">
                            <img src="{{ asset('assets/img/flags/fa.svg') }}" alt="FA" class="w-px-20 me-2" />
                            <span>فارسی</span>
                            @if (app()->getLocale() === 'fa')
                                <i class="bi bi-check2 text-success ms-auto"></i>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'ps' ? 'active' : '' }}" href="{{ route('lang.switch', 'ps') }}">
                            <img src="{{ asset('assets/img/flags/ps.svg') }}" alt="PS" class="w-px-20 me-2" />
                            <span>پښتو</span>
                            @if (app()->getLocale() === 'ps')
                                <i class="bi bi-check2 text-success ms-auto"></i>
                            @endif
                        </a>
                    </li>
                </ul>
            </li>

            <!--/ Language -->

            <!-- Quick links  -->
            <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                    data-bs-auto-close="outside" aria-expanded="false">
                    <i class='ri-star-smile-line ri-22px'></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end py-0">
                    <div class="dropdown-menu-header border-bottom py-50">
                        <div class="dropdown-header d-flex align-items-center py-2">
                            <h6 class="mb-0 me-auto">{{ __('ui.blade_shortcuts') }}</h6>
                            <a href="javascript:void(0)" class="btn btn-text-secondary rounded-pill btn-icon dropdown-shortcuts-add" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="{{ __('ui.blade_add_shortcuts') }}"><i class="ri-layout-grid-line ri-24px text-heading"></i></a>
                        </div>
                    </div>
                    <div class="dropdown-shortcuts-list scrollable-container">
                        <div class="row row-bordered g-0 overflow-visible">
                            @can('view customers')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-people-fill text-primary"></i>
                                    </span>
                                    <a href="{{ route('admin.accounts.index') }}" class="stretched-link">{{ __('ui.customers') }}</a>
                                </div>
                            @endcan

                        </div>

                        <div class="row row-bordered g-0 overflow-visible">
                            @can('view transactions')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-cash-stack text-warning"></i>
                                    </span>
                                    <a href="{{ route('admin.transactions.index') }}" class="stretched-link">{{ __('ui.transactions') }}</a>
                                </div>
                            @endcan

                            @can('view journal')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-book text-info"></i>
                                    </span>
                                    <a href="{{ route('admin.journal.index') }}" class="stretched-link">{{ __('ui.journal') }}</a>
                                </div>
                            @endcan
                        </div>

                    </div>

                </div>
            </li>
            <!-- Quick links -->

            <!-- Notification -->
            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-xl-1 me-4">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                    data-bs-auto-close="outside" aria-expanded="false">
                    <i class="ri-notification-2-line ri-22px"></i>
                    <span class="position-absolute start-50 translate-middle-y badge badge-dot bg-danger top-0 mt-2 border"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end py-0">
                    <li class="dropdown-menu-header border-bottom">
                        <div class="dropdown-header d-flex align-items-center py-3">
                            <h6 class="mb-0 me-auto">{{ __('ui.blade_notification') }}</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge rounded-pill bg-label-primary me-2">{{ __('ui.blade_8_new') }}</span>
                                <a href="javascript:void(0)" class="btn btn-text-secondary rounded-pill btn-icon dropdown-notifications-all" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="{{ __('ui.blade_mark_all_as_read') }}"><i class="ri-mail-open-line ri-20px text-body"></i></a>
                            </div>
                        </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container">
                        <ul class="list-group list-group-flush">
                            {{-- <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_congratulation_lettie') }}</h6>
                                        <small class="d-block text-body mb-1">{{ __('ui.blade_won_the_monthly_best_seller_gold_badge') }}</small>
                                        <small class="text-muted">{{ __('ui.blade_1h_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li> --}}
                            {{-- <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-danger">{{ __('ui.blade_cf') }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_charles_franklin') }}</h6>
                                        <small class="d-block text-body mb-1">{{ __('ui.blade_accepted_your_connection') }}</small>
                                        <small class="text-muted">{{ __('ui.blade_12hr_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/2.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_new_message') }}</h6>
                                        <small class="d-block text-body mb-1">{{ __('ui.blade_you_have_new_message_from_natalie') }}</small>
                                        <small class="text-muted">{{ __('ui.blade_1h_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-success"><i class="ri-shopping-cart-2-line"></i></span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_whoo_you_have_new_order') }} </h6>
                                        <small class="d-block text-body mb-1">ACME Inc. made new order $1,154</small>
                                        <small class="text-muted">{{ __('ui.blade_1_day_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/9.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_application_has_been_approved') }} </h6>
                                        <small class="d-block text-body mb-1">{{ __('ui.blade_your_abc_project_application_has_been_approved') }}</small>
                                        <small class="text-muted">{{ __('ui.blade_2_days_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-success"><i class="ri-pie-chart-2-line"></i></span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_monthly_report_is_generated') }}</h6>
                                        <small class="d-block text-body mb-1">{{ __('ui.blade_july_monthly_financial_report_is_generated') }} </small>
                                        <small class="text-muted">{{ __('ui.blade_3_days_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/5.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_send_connection_request') }}</h6>
                                        <small class="d-block text-body mb-1">{{ __('ui.blade_peter_sent_you_connection_request') }}</small>
                                        <small class="text-muted">{{ __('ui.blade_4_days_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/6.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_new_message_from_jane') }}</h6>
                                        <small class="d-block text-body mb-1">{{ __('ui.blade_your_have_new_message_from_jane') }}</small>
                                        <small class="text-muted">{{ __('ui.blade_5_days_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-warning"><i class="ri-error-warning-line"></i></span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">{{ __('ui.blade_cpu_is_running_high') }}</h6>
                                        <small class="d-block text-body mb-1">{{ __('ui.blade_cpu_utilization_percent_is_currently_at_88_63') }}</small>
                                        <small class="text-muted">{{ __('ui.blade_5_days_ago') }}</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li> --}}
                        </ul>
                    </li>
                    <li class="border-top">
                        <div class="d-grid p-4">
                            <a class="btn btn-primary btn-sm d-flex" href="javascript:void(0);">
                                <small class="align-middle">{{ __('ui.blade_view_all_notifications') }}</small>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ Notification -->

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 rounded-circle h-auto">
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
                    <li>
                        <a class="dropdown-item pb-3" href="{{ Route::has('profile.show') ? route('profile.show') : url('pages/profile-user') }}">
                            <div class="d-flex align-items-center">
                                <div class="me-2 flex-shrink-0">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="small mb-0">
                                        @if (Auth::check())
                                            {{ Auth::user()->name }}
                                        @else
                                            Admin
                                        @endif
                                    </h6>
                                    <small class="text-muted">{{ Auth::user()->account_type }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ Route::has('profile.show') ? route('profile.show') : url('pages/profile-user') }}">
                            <i class="ri-user-3-line ri-22px me-2"></i>
                            <span class="align-middle">{{ __('ui.blade_my_profile') }}</span>
                        </a>
                    </li>

                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    @if (Auth::check())
                        <li>
                            <div class="d-grid px-4 pb-1 pt-2">
                                <a class="btn btn-danger d-flex" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <small class="align-middle">{{ __('ui.blade_logout') }}</small>
                                    <i class="ri-logout-box-r-line ri-16px ms-2"></i>
                                </a>
                            </div>
                        </li>
                        <form method="POST" id="logout-form" action="{{ route('logout') }}">
                            @csrf
                        </form>
                    @else
                        <li>
                            <div class="d-grid px-4 pb-1 pt-2">
                                <a class="btn btn-danger d-flex" href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}">
                                    <small class="align-middle">{{ __('ui.blade_login') }}</small>
                                    <i class="ri-logout-box-r-line ri-16px ms-2"></i>
                                </a>
                            </div>
                        </li>
                    @endif
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
