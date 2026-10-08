(() => {
    const form = document.querySelector('[data-payment-checkout]');
    if (!form) return;
    const card = document.getElementById('demoCardFields');
    const manual = document.getElementById('manualPaymentFields');
    const reference = document.getElementById('transaction_reference');
    const submit = document.getElementById('paymentSubmit');
    const number = document.getElementById('demoCardNumber');
    const expiry = document.getElementById('demoCardExpiry');
    const cvv = document.getElementById('demoCardCvv');
    const holder = document.getElementById('demoCardholder');
    const digits = value => value.replace(/\D/g, '');
    function selectMethod() {
        const method = form.querySelector('[name="payment_method"]:checked').value;
        card.hidden = method !== 'Card';
        card.disabled = method !== 'Card';
        manual.hidden = method !== 'ABA / KHQR';
        reference.disabled = manual.hidden;
        reference.required = !manual.hidden;
        form.querySelectorAll('.payment-method-tile').forEach(tile => tile.classList.toggle('is-selected', tile.querySelector('input').checked));
        document.getElementById('paymentNextStep').textContent = method === 'Card'
            ? 'Demo only: submitting records Paid without charging a real card.'
            : method === 'Cash at Hotel' ? 'Payment pending — pay at hotel. Staff will record collection.' : 'Payment stays Pending until hotel staff manually verify your transaction.';
        submit.textContent = method === 'Card' ? `Pay ${submit.dataset.amount} · Demo` : method === 'Cash at Hotel' ? 'Choose Pay at Hotel' : 'Submit for Verification';
    }
    form.querySelectorAll('[name="payment_method"]').forEach(input => input.addEventListener('change', selectMethod));
    number.addEventListener('input', () => {
        number.value = digits(number.value).slice(0, 19).replace(/(.{4})/g, '$1 ').trim();
        number.setCustomValidity('');
    });
    expiry.addEventListener('input', () => {
        const value = digits(expiry.value).slice(0, 4);
        expiry.value = value.length > 2 ? value.slice(0, 2) + ' / ' + value.slice(2) : value;
        expiry.setCustomValidity('');
    });
    cvv.addEventListener('input', () => { cvv.value = digits(cvv.value).slice(0, 4); });
    holder.addEventListener('input', () => holder.setCustomValidity(''));
    form.addEventListener('submit', event => {
        if (!card.disabled) {
            const raw = digits(number.value);
            number.setCustomValidity(raw.length >= 13 && raw.length <= 19 ? '' : 'Enter a demo card number with 13–19 digits.');
            holder.setCustomValidity(holder.value.trim() ? '' : 'Enter the demo cardholder name.');
            const value = digits(expiry.value);
            const month = Number(value.slice(0, 2));
            const year = 2000 + Number(value.slice(2));
            const now = new Date();
            expiry.setCustomValidity(value.length === 4 && month >= 1 && month <= 12 && (year > now.getFullYear() || (year === now.getFullYear() && month >= now.getMonth() + 1)) ? '' : 'Enter a current or future expiry date (MM / YY).');
        }
        if (!form.reportValidity()) { event.preventDefault(); return; }
        submit.disabled = true;
        submit.textContent = 'Recording payment…';
    });
    window.addEventListener('pageshow', () => { submit.disabled = false; selectMethod(); });
    selectMethod();
})();
