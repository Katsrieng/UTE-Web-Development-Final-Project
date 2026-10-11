<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utopia Bay</title>
    <link rel="icon" type="image/svg+xml" sizes="any" href="{{ asset('images/brand/utopia_bay_logo_color.svg') }}">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/hotel.css') }}" rel="stylesheet">
</head>
<body class="hotel-body staff-login-body">
<main class="auth-section">
    <div class="container">
        <div class="row justify-content-center"><div class="col-md-8 col-lg-6">
            <div class="auth-card"><div class="row g-0">
                <div class="col-12"><div class="auth-card-form">
                    <img class="auth-brand-logo mb-3" src="{{ asset('images/brand/utopia_bay_logo_color.svg') }}" alt="Utopia Bay" width="68" height="68">
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
