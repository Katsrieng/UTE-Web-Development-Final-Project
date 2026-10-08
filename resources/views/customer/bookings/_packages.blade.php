@php
    $selectedPackages = collect(old('packages', $reservation['packages'] ?? []))->filter(fn ($row) => is_array($row) && isset($row['package_id']) && (is_int($row['package_id']) || is_string($row['package_id'])) && ctype_digit((string) $row['package_id']))->keyBy('package_id');
@endphp
<section class="checkout-add-ons mt-4 pt-4 border-top" aria-labelledby="add-ons-heading">
    <h2 id="add-ons-heading" class="h5 mb-1">Enhance your stay</h2>
    <p class="text-muted small mb-3">Add optional extras to your booking.</p>
    @forelse($packages as $package)
        @php
            $selected = $selectedPackages->get($package->id);
            $singleUnit = in_array($package->type, ['romantic', 'family', 'accommodation'], true);
            $quantityLimit = $package->bookingQuantityLimit(max(1, (int) old('number_of_guests', $reservation['number_of_guests'] ?? 1)));
            $quantityValue = $singleUnit ? 1 : min($quantityLimit, max(1, (int) (is_scalar($selected['quantity'] ?? 1) ? ($selected['quantity'] ?? 1) : 1)));
        @endphp
        <div class="checkout-add-on">
            <input id="package-{{ $package->id }}" type="checkbox" class="form-check-input" name="packages[{{ $package->id }}][package_id]" value="{{ $package->id }}" data-package-selection data-package-type="{{ $package->type }}" data-package-name="{{ $package->name }}" data-unit-cents="{{ (int) round($package->price * 100) }}" data-quantity-target="quantity-{{ $package->id }}" @checked($selected)>
            <div class="checkout-add-on-content">
                <label class="fw-semibold" for="package-{{ $package->id }}">{{ $package->name }}</label>
                @if($package->description)<p class="small text-muted mb-0 mt-1">{{ Illuminate\Support\Str::limit($package->description, 140) }}</p>@endif
            </div>
            <div class="checkout-add-on-actions"><span class="checkout-unit-price">${{ number_format($package->price, 2) }} <small class="text-muted fw-normal">/ unit</small></span>
                @if(!$singleUnit)<div data-quantity-wrap class="checkout-quantity mt-2" @if(!$selected) hidden @endif><label for="quantity-{{ $package->id }}" class="small text-muted">Qty</label><button type="button" class="btn btn-outline-secondary btn-sm" data-quantity-step="-1" data-quantity-target="quantity-{{ $package->id }}" aria-label="Decrease quantity for {{ $package->name }}">−</button><input id="quantity-{{ $package->id }}" type="number" min="1" max="{{ $quantityLimit }}" name="packages[{{ $package->id }}][quantity]" value="{{ $quantityValue }}" class="form-control form-control-sm" data-package-quantity @disabled(!$selected)><button type="button" class="btn btn-outline-secondary btn-sm" data-quantity-step="1" data-quantity-target="quantity-{{ $package->id }}" aria-label="Increase quantity for {{ $package->name }}">+</button></div>
                @else<input id="quantity-{{ $package->id }}" type="hidden" name="packages[{{ $package->id }}][quantity]" value="1" data-package-quantity @disabled(!$selected)>@endif
            </div>
        </div>
    @empty
        <p class="small text-muted mb-0">No optional packages are available right now.</p>
    @endforelse
</section>
