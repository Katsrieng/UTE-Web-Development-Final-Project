<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Sign In | Utopia Bay</title>
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/hotel.css') }}" rel="stylesheet">
</head>
<body class="hotel-body staff-login-body">
<main class="auth-section">
    <div class="container">
        <div class="row justify-content-center"><div class="col-xl-9">
            <div class="auth-card"><div class="row g-0">
                <div class="col-lg-5 d-none d-lg-block"><div class="auth-card-aside">
                    <img class="auth-brand-logo" src="{{ asset('images/brand/utopia_bay_logo_wb.svg') }}" alt="Utopia Bay" width="68" height="68"><h1 class="h2 mt-4">Utopia Bay Staff Portal</h1>
                    <p class="text-white-50 lh-lg">Access Utopia Bay Resort Management.</p>
                    <p class="small text-white-50">For authorized hotel staff and administrators.</p>
                </div></div>
                <div class="col-lg-7"><div class="auth-card-form">
                    <p class="section-kicker">Utopia Bay Resort Management</p><h2 class="h2 mb-2">Staff Sign In</h2>
                    <p class="text-muted mb-4">Use your hotel staff account to continue.</p>
                    @include('partials.flash-messages')
                    @include('auth._login-form', ['loginAction' => route('staff.login.store'), 'loginButton' => 'Sign in to Management'])
                    <a class="d-block small text-center mt-4" href="{{ route('welcome') }}">← Back to Utopia Bay</a>
                </div></div>
            </div></div>
        </div></div>
    </div>
</main>
<script src="{{ asset('js/hotel.js') }}" defer></script>
</body>
</html>
