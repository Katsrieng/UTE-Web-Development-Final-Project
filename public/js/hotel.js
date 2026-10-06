document.addEventListener('DOMContentLoaded', () => {
    const roomPhotoInput = document.getElementById('images');
    document.querySelectorAll('[data-choose-room-photos]').forEach(button => {
        button.addEventListener('click', () => roomPhotoInput?.click());
    });
    roomPhotoInput?.addEventListener('change', () => {
        const status = document.getElementById('photo-selection-status');
        if (!status) return;
        const count = roomPhotoInput.files.length;
        const editUpload = roomPhotoInput.form?.id === 'photo-upload-form';
        status.textContent = count
            ? `${count} photo${count === 1 ? '' : 's'} selected — ${editUpload ? 'ready to upload' : 'ready to save with this room'}.`
            : (editUpload ? 'Photo actions save separately from room details.' : 'Photos upload when you create the room.');
    });
    document.querySelectorAll('[data-room-gallery]').forEach(gallery => {
        const main = gallery.querySelector('[data-room-main]');
        gallery.querySelectorAll('[data-room-thumbnail]').forEach(button => {
            button.addEventListener('click', () => {
                if (!main) return;
                main.src = button.dataset.imageSrc;
                main.alt = button.dataset.imageAlt;
                gallery.querySelectorAll('[data-room-thumbnail]').forEach(item => {
                    item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
                });
            });
        });
    });
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
