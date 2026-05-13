(function () {
    const maxSize = 5 * 1024 * 1024;
    const acceptedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    const getInputForEditor = (editor) => {
        const inputId = editor.getAttribute('input');

        return inputId ? document.getElementById(inputId) : null;
    };

    const prepareEditor = (editor) => {
        const input = getInputForEditor(editor);
        if (!input || input.dataset.richTextTheme !== 'site') {
            return;
        }

        editor.classList.add('admin-rich-text-editor');
        const toolbar = editor.toolbarElement;
        if (toolbar) {
            toolbar.classList.add('admin-rich-text-toolbar');
        }
    };

    const setUploadError = (editor, message) => {
        const field = editor.closest('.field-text_editor');
        if (!field) {
            window.alert(message);
            return;
        }

        let error = field.querySelector('[data-rich-text-error]');
        if (!error) {
            error = document.createElement('p');
            error.dataset.richTextError = 'true';
            error.className = 'admin-rich-text-error';
            field.appendChild(error);
        }

        error.textContent = message;
    };

    const clearUploadError = (editor) => {
        const error = editor.closest('.field-text_editor')?.querySelector('[data-rich-text-error]');
        if (error) {
            error.remove();
        }
    };

    const uploadAttachment = (editor, attachment) => {
        const input = getInputForEditor(editor);
        const file = attachment.file;
        if (!input || !file) {
            return;
        }

        if (!acceptedTypes.includes(file.type)) {
            attachment.remove();
            setUploadError(editor, 'Formats acceptés : JPG, PNG, WebP ou GIF.');
            return;
        }

        if (file.size > maxSize) {
            attachment.remove();
            setUploadError(editor, 'L’image dépasse la taille maximale de 5 Mo.');
            return;
        }

        clearUploadError(editor);

        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', input.dataset.richTextUploadUrl, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', input.dataset.richTextUploadToken);

        xhr.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) {
                attachment.setUploadProgress((event.loaded / event.total) * 100);
            }
        });

        xhr.addEventListener('load', () => {
            let payload = {};
            try {
                payload = JSON.parse(xhr.responseText);
            } catch (error) {
                payload = {};
            }

            if (xhr.status >= 200 && xhr.status < 300 && payload.url) {
                attachment.setAttributes({
                    url: payload.url,
                    href: payload.href || payload.url,
                });
                return;
            }

            attachment.remove();
            setUploadError(editor, payload.error || 'Impossible d’envoyer cette image.');
        });

        xhr.addEventListener('error', () => {
            attachment.remove();
            setUploadError(editor, 'Impossible d’envoyer cette image.');
        });

        xhr.send(formData);
    };

    document.addEventListener('trix-initialize', (event) => {
        prepareEditor(event.target);
    });

    document.addEventListener('trix-attachment-add', (event) => {
        const editor = event.target;
        const input = getInputForEditor(editor);
        if (!input || input.dataset.richTextTheme !== 'site') {
            return;
        }

        uploadAttachment(editor, event.attachment);
    });
})();
