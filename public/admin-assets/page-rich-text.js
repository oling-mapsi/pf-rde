(function () {
    const maxSize = 5 * 1024 * 1024;
    const acceptedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    const editorContentCss = '/admin-assets/rich-text-content.css';

    const styleFormats = [
        {
            title: 'Styles PF',
            items: [
                { title: 'Paragraphe', block: 'p' },
                { title: 'Titre de section', block: 'h2' },
                { title: 'Sous-titre', block: 'h3' },
                { title: 'Intertitre discret', block: 'h4' },
                { title: 'Introduction', block: 'p', classes: 'pf-lead' },
                { title: 'Texte mis en avant', inline: 'span', classes: 'pf-highlight' },
                { title: 'Encadré PF', block: 'div', classes: 'pf-callout pf-callout--accent', wrapper: true },
                { title: 'Encadré neutre', block: 'div', classes: 'pf-callout pf-callout--muted', wrapper: true },
                { title: 'Carte éditoriale', block: 'div', classes: 'pf-card', wrapper: true },
            ],
        },
        {
            title: 'Images',
            items: [
                { title: 'Image centrée', selector: 'img,figure', classes: 'pf-media-center' },
                { title: 'Image à gauche', selector: 'img,figure', classes: 'pf-media-left' },
                { title: 'Image à droite', selector: 'img,figure', classes: 'pf-media-right' },
                { title: 'Image pleine largeur', selector: 'img,figure', classes: 'pf-media-full' },
                { title: 'Image avec cadre', selector: 'img,figure', classes: 'pf-media-framed' },
            ],
        },
        {
            title: 'Tableaux',
            items: [
                { title: 'Tableau PF', selector: 'table', classes: 'pf-table' },
                { title: 'Tableau compact', selector: 'table', classes: 'pf-table pf-table--compact' },
                { title: 'Tableau zébré', selector: 'table', classes: 'pf-table pf-table--striped' },
            ],
        },
    ];

    const templates = [
        {
            title: '2 colonnes éditoriales',
            description: 'Deux cartes alignées',
            content: `
                <div class="pf-columns pf-columns--2">
                    <div class="pf-card">
                        <h3>Titre 1</h3>
                        <p>Texte de présentation.</p>
                    </div>
                    <div class="pf-card">
                        <h3>Titre 2</h3>
                        <p>Texte de présentation.</p>
                    </div>
                </div>
            `,
        },
        {
            title: 'Comparatif 3 blocs',
            description: 'Bloc type smartart horizontal',
            content: `
                <div class="pf-columns pf-columns--3">
                    <div class="pf-card"><h3>Bloc 1</h3><p>Contenu.</p></div>
                    <div class="pf-card"><h3>Bloc 2</h3><p>Contenu.</p></div>
                    <div class="pf-card"><h3>Bloc 3</h3><p>Contenu.</p></div>
                </div>
            `,
        },
        {
            title: 'Processus / étapes',
            description: 'Suite d’étapes visuelles',
            content: `
                <div class="pf-steps">
                    <div class="pf-step">
                        <span class="pf-step__index">1</span>
                        <div><h3>Étape 1</h3><p>Description.</p></div>
                    </div>
                    <div class="pf-step">
                        <span class="pf-step__index">2</span>
                        <div><h3>Étape 2</h3><p>Description.</p></div>
                    </div>
                    <div class="pf-step">
                        <span class="pf-step__index">3</span>
                        <div><h3>Étape 3</h3><p>Description.</p></div>
                    </div>
                </div>
            `,
        },
        {
            title: 'Chiffres clés',
            description: 'Bloc KPI éditorial',
            content: `
                <div class="pf-kpis">
                    <div class="pf-kpi"><strong>95%</strong><span>Indicateur</span></div>
                    <div class="pf-kpi"><strong>24/7</strong><span>Disponibilité</span></div>
                    <div class="pf-kpi"><strong>12</strong><span>Services</span></div>
                </div>
            `,
        },
    ];

    const setUploadError = (element, message) => {
        const field = element.closest('.field-textarea');
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

    const clearUploadError = (element) => {
        const error = element.closest('.field-textarea')?.querySelector('[data-rich-text-error]');
        if (error) {
            error.remove();
        }
    };

    const createUploadHandler = (textarea) => {
        return (blobInfo, progress) => new Promise((resolve, reject) => {
            const file = blobInfo.blob();
            if (!acceptedTypes.includes(file.type)) {
                setUploadError(textarea, 'Formats acceptés : JPG, PNG, WebP ou GIF.');
                reject({ message: 'Format non accepté', remove: true });
                return;
            }

            if (file.size > maxSize) {
                setUploadError(textarea, 'L’image dépasse la taille maximale de 5 Mo.');
                reject({ message: 'Image trop volumineuse', remove: true });
                return;
            }

            clearUploadError(textarea);

            const formData = new FormData();
            formData.append('file', file, file.name || 'image');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', textarea.dataset.richTextUploadUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', textarea.dataset.richTextUploadToken);

            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    progress((event.loaded / event.total) * 100);
                }
            });

            xhr.addEventListener('load', () => {
                let payload = {};
                try {
                    payload = JSON.parse(xhr.responseText);
                } catch (error) {
                    payload = {};
                }

                if (xhr.status >= 200 && xhr.status < 300 && payload.location) {
                    resolve(payload.location);
                    return;
                }

                const message = payload.error || 'Impossible d’envoyer cette image.';
                setUploadError(textarea, message);
                reject({ message, remove: true });
            });

            xhr.addEventListener('error', () => {
                const message = 'Impossible d’envoyer cette image.';
                setUploadError(textarea, message);
                reject({ message, remove: true });
            });

            xhr.send(formData);
        });
    };

    const initEditor = (textarea) => {
        if (textarea.dataset.richTextReady === 'true' || !window.tinymce) {
            return;
        }

        textarea.dataset.richTextReady = 'true';

        window.tinymce.init({
            target: textarea,
            license_key: 'gpl',
            promotion: false,
            branding: false,
            height: 720,
            min_height: 560,
            menubar: 'file edit view insert format table tools help',
            plugins: 'advlist anchor autoresize charmap code fullscreen help image insertdatetime link lists media preview quickbars searchreplace table template visualblocks wordcount',
            toolbar: 'undo redo | blocks styles | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image media link table template | hr charmap blockquote | code visualblocks fullscreen preview help',
            toolbar_sticky: true,
            contextmenu: 'link image table',
            object_resizing: 'img,table',
            browser_spellcheck: true,
            images_file_types: 'jpg,jpeg,png,webp,gif',
            automatic_uploads: true,
            images_upload_handler: createUploadHandler(textarea),
            image_title: true,
            image_caption: true,
            image_advtab: true,
            image_class_list: [
                { title: 'Aucune', value: '' },
                { title: 'Centrée', value: 'pf-media-center' },
                { title: 'À gauche', value: 'pf-media-left' },
                { title: 'À droite', value: 'pf-media-right' },
                { title: 'Pleine largeur', value: 'pf-media-full' },
                { title: 'Avec cadre', value: 'pf-media-framed' },
            ],
            link_default_target: '_self',
            link_assume_external_targets: true,
            link_context_toolbar: true,
            block_formats: 'Paragraphe=p;Titre de section=h2;Sous-titre=h3;Intertitre=h4',
            style_formats: styleFormats,
            templates,
            table_default_attributes: {
                class: 'pf-table',
            },
            table_class_list: [
                { title: 'Tableau PF', value: 'pf-table' },
                { title: 'Tableau compact', value: 'pf-table pf-table--compact' },
                { title: 'Tableau zébré', value: 'pf-table pf-table--striped' },
            ],
            table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol | tablecellprops tablemergecells tablesplitcells',
            quickbars_selection_toolbar: 'bold italic | quicklink blockquote | alignleft aligncenter alignright',
            quickbars_insert_toolbar: 'image table hr',
            content_css: editorContentCss,
            content_style: 'body{padding:24px;background:transparent;}',
            setup: (editor) => {
                editor.on('init', () => {
                    textarea.form?.addEventListener('submit', () => editor.save(), { once: false });
                });

                editor.on('blur change input undo redo', () => {
                    clearUploadError(textarea);
                    editor.save();
                });
            },
        });
    };

    const bootEditors = () => {
        document.querySelectorAll('textarea[data-admin-rich-text="site"]').forEach(initEditor);
    };

    document.addEventListener('DOMContentLoaded', bootEditors);
})();
