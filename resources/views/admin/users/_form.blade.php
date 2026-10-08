{{-- Shared by create.blade.php and edit.blade.php. Needs a $user variable. --}}
@php
    $isEdit = $user->exists;
    $isSelf = $isEdit && $user->is(auth()->user());
@endphp

<div class="mb-3">
    <label for="name" class="form-label">Full name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
           class="form-control @error('email') is-invalid @enderror" required>
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Phone</label>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
           class="form-control @error('phone') is-invalid @enderror">
    @error('phone')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="role" class="form-label">Role</label>

    @if($isSelf)
        {{-- You can't change your own role; send the current value along so validation passes. --}}
        <input type="hidden" name="role" value="{{ $user->role }}">
        <input type="text" class="form-control" value="{{ $user->roleLabel() }}" disabled>
        <div class="form-text">You cannot change your own role.</div>
    @else
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_STAFF] as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ $role === 'staff' ? 'Staff / Front Desk' : ucfirst($role) }}</option>
            @endforeach
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    @endif
</div>

<div class="mb-3">
    <label for="password" class="form-label">
        Password
        @if($isEdit)<span class="text-muted">(leave blank to keep current)</span>@endif
    </label>
    <input type="password" id="password" name="password"
           class="form-control @error('password') is-invalid @enderror"
           @if(! $isEdit) required @endif autocomplete="new-password">
    <div class="form-text">At least 8 characters, with letters and numbers.</div>
    @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="password_confirmation" class="form-label">Confirm password</label>
    <input type="password" id="password_confirmation" name="password_confirmation"
           class="form-control" autocomplete="new-password">
</div>

<div class="form-check mb-4">
    {{-- Hidden "0" makes an unchecked box still send a value --}}
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
           @checked((bool) old('is_active', $user->is_active))
           @disabled($isSelf)>
    <label class="form-check-label" for="is_active">Account is active (can log in)</label>
</div>
