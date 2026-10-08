@extends('layouts.management')
@section('title', 'Payment Settings')
@section('page-label', 'Payments')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Hotel payment information</p><h1>Payment Settings</h1><p>Manage the account and QR code shown at customer checkout.</p></div></div>
<form action="{{ route('payment-settings.update') }}" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm">
    @csrf @method('PUT')
    <div class="card-body p-4">
        @if($errors->any())<div class="validation-summary mb-3" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="account_name">Payment Account Name</label>
                    <input id="account_name" name="account_name" class="form-control" value="{{ old('account_name', $settings->account_name) }}" maxlength="191" aria-describedby="account-name-help" required>
                    <div id="account-name-help" class="form-text">Customers see this name during ABA/KHQR checkout.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="account_label">Account or Reference Details</label>
                    <input id="account_label" name="account_label" class="form-control" value="{{ old('account_label', $settings->account_label) }}" maxlength="191" aria-describedby="account-details-help">
                    <div id="account-details-help" class="form-text">Optional account or transfer reference text shown at checkout.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="khqr_image">Payment QR Code</label>
                    <input id="khqr_image" name="khqr_image" type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp" aria-describedby="qr-upload-help">
                    <div id="qr-upload-help" class="form-text">JPG, PNG or WebP · Max 2 MB. Leave blank to keep the current QR.</div>
                </div>
                <div class="border-top pt-3">
                    <input type="hidden" name="aba_khqr_enabled" value="0">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" id="aba_khqr_enabled" name="aba_khqr_enabled" type="checkbox" value="1" aria-describedby="khqr-toggle-help" @checked(old('aba_khqr_enabled', $settings->aba_khqr_enabled))>
                        <label class="form-check-label fw-semibold" for="aba_khqr_enabled">ABA / KHQR Payments</label>
                    </div>
                    <p id="khqr-toggle-help" class="small text-muted mb-1">Allow customers to select ABA/KHQR during checkout.</p>
                    <p class="small text-muted mb-0">Uses your custom QR, or the default demo QR when none is uploaded.</p>
                </div>
            </div>
            <div class="col-lg-5">
                <section class="border rounded-3 p-3 p-sm-4 bg-light text-center" aria-labelledby="qr-preview-heading">
                    <h2 id="qr-preview-heading" class="h6 text-start mb-3"><i class="bi bi-qr-code me-2" aria-hidden="true"></i>Current Payment QR</h2>
                    @if($settings->qrUrl())
                        <img src="{{ $settings->qrUrl() }}" alt="Payment QR code for {{ $settings->account_name }}" class="img-fluid rounded border bg-white p-2" style="width:100%;max-width:210px;max-height:230px;object-fit:contain">
                        <p class="small text-muted mt-3 mb-1">{{ $settings->hasCustomQr() ? 'Custom hotel QR' : 'Default demo QR' }}</p>
                        <p class="fw-semibold small text-break mb-0">{{ $settings->account_name }}</p>
                    @else
                        <div class="py-4">
                            <i class="bi bi-qr-code fs-1 text-muted" aria-hidden="true"></i>
                            <p class="fw-semibold small mt-3 mb-1">No QR code uploaded yet</p>
                            <p class="small text-muted mb-0">Upload your hotel's payment QR to get started.</p>
                        </div>
                    @endif
                    @if($settings->hasCustomQr())
                        <button type="submit" form="removeCustomQrForm" class="btn btn-sm btn-outline-danger mt-3" data-confirm="Remove the custom QR and use the default demo QR?">Remove Custom QR</button>
                    @endif
                </section>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white px-4 py-3 d-flex justify-content-end"><button class="btn btn-hotel px-4" type="submit">Save Payment Settings</button></div>
</form>
@if($settings->hasCustomQr())
    <form id="removeCustomQrForm" action="{{ route('payment-settings.remove-qr') }}" method="POST">@csrf @method('DELETE')</form>
@endif
@endsection
