(function () {
    const typeSelect = document.querySelector('[data-source-type-selector], [data-source-type-selector="true"]');
    const iconSelect = document.querySelector('[data-icon-selector], [data-icon-selector="true"]');
    const guideBox = document.querySelector('[data-source-guide]');
    const iconPreview = document.querySelector('[data-icon-preview]');
    const fieldRows = Array.from(document.querySelectorAll('[data-source-types]'));

    const guides = {
        cartography_link: {
            title: 'Lien vers cartographie',
            steps: [
                'Renseignez une URL de consultation (interne ou externe).',
                'Associez une carte interactive interne si elle existe.',
                'Precisez un resume court oriente utilisateur final.',
            ],
        },
        wms: {
            title: 'Service WMS',
            steps: [
                'Renseignez l’URL du service WMS.',
                'Sélectionnez l’endpoint WMS associé.',
                'Indiquez un format d’usage et une licence.',
            ],
        },
        wfs: {
            title: 'Service WFS',
            steps: [
                'Renseignez l’URL du service WFS.',
                'Sélectionnez l’endpoint WFS associé.',
                'Ajoutez un résumé orienté réutilisation des données.',
            ],
        },
        data_file: {
            title: 'Fichier de données',
            steps: [
                'Choisissez le format (Excel, CSV, JSON, GeoJSON...).',
                'Renseignez le chemin du fichier et/ou une URL de téléchargement.',
                'Ajoutez une image de base pour faciliter l identification.',
            ],
        },
        static_map: {
            title: 'Carte statique',
            steps: [
                'Liez la source à une carte statique existante.',
                'Renseignez le fichier principal (PDF/PNG) et le format.',
                'Ajoutez un résumé pédagogique de l’usage de la carte.',
            ],
        },
    };

    const iconMap = {
        map: 'fa-map',
        layers: 'fa-layer-group',
        database: 'fa-database',
        chart: 'fa-chart-line',
        business: 'fa-building',
        briefcase: 'fa-briefcase',
        building: 'fa-city',
        handshake: 'fa-handshake',
        digital: 'fa-laptop-code',
        si: 'fa-network-wired',
        sig: 'fa-map-location-dot',
        network: 'fa-diagram-project',
        cloud: 'fa-cloud',
        server: 'fa-server',
        code: 'fa-code',
        route: 'fa-route',
        roadwork: 'fa-triangle-exclamation',
        transport: 'fa-truck-fast',
        file: 'fa-file',
        'file-excel': 'fa-file-excel',
        'file-csv': 'fa-file-csv',
        'file-json': 'fa-file-code',
        'file-pdf': 'fa-file-pdf',
        link: 'fa-link',
        globe: 'fa-globe',
        satellite: 'fa-satellite',
        road: 'fa-road',
        truck: 'fa-truck',
        bus: 'fa-bus',
        car: 'fa-car',
        ship: 'fa-ship',
        bridge: 'fa-bridge',
        cone: 'fa-traffic-cone',
        traffic: 'fa-traffic-light',
        'map-pin': 'fa-map-pin',
        compass: 'fa-compass',
        wrench: 'fa-wrench',
        clipboard: 'fa-clipboard-list',
        shield: 'fa-shield-alt',
        'chart-line': 'fa-chart-line',
        'location-dot': 'fa-location-dot',
        users: 'fa-users',
        download: 'fa-download',
        search: 'fa-magnifying-glass',
    };

    function resolveIconClass(iconKey) {
        return iconMap[iconKey] || 'fa-circle';
    }

    function escapeHtml(value) {
        return value
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderIconOption(data, escape) {
        if (!data.value) {
            return `<div class="ds-icon-option ds-icon-option--placeholder"><span class="ds-icon-option__label">${escape(data.text || 'Choisir une icone')}</span></div>`;
        }

        const iconClass = resolveIconClass(String(data.value));
        return `<div class="ds-icon-option"><span class="ds-icon-option__glyph"><i class="fa ${escape(iconClass)}" aria-hidden="true"></i></span><span class="ds-icon-option__label">${escape(data.text || data.value)}</span></div>`;
    }

    function renderIconItem(data, escape) {
        if (!data.value) {
            return `<div>${escape(data.text || '')}</div>`;
        }

        const iconClass = resolveIconClass(String(data.value));
        return `<div class="ds-icon-item"><span class="ds-icon-item__glyph"><i class="fa ${escape(iconClass)}" aria-hidden="true"></i></span><span class="ds-icon-item__label">${escape(data.text || data.value)}</span></div>`;
    }

    function decorateNativeOptions() {
        if (!(iconSelect instanceof HTMLSelectElement)) {
            return;
        }

        iconSelect.querySelectorAll('option').forEach((option) => {
            if (!(option instanceof HTMLOptionElement)) {
                return;
            }

            if (!option.value) {
                return;
            }

            option.dataset.iconClass = resolveIconClass(option.value);
        });
    }

    function enhanceIconSelector() {
        if (!(iconSelect instanceof HTMLSelectElement)) {
            return;
        }

        decorateNativeOptions();

        if (iconSelect.tomselect) {
            const control = iconSelect.tomselect;
            Object.entries(control.options).forEach(([value, optionData]) => {
                optionData.iconClass = resolveIconClass(value);
            });

            control.settings.render.option = renderIconOption;
            control.settings.render.item = renderIconItem;
            control.clearCache();
            control.refreshOptions(false);
            control.refreshItems();
            control.off('change', updateIconPreview);
            control.on('change', updateIconPreview);

            return;
        }

        if (typeof window.TomSelect !== 'function') {
            return;
        }

        const placeholderText = iconSelect.options.length > 0 ? iconSelect.options[0].textContent || 'Choisir une icone' : 'Choisir une icone';
        new window.TomSelect(iconSelect, {
            create: false,
            allowEmptyOption: true,
            closeAfterSelect: true,
            placeholder: placeholderText,
            render: {
                option: renderIconOption,
                item: renderIconItem,
            },
            onChange: updateIconPreview,
        });
    }

    function updateGuide(type) {
        if (!(guideBox instanceof HTMLElement)) {
            return;
        }

        const guide = guides[type];
        if (!guide) {
            guideBox.innerHTML = '';
            return;
        }

        const items = guide.steps.map((step) => `<li>${step}</li>`).join('');
        guideBox.innerHTML = `<p class="ds-guide__title">${guide.title}</p><ol class="ds-guide__list">${items}</ol>`;
    }

    function updateVisibility(type) {
        fieldRows.forEach((row) => {
            if (!(row instanceof HTMLElement)) {
                return;
            }

            const acceptedTypes = (row.dataset.sourceTypes || '')
                .split(',')
                .map((value) => value.trim())
                .filter((value) => value !== '');

            const visible = acceptedTypes.length === 0 || acceptedTypes.includes(type);
            row.hidden = !visible;

            row.querySelectorAll('input, select, textarea').forEach((input) => {
                if (input === typeSelect) {
                    return;
                }

                if (input instanceof HTMLInputElement || input instanceof HTMLSelectElement || input instanceof HTMLTextAreaElement) {
                    input.disabled = !visible;
                }
            });
        });
    }

    function updateIconPreview() {
        if (!(iconSelect instanceof HTMLSelectElement) || !(iconPreview instanceof HTMLElement)) {
            return;
        }

        const iconKey = iconSelect.value;
        if (!iconKey) {
            iconPreview.innerHTML = 'Sélectionnez une icône pour prévisualiser.';
            return;
        }

        const className = resolveIconClass(iconKey);
        const label = iconSelect.selectedOptions.length > 0 ? iconSelect.selectedOptions[0].textContent : iconKey;
        iconPreview.innerHTML = `<span class="ds-icon-preview__chip"><i class="fa ${escapeHtml(className)}" aria-hidden="true"></i> ${escapeHtml((label ?? iconKey).trim())}</span>`;
    }

    function refresh() {
        if (!(typeSelect instanceof HTMLSelectElement)) {
            updateIconPreview();
            return;
        }

        const currentType = typeSelect.value;
        updateVisibility(currentType);
        updateGuide(currentType);
        updateIconPreview();
    }

    if (typeSelect instanceof HTMLSelectElement) {
        typeSelect.addEventListener('change', refresh);
    }
    if (iconSelect instanceof HTMLSelectElement) {
        iconSelect.addEventListener('change', updateIconPreview);
        enhanceIconSelector();
    }

    refresh();
})();
