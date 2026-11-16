<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="layout-navbar-fixed layout-menu-fixed layout-compact {{ $isRtl ? 'layout-rtl' : '' }}"
    data-skin="default" data-assets-path="{{ asset('assets') }}" data-template="vertical-menu-template-starter" data-bs-theme="light">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
        <title>@yield('title')</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="shortcut icon" href="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}" type="image/x-icon">

        <!-- SEO -->
        <meta name="robots" content="index, follow" />

        <meta name="description"
            content="Fida Expense Management is a modern, simple, and powerful system for tracking expenses, budgets, receipts, and financial reports — built for businesses in Afghanistan." />

        <meta name="keywords"
            content="expense management, expense tracker, business expenses Afghanistan, financial tracking software, Fida Technologies, budget management, accounting tools" />

        <meta name="author" content="Fida Technologies" />

        <link rel="canonical" href="https://fida.af" />

        <!-- Open Graph -->
        <meta property="og:title" content="@yield('title') — Expense Management" />
        <meta property="og:description"
            content="Manage and control your company expenses with Fida Expense Management — smart, fast, and easy-to-use software tailored for Afghan businesses." />
        <meta property="og:image" content="https://fida.af/images/expense-management.jpg" />
        <meta property="og:url" content="https://fida.af" />
        <meta property="og:type" content="website" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="@yield('title') — Expense Management" />
        <meta name="twitter:description" content="Track expenses, control budgets, and generate detailed financial reports with Fida Expense Management." />
        <meta name="twitter:image" content="https://fida.af/images/expense-management.jpg" />

        <!-- Content versions keep cached menu styles and scripts consistent across pages. -->
        <!-- Vendor Styles -->
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/offline/3b261c28df69447e.css') }}" />
        <link href="{{ asset('assets/offline/d55d61f8152b62c7.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}?v={{ substr(hash_file('sha256', public_path('assets/vendor/css/core.css')), 0, 12) }}" />
        <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}?v={{ substr(hash_file('sha256', public_path('assets/css/demo.css')), 0, 12) }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
        <link href="{{ asset('assets/offline/6a071a1df7b78afb.css') }}" rel="stylesheet">

        <!-- Custom Styles -->
        <style>
            @font-face {
                font-family: 'Vazirmatn';
                src: url("{{ asset('fonts/webfonts/Vazirmatn[wght].woff2') }}") format('woff2');
                font-weight: 100 900;
                font-style: normal;
                font-display: swap;
            }
            .select2-container--open {
                z-index: 999999 !important;
            }

            .select2-container .select2-selection--single {
                height: 47px !important;
                padding: 6px 12px !important;
                border: 1px solid #ced4da !important;
                border-radius: 0.375rem !important;
                display: flex !important;
                align-items: center !important;
            }

            .bg-success {
                box-shadow: 0 0 4px #28a745;
            }

            .bg-warning {
                box-shadow: 0 0 4px #ffc107;
            }

            .bg-secondary {
                opacity: 0.6;
            }
        </style>

        <!-- RTL Support -->
        @if ($isRtl)
            <style>
                body {
                    direction: rtl;
                    text-align: right;
                }

                /* Menu */
                .menu-inner,
                .layout-rtl .menu-inner {
                    text-align: right;
                }

                /* Navbar */
                .navbar-nav-right,
                .layout-rtl .navbar-nav-right {
                    margin-left: auto;
                    margin-right: 0;
                }

                /* Dropdown */
                .dropdown-menu {
                    left: auto;
                    right: 0;
                    text-align: right;
                }

                /* Inputs */
                .form-control {
                    text-align: right;
                }

                /* Fix margin helpers */
                .me-2 {
                    margin-left: .5rem !important;
                    margin-right: 0 !important;
                }

                table {
                    direction: rtl;
                }

                table thead th,
                table tbody td {
                    text-align: right !important;
                }

                /* RTL DataTables toolbar: search at the right/start, length at the left/end. */
                .dataTables_wrapper > .row:first-child > div:first-child {
                    order: 2;
                }

                .dataTables_wrapper > .row:first-child > div:last-child {
                    order: 1;
                }

                .dataTables_wrapper .dataTables_filter,
                .dt-container .dt-search {
                    text-align: right !important;
                }

                .dataTables_wrapper .dataTables_length,
                .dt-container .dt-length {
                    text-align: left !important;
                }

                .dataTables_wrapper .dataTables_filter input {
                    margin-right: .5em;
                    margin-left: 0;
                }

                .dt-container .dt-layout-row:first-child .dt-layout-start {
                    order: 2;
                }

                .dt-container .dt-layout-row:first-child .dt-layout-end {
                    order: 1;
                }

                .dataTables_wrapper .dataTables_paginate {
                    text-align: left;
                }
            </style>
        @endif

        <!-- Arabic Fonts -->
        @if (app()->getLocale() === 'fa' || app()->getLocale() === 'ps')
            
            <style>
                body,
                html,
                * {
                    font-family: 'Vazirmatn', sans-serif !important;
                }

                .menu .menu-inner .menu-item>a,
                .menu .menu-inner .menu-header {
                    white-space: nowrap;
                    font-weight: bolder !important;
                    font-size: 17px !important;
                }
            </style>
        @endif

        <style>
            /* Premium Sneat High-Density Overrides */
            :root {
                --fin-bg: #f8fafc;
                --fin-border: #edf2f7;
                --fin-text-main: #1a202c;
                --fin-text-muted: #718096;
                --fin-accent: #3b82f6;
                /* Soft blue like the reference */
            }

            body {
                background-color: var(--fin-bg) !important;
                font-family: 'Inter', -apple-system, sans-serif !important;
                letter-spacing: -0.01em;
            }

            /* --- High-Density Luxury Cards --- */
            .card-modern {
                border: 1px solid var(--fin-border) !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02) !important;
                border-radius: 8px !important;
                background: #ffffff;
            }

            /* --- Low Spacing Premium Table (Tailwind Inspired) --- */
            .table-premium {
                width: 100%;
                border-collapse: collapse;
            }

            .table-premium thead th {
                background-color: #ffffff !important;
                color: var(--fin-text-muted) !important;
                text-transform: capitalize !important;
                font-size: 0.75rem !important;
                font-weight: 500 !important;
                padding: 8px 12px !important;
                /* Lesser spacing */
                border-bottom: 2px solid var(--fin-border) !important;
                border-top: none !important;
            }

            .table-premium tbody tr {
                border-bottom: 1px solid var(--fin-border);
                transition: background 0.1s ease;
            }

            /* Hover effect from the image */
            .table-premium tbody tr:hover {
                background-color: #f7fafc !important;
            }

            /* Selected/Checked row effect from the image */
            .table-premium tbody tr.selected {
                background-color: #ebf4ff !important;
            }

            .table-premium td {
                padding: 6px 12px !important;
                /* Tight vertical spacing */
                font-size: 0.825rem !important;
                color: var(--fin-text-main);
                vertical-align: middle;
            }

            /* --- Financial Status Badges --- */
            .badge-fin {
                font-size: 0.65rem;
                font-weight: 700;
                padding: 2px 6px;
                border-radius: 4px;
                text-transform: uppercase;
            }

            .bg-label-ok {
                background: #ecfdf5;
                color: #10b981;
            }

            /* Green */
            .bg-label-loss {
                background: #fef2f2;
                color: #ef4444;
            }

            /* Red */
            .bg-label-warn {
                background: #fffbeb;
                color: #f59e0b;
            }

            /* Orange */
            .bg-label-stopped {
                background: #f3f4f6;
                color: #6b7280;
            }

            /* Grey */
            /* --- Financial Mono Numbers --- */
            .mono {
                font-family: 'JetBrains Mono', monospace !important;
                letter-spacing: -0.02em;
            }
        </style>
        @yield('css')

        <!-- Helpers -->
        <script src="{{ asset('assets/vendor/js/helpers.js') }}?v={{ substr(hash_file('sha256', public_path('assets/vendor/js/helpers.js')), 0, 12) }}"></script>
        <script src="{{ asset('assets/js/config.js') }}?v={{ substr(hash_file('sha256', public_path('assets/js/config.js')), 0, 12) }}"></script>
    </head>

    <body>
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">

                <!-- Sidebar -->
                @include('layouts.admin.menu')

                <div class="layout-page">
                    <!-- Navbar -->
                    @include('layouts.admin.navbar')

                    <!-- Page Content -->
                    <div class="content-wrapper">
                        <div class="container-xxl flex-grow-1 container-p-y">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Core JS -->
        <script src="{{ asset('assets/offline/a3133eb07bfae987.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
        <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
        <script src="{{ asset('assets/vendor/js/menu.js') }}?v={{ substr(hash_file('sha256', public_path('assets/vendor/js/menu.js')), 0, 12) }}"></script>
        <script src="{{ asset('assets/js/main.js') }}?v={{ substr(hash_file('sha256', public_path('assets/js/main.js')), 0, 12) }}"></script>
        <script src="{{ asset('assets/offline/d22ecaf30e58379b.js') }}"></script>
        <script src="{{ asset('assets/js/numeric-inputs.js') }}"></script>
        @yield('js')

        <!-- SweetAlert Toasts -->
        <script>
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: '{{ __('ui.blade_success') }}',
                    text: '{{ session('success') }}',
                    showConfirmButton: false,
                    timer: 3000
                });
            @endif
            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: '{{ __('ui.blade_error') }}',
                    text: '{{ session('error') }}',
                    showConfirmButton: false,
                    timer: 3000
                });
            @endif
        </script>
    </body>

</html>
