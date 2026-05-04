(() => {
    const root = document.querySelector('[data-controller~="interactive-map"]');
    if (!root) {
        return;
    }

    const statusEl = root.querySelector('[data-interactive-map-target="status"]');
    const canvasEl = root.querySelector('[data-interactive-map-target="canvas"]');
    const resultCountEl = root.querySelector('[data-interactive-map-target="resultCount"]');
    const legendListEl = root.querySelector('[data-interactive-map-target="legendList"]');
    const featureInfoEl = root.querySelector('[data-interactive-map-target="featureInfo"]');
    const searchInputEl = root.querySelector('[data-interactive-map-target="searchInput"]');
    const statusFilterEl = root.querySelector('[data-interactive-map-target="statusFilter"]');
    const categoryFilterEl = root.querySelector('[data-interactive-map-target="categoryFilter"]');
    const resetButton = root.querySelector('[data-action="interactive-map#resetFilters"]');

    if (!(statusEl instanceof HTMLElement) || !(canvasEl instanceof HTMLElement)) {
        return;
    }

    const bootstrapUrl = root.getAttribute('data-interactive-map-bootstrap-url-value') || '';
    const basemapUrl = root.getAttribute('data-interactive-map-basemap-url-value') || '';
    const featuresUrl = root.getAttribute('data-interactive-map-features-url-value') || '';
    const legendUrl = root.getAttribute('data-interactive-map-legend-url-value') || '';
    const featureInfoUrl = root.getAttribute('data-interactive-map-feature-info-url-value') || '';
    if (!bootstrapUrl || !basemapUrl || !featuresUrl || !legendUrl || !featureInfoUrl) {
        return;
    }

    const MAPLIBRE_JS_URL = '/vendor/maplibre/maplibre-gl-csp.js';
    const MAPLIBRE_CSS_URL = '/vendor/maplibre/maplibre-gl.css';
    const MAPLIBRE_WORKER_URL = '/vendor/maplibre/maplibre-gl-csp-worker.js';

    const setStatus = (message) => {
        statusEl.textContent = message;
    };

    const escapeHtml = (value) =>
        String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

    const loadMapLibre = async () => {
        if (window.maplibregl) {
            if (typeof window.maplibregl.setWorkerUrl === 'function') {
                window.maplibregl.setWorkerUrl(MAPLIBRE_WORKER_URL);
            }
            return;
        }

        if (!document.querySelector('link[data-maplibre-fallback-css]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = MAPLIBRE_CSS_URL;
            link.setAttribute('data-maplibre-fallback-css', '1');
            document.head.appendChild(link);
        }

        await new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-maplibre-fallback-js]');
            if (existingScript instanceof HTMLScriptElement) {
                existingScript.addEventListener('load', () => resolve(), { once: true });
                existingScript.addEventListener('error', () => reject(new Error('Chargement MapLibre impossible.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = MAPLIBRE_JS_URL;
            script.async = true;
            script.defer = true;
            script.setAttribute('data-maplibre-fallback-js', '1');
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Chargement MapLibre impossible.'));
            document.head.appendChild(script);
        });

        if (window.maplibregl && typeof window.maplibregl.setWorkerUrl === 'function') {
            window.maplibregl.setWorkerUrl(MAPLIBRE_WORKER_URL);
        }
    };

    const fetchJson = async (url) => {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    };

    const shouldRunFallback = () => {
        const text = (statusEl.textContent || '').trim().toLowerCase();
        return !canvasEl.querySelector('.maplibregl-canvas') && text.includes('initialisation de la carte interactive');
    };

    const EMPTY_FEATURE_COLLECTION = { type: 'FeatureCollection', features: [] };

    const buildFeatureUrl = (activeLayerIds) => {
        const params = new URLSearchParams();
        activeLayerIds.forEach((layerId) => params.append('layers[]', layerId));
        if (statusFilterEl instanceof HTMLSelectElement && statusFilterEl.value !== '') {
            params.set('status', statusFilterEl.value);
        }
        if (categoryFilterEl instanceof HTMLSelectElement && categoryFilterEl.value !== '') {
            params.set('category', categoryFilterEl.value);
        }
        if (searchInputEl instanceof HTMLInputElement && searchInputEl.value.trim() !== '') {
            params.set('q', searchInputEl.value.trim());
        }
        return `${featuresUrl}?${params.toString()}`;
    };

    const buildLegendUrl = (activeLayerIds) => {
        const params = new URLSearchParams();
        activeLayerIds.forEach((layerId) => params.append('layers[]', layerId));
        if (statusFilterEl instanceof HTMLSelectElement && statusFilterEl.value !== '') {
            params.set('status', statusFilterEl.value);
        }
        if (categoryFilterEl instanceof HTMLSelectElement && categoryFilterEl.value !== '') {
            params.set('category', categoryFilterEl.value);
        }
        if (searchInputEl instanceof HTMLInputElement && searchInputEl.value.trim() !== '') {
            params.set('q', searchInputEl.value.trim());
        }
        return `${legendUrl}?${params.toString()}`;
    };

    const run = async () => {
        await loadMapLibre();
        setStatus('Initialisation du moteur cartographique (fallback)...');

        const bootstrap = await fetchJson(bootstrapUrl);
        const rawBasemapConfig = bootstrap?.map?.basemap ?? null;
        const basemapConfig = rawBasemapConfig && String(rawBasemapConfig.type || '').toLowerCase() === 'raster'
            ? rawBasemapConfig
            : null;
        const basemap = basemapConfig === null
            ? await fetchJson(basemapUrl).then((payload) => (
                payload?.type === 'FeatureCollection' && Array.isArray(payload.features)
                    ? payload
                    : EMPTY_FEATURE_COLLECTION
            ))
            : EMPTY_FEATURE_COLLECTION;
        const layers = Array.isArray(bootstrap.layers) ? bootstrap.layers : [];
        const activeLayerIds = layers
            .filter((layer) => Boolean(layer.visibleByDefault))
            .map((layer) => String(layer.id))
            .filter((value) => value !== '');

        if (statusFilterEl instanceof HTMLSelectElement && bootstrap.filters?.statuses) {
            statusFilterEl.innerHTML = `<option value="">Tous statuts</option>${bootstrap.filters.statuses
                .map((status) => `<option value="${escapeHtml(status)}">${escapeHtml(status)}</option>`)
                .join('')}`;
        }
        if (categoryFilterEl instanceof HTMLSelectElement && bootstrap.filters?.categories) {
            categoryFilterEl.innerHTML = `<option value="">Toutes categories</option>${bootstrap.filters.categories
                .map((category) => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`)
                .join('')}`;
        }

        const center = Array.isArray(bootstrap.map?.center) ? bootstrap.map.center : [-61.551, 16.265];
        const mapStyle = basemapConfig !== null
            ? {
                version: 8,
                sources: {
                    'rdg-raster-basemap': {
                        type: 'raster',
                        tiles: Array.isArray(basemapConfig.tiles) ? basemapConfig.tiles : [],
                        tileSize: Number.isFinite(basemapConfig.tileSize) ? Number(basemapConfig.tileSize) : 256,
                        attribution: String(basemapConfig.attribution || ''),
                        maxzoom: Number.isFinite(basemapConfig.maxZoom) ? Number(basemapConfig.maxZoom) : 19,
                    },
                    'reseau_principal-source': { type: 'geojson', data: { type: 'FeatureCollection', features: [] } },
                    'travaux_planifies-source': { type: 'geojson', data: { type: 'FeatureCollection', features: [] } },
                },
                layers: [
                    { id: 'rdg-raster-basemap-layer', type: 'raster', source: 'rdg-raster-basemap' },
                    {
                        id: 'reseau_principal-layer',
                        type: 'line',
                        source: 'reseau_principal-source',
                        paint: {
                            'line-color': ['match', ['get', 'category'], 'nationale', '#0E5AA7', 'departementale', '#2FA7D9', 'communale', '#7AA63A', '#0E5AA7'],
                            'line-width': ['interpolate', ['linear'], ['zoom'], 9, 2.2, 12, 4.2, 15, 6.2],
                            'line-opacity': 0.9,
                        },
                    },
                    {
                        id: 'travaux_planifies-layer',
                        type: 'circle',
                        source: 'travaux_planifies-source',
                        paint: {
                            'circle-color': ['match', ['get', 'status'], 'planned', '#F3C623', 'in_progress', '#E57A22', 'done', '#7AA63A', '#2FA7D9'],
                            'circle-radius': ['interpolate', ['linear'], ['zoom'], 9, 4.5, 12, 7, 15, 9.5],
                            'circle-stroke-width': 1.5,
                            'circle-stroke-color': '#ffffff',
                            'circle-opacity': 0.95,
                        },
                    },
                ],
            }
            : {
                version: 8,
                sources: {
                    'local-basemap': { type: 'geojson', data: basemap },
                    'reseau_principal-source': { type: 'geojson', data: { type: 'FeatureCollection', features: [] } },
                    'travaux_planifies-source': { type: 'geojson', data: { type: 'FeatureCollection', features: [] } },
                },
                layers: [
                    { id: 'sea-bg', type: 'background', paint: { 'background-color': '#dceef9' } },
                    {
                        id: 'land-fill',
                        type: 'fill',
                        source: 'local-basemap',
                        filter: ['==', ['get', 'basemap_type'], 'land'],
                        paint: { 'fill-color': '#eef4dc', 'fill-opacity': 0.96 },
                    },
                    {
                        id: 'land-outline',
                        type: 'line',
                        source: 'local-basemap',
                        filter: ['==', ['get', 'basemap_type'], 'land'],
                        paint: { 'line-color': '#5e7750', 'line-width': 1.5 },
                    },
                    {
                        id: 'road-local',
                        type: 'line',
                        source: 'local-basemap',
                        filter: ['all', ['==', ['get', 'basemap_type'], 'road'], ['==', ['get', 'road_class'], 'local']],
                        paint: {
                            'line-color': '#a9b8c6',
                            'line-width': ['interpolate', ['linear'], ['zoom'], 9, 0.6, 12, 1.2, 15, 2.3],
                            'line-opacity': 0.88,
                        },
                        layout: { 'line-cap': 'round', 'line-join': 'round' },
                    },
                    {
                        id: 'road-secondary',
                        type: 'line',
                        source: 'local-basemap',
                        filter: ['all', ['==', ['get', 'basemap_type'], 'road'], ['==', ['get', 'road_class'], 'secondary']],
                        paint: {
                            'line-color': '#88a8be',
                            'line-width': ['interpolate', ['linear'], ['zoom'], 9, 1.1, 12, 2.1, 15, 3.8],
                            'line-opacity': 0.92,
                        },
                        layout: { 'line-cap': 'round', 'line-join': 'round' },
                    },
                    {
                        id: 'road-primary',
                        type: 'line',
                        source: 'local-basemap',
                        filter: ['all', ['==', ['get', 'basemap_type'], 'road'], ['==', ['get', 'road_class'], 'primary']],
                        paint: {
                            'line-color': '#6d95b3',
                            'line-width': ['interpolate', ['linear'], ['zoom'], 9, 1.5, 12, 2.9, 15, 5.0],
                            'line-opacity': 0.96,
                        },
                        layout: { 'line-cap': 'round', 'line-join': 'round' },
                    },
                    {
                        id: 'road-trunk',
                        type: 'line',
                        source: 'local-basemap',
                        filter: ['all', ['==', ['get', 'basemap_type'], 'road'], ['==', ['get', 'road_class'], 'trunk']],
                        paint: {
                            'line-color': '#4f7fa4',
                            'line-width': ['interpolate', ['linear'], ['zoom'], 9, 1.9, 12, 3.6, 15, 6.0],
                            'line-opacity': 0.98,
                        },
                        layout: { 'line-cap': 'round', 'line-join': 'round' },
                    },
                    {
                        id: 'place-points',
                        type: 'circle',
                        source: 'local-basemap',
                        filter: ['==', ['get', 'basemap_type'], 'place'],
                        paint: {
                            'circle-color': '#4e6680',
                            'circle-stroke-color': '#ffffff',
                            'circle-stroke-width': 1,
                            'circle-radius': ['interpolate', ['linear'], ['zoom'], 9, 1.8, 12, 2.8, 15, 4.2],
                            'circle-opacity': 0.9,
                        },
                    },
                    {
                        id: 'reseau_principal-layer',
                        type: 'line',
                        source: 'reseau_principal-source',
                        paint: {
                            'line-color': ['match', ['get', 'category'], 'nationale', '#0E5AA7', 'departementale', '#2FA7D9', 'communale', '#7AA63A', '#0E5AA7'],
                            'line-width': ['interpolate', ['linear'], ['zoom'], 9, 2.2, 12, 4.2, 15, 6.2],
                            'line-opacity': 0.9,
                        },
                    },
                    {
                        id: 'travaux_planifies-layer',
                        type: 'circle',
                        source: 'travaux_planifies-source',
                        paint: {
                            'circle-color': ['match', ['get', 'status'], 'planned', '#F3C623', 'in_progress', '#E57A22', 'done', '#7AA63A', '#2FA7D9'],
                            'circle-radius': ['interpolate', ['linear'], ['zoom'], 9, 4.5, 12, 7, 15, 9.5],
                            'circle-stroke-width': 1.5,
                            'circle-stroke-color': '#ffffff',
                            'circle-opacity': 0.95,
                        },
                    },
                ],
            };
        const map = new window.maplibregl.Map({
            container: canvasEl,
            center,
            zoom: Number.isFinite(bootstrap.map?.zoom) ? Number(bootstrap.map.zoom) : 11,
            minZoom: Number.isFinite(bootstrap.map?.minZoom) ? Number(bootstrap.map.minZoom) : 9,
            maxZoom: Number.isFinite(bootstrap.map?.maxZoom) ? Number(bootstrap.map.maxZoom) : 17,
            style: mapStyle,
        });

        map.addControl(new window.maplibregl.NavigationControl(), 'top-right');

        const refresh = async () => {
            const payload = await fetchJson(buildFeatureUrl(activeLayerIds));
            const features = Array.isArray(payload.features) ? payload.features : [];
            const byLayer = { reseau_principal: [], travaux_planifies: [] };
            features.forEach((feature) => {
                const layerId = String(feature?.properties?.layer_id || '');
                if (layerId in byLayer) {
                    byLayer[layerId].push(feature);
                }
            });

            const roadSource = map.getSource('reseau_principal-source');
            const worksSource = map.getSource('travaux_planifies-source');
            if (roadSource && typeof roadSource.setData === 'function') {
                roadSource.setData({ type: 'FeatureCollection', features: byLayer.reseau_principal });
            }
            if (worksSource && typeof worksSource.setData === 'function') {
                worksSource.setData({ type: 'FeatureCollection', features: byLayer.travaux_planifies });
            }

            if (resultCountEl instanceof HTMLElement) {
                resultCountEl.textContent = `${Number(payload.total || 0)} element(s) visible(s)`;
            }

            const legend = await fetchJson(buildLegendUrl(activeLayerIds));
            const items = Array.isArray(legend.items) ? legend.items : [];
            if (legendListEl instanceof HTMLElement) {
                legendListEl.innerHTML = items.length === 0
                    ? '<li>Aucune legende disponible.</li>'
                    : items.map((item) =>
                        `<li><span class="map-legend-symbol" style="--legend-color:${escapeHtml(item.color || '#0E5AA7')}"></span><strong>${escapeHtml(item.label || 'Element')}</strong> <small>${Number(item.count || 0)} element(s)</small></li>`
                    ).join('');
            }

            if (bootstrap.degradedMode) {
                setStatus(bootstrap.degradedModeMessage || 'Mode degrade actif: certains services sont indisponibles.');
            } else {
                setStatus('Carte interactive chargee.');
            }
        };

        map.on('click', async (event) => {
            try {
                const params = new URLSearchParams();
                params.set('lng', String(event.lngLat.lng));
                params.set('lat', String(event.lngLat.lat));
                activeLayerIds.forEach((layerId) => params.append('layers[]', layerId));
                const payload = await fetchJson(`${featureInfoUrl}?${params.toString()}`);
                if (!payload.feature) {
                    if (featureInfoEl instanceof HTMLElement) {
                        featureInfoEl.innerHTML = '<p class="mb-0">Aucun element significatif a proximite du clic.</p>';
                    }
                    return;
                }

                const feature = payload.feature;
                if (featureInfoEl instanceof HTMLElement) {
                    featureInfoEl.innerHTML = `
                        <p class="mb-2"><strong>${escapeHtml(feature.title || 'Element')}</strong></p>
                        <p class="mb-2">Commune: ${escapeHtml(feature.commune || 'n/a')}</p>
                        <p class="mb-2">Categorie: ${escapeHtml(feature.category || 'n/a')} | Statut: ${escapeHtml(feature.status || 'n/a')}</p>
                        <p class="mb-0">${escapeHtml(feature.description || '')}</p>
                    `;
                }
            } catch (_error) {
                if (featureInfoEl instanceof HTMLElement) {
                    featureInfoEl.innerHTML = '<p class="mb-0">Impossible de recuperer la fiche detaillee.</p>';
                }
            }
        });

        if (searchInputEl instanceof HTMLInputElement) {
            let timeout = null;
            searchInputEl.addEventListener('input', () => {
                window.clearTimeout(timeout);
                timeout = window.setTimeout(() => {
                    refresh().catch((error) => setStatus(`Erreur cartographique: ${error.message || 'inconnue'}`));
                }, 280);
            });
        }

        if (statusFilterEl instanceof HTMLSelectElement) {
            statusFilterEl.addEventListener('change', () => {
                refresh().catch((error) => setStatus(`Erreur cartographique: ${error.message || 'inconnue'}`));
            });
        }
        if (categoryFilterEl instanceof HTMLSelectElement) {
            categoryFilterEl.addEventListener('change', () => {
                refresh().catch((error) => setStatus(`Erreur cartographique: ${error.message || 'inconnue'}`));
            });
        }
        if (resetButton instanceof HTMLButtonElement) {
            resetButton.addEventListener('click', (event) => {
                event.preventDefault();
                if (searchInputEl instanceof HTMLInputElement) {
                    searchInputEl.value = '';
                }
                if (statusFilterEl instanceof HTMLSelectElement) {
                    statusFilterEl.value = '';
                }
                if (categoryFilterEl instanceof HTMLSelectElement) {
                    categoryFilterEl.value = '';
                }
                refresh().catch((error) => setStatus(`Erreur cartographique: ${error.message || 'inconnue'}`));
            });
        }

        await refresh();
    };

    window.setTimeout(() => {
        if (!shouldRunFallback()) {
            return;
        }

        run().catch((error) => {
            setStatus(`Erreur cartographique: ${error && error.message ? error.message : 'inconnue'}`);
        });
    }, 1300);
})();
