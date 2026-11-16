<!doctype html>
<html lang="en" class="layout-wide customizer-hide" dir="ltr" data-skin="default" data-assets-path="{{ asset('assets') }}/" data-template="vertical-menu-template" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex" />

    <title>{{ __('ui.blade_sarafi_management_system_login_page') }}</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link
        href="{{ asset('assets/offline/ee7ba94afea4ad68.css') }}"
        rel="stylesheet" />

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/template-customizer.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
</head>

<body style="background-image: url({{ asset('images/bg.webp') }}); background-size: cover; background-repeat: no-repeat; height: 100vh; width: 100vw; background-color: rgba(0,0,0,0.5);">
    <div class="position-relative">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner mx-4 py-6">
                <!-- Login -->
                <div class="card p-sm-7 p-2">
                    <div class="app-brand justify-content-center mt-5">
                        <img src="{{ asset('images/' . $setting->logo) }}"
                             alt="{{ $setting->company_name }}" height="100">
                    </div>
                    <div class="card-body mt-1">
                        <h4 class="mb-1 text-center">!خوش آمدید</h4>
                        <p class="mb-5">{{ __('ui.blade_please_sign_in_to_your_account') }}</p>

                        <form id="formAuthentication" class="mb-5" action="{{ route('login') }}" method="POST">
                            @csrf
                            <div class="form-floating form-floating-outline form-control-validation mb-5">
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                       id="username" name="username" value="{{ old('username') }}"
                                       placeholder="{{ __('ui.blade_enter_your_username') }}" minlength="4" required autofocus />
                                <label for="username">{{ __('ui.username') }}</label>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-5">
                                <div class="form-password-toggle form-control-validation">
                                    <div class="input-group input-group-merge">
                                        <div class="form-floating form-floating-outline">
                                            <input type="password" id="password"
                                                   class="form-control @error('password') is-invalid @enderror"
                                                   name="password" placeholder="************"
                                                   aria-describedby="password" />
                                            <label for="password">{{ __('ui.password') }}</label>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <span class="input-group-text cursor-pointer">
                                            <i class="icon-base ri ri-eye-off-line icon-20px"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-5">
                                <button class="btn btn-primary d-grid w-100" type="submit">{{ __('ui.blade_login') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /Login -->

                <img src="{{ asset('assets/img/illustrations/tree-3.png') }}" alt="auth-tree"
                     class="authentication-image-object-left d-none d-lg-block" />
                <img src="{{ asset('assets/img/illustrations/auth-basic-mask-light.png') }}"
                     class="authentication-image d-none d-lg-block scaleX-n1-rtl" height="172" alt="triangle-bg"
                     data-app-light-img="illustrations/auth-basic-mask-light.png"
                     data-app-dark-img="illustrations/auth-basic-mask-dark.png" />
                <img src="{{ asset('assets/img/illustrations/tree.png') }}" alt="auth-tree"
                     class="authentication-image-object-right d-none d-lg-block" />
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@algolia/autocomplete-js.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>

    <!-- Vendors JS -->
    <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/js/pages-auth.js') }}"></script>
</body>
</html>
