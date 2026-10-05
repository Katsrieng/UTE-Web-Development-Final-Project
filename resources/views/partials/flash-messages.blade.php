@if(session('success') || session('error') || session('warning') || session('status'))
    <div class="flash-container">
        @if(session('success'))
            <div class="alert alert-success hotel-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger hotel-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-octagon-fill"></i><span>{{ session('error') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning hotel-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('warning') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('status'))
            <div class="alert alert-info hotel-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill"></i><span>{{ session('status') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>
@endif
