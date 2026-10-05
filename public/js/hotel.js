document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);

            if (!input) {
                return;
            }

            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.querySelector('i')?.classList.toggle('bi-eye', showing);
            button.querySelector('i')?.classList.toggle('bi-eye-slash', !showing);
        });
    });

    document.querySelectorAll('input[type="file"][data-preview-target]').forEach((input) => {
        input.addEventListener('change', () => {
            const target = document.getElementById(input.dataset.previewTarget);

            if (!target) {
                return;
            }

            target.replaceChildren();

            [...input.files].slice(0, 5).forEach((file) => {
                if (!file.type.startsWith('image/')) {
                    return;
                }

                const image = document.createElement('img');
                image.alt = `Preview of ${file.name}`;
                image.src = URL.createObjectURL(file);
                image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
                target.appendChild(image);
            });
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!window.confirm(element.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
});
