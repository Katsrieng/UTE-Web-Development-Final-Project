@extends(auth()->user()->hasRole('admin', 'staff') ? 'layouts.management' : 'layouts.app')

@section('title', 'My Profile')
@section('page-label', 'My Profile')

@section('content')
<section class="{{ auth()->user()->hasRole('admin', 'staff') ? '' : 'content-section compact' }}">
    <div class="{{ auth()->user()->hasRole('admin', 'staff') ? '' : 'container' }}">
        <div class="page-heading">
            <div>
                <p class="section-kicker">Account settings</p>
                <h1>My Profile</h1>
                <p>Keep your personal details and password up to date.</p>
            </div>
            <x-status-badge :status="$user->is_active ? 'active' : 'disabled'" />
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="avatar-circle" style="width:54px;height:54px;font-size:1rem">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <div><h2 class="h4 mb-1">Profile information</h2><p class="text-muted mb-0">Signed in as {{ ucfirst($user->role) }}</p></div>
                        </div>
                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3"><label for="name" class="form-label">Full name</label><input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="mb-3"><label for="email" class="form-label">Email address</label><input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="mb-4"><label for="phone" class="form-label">Phone</label><input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <button type="submit" class="btn btn-hotel">Save profile</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="h4 mb-2">Change password</h2>
                        <p class="text-muted mb-4">Use a strong password you do not reuse elsewhere.</p>
                        <form method="POST" action="{{ route('profile.password') }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3"><label for="current_password" class="form-label">Current password</label><input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="mb-3"><label for="password" class="form-label">New password</label><input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="mb-4"><label for="password_confirmation" class="form-label">Confirm new password</label><input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
                            <button type="submit" class="btn btn-dark">Update password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
