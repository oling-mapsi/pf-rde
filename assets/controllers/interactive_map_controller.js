import { Controller } from '@hotwired/stimulus';

const SEARCH_DEBOUNCE_MS = 260;
const MAPLIBRE_JS_URL = '/vendor/maplibre/maplibre-gl-csp.js';
const MAPLIBRE_CSS_URL = '/vendor/maplibre/maplibre-gl.css';
const MAPLIBRE_WORKER_URL = '/vendor/maplibre/maplibre-gl-csp-worker.js';
let maplibreLoaderPromise = null;
const EMPTY_FEATURE_COLLECTION = { type: 'FeatureCollection', features: [] };

export default class extends Controller {
    static targets = [
        'canvas',
        'status',
        'resultCount',
        'layerList',
        'legendList',
        'featureInfo',
        'searchInput',
        'statusFilter',
        'categoryFilter',
    ];

    static values = {
        bootstrapUrl: String,
        basemapUrl: String,
        featuresUrl: String,
        legendUrl: String,
        featureInfoUrl: String,
    };

    connect() {
        this.map = null;
        this.bootstrapPayload = null;
        this.layerConfigs = [];
        this.activeLayerIds = new Set();
        this.layerSources = new Map();
        this.basemapData = EMPTY_FEATURE_COLLECTION;
        this.basemapConfig = null;
        this.searchTimer = null;
        this.abortController = null;
        this.handleMapClickBound = this.handleMapClick.bind(this);
        this.initialize().catch((error) => {
            const message = error && typeof error.message === 'string'
                ? error.message
                : 'Erreur lors du chargement de la carte interactive.';
            this.setStatus(`Erreur cartographique: ${message}`);
            this.renderFeatureInfoMessage('Impossible de charger la simulation cartographique.');
        });
    }

    disconnect() {
        if (this.searchTimer) {
            window.clearTimeout(this.searchTimer);
            this.searchTimer = null;
        }

        if (this.abortController) {
            this.abortController.abort();
        }

        if (this.map) {
            this.map.off('click', this.handleMapClickBound);
            this.map.remove();
            this.map = null;
        }
    }

    onSearchInput() {
        if (this.searchTimer) {
            window.clearTimeout(this.searchTimer);
        }

        this.searchTimer = window.setTimeout(() => {
            this.refreshDataAndLegend();
        }, SEARCH_DEBOUNCE_MS);
    }

    onFilterChange() {
        this.refreshDataAndLegend();
    }

    resetFilters(event) {
        if (event) {
            event.preventDefault();
        }

        if (this.hasSearchInputTarget) {
            this.searchInputTarget.value = '';
        }
        if (this.hasStatusFilterTarget) {
            this.statusFilterTarget.value = '';
        }
        if (this.hasCategoryFilterTarget) {
            this.categoryFilterTarget.value = '';
        }

        this.refreshDataAndLegend();
    }

    async initialize() {
        if (!this.hasCanvasTarget) {
            return;
        }

        this.setStatus('Initialisation du moteur cartographique...');
        await this.ensureMapLibreReady();
        const payload = await this.fetchJson(this.bootstrapUrlValue, { abortable: false });

        this.bootstrapPayload = payload;
        this.basemapConfig = this.normalizeBasemapConfig(payload?.map?.basemap);
        if (this.basemapConfig === null) {
            const basemapPayload = await this.fetchJson(this.basemapUrlValue, { abortable: false });
            this.basemapData = this.normalizeBasemapPayload(basemapPayload);
        } else {
            this.basemapData = EMPTY_FEATURE_COLLECTION;
        }
        this.layerConfigs = Array.isArray(payload.layers) ? payload.layers : [];
        this.activeLayerIds = new Set(
            this.layerConfigs
                .filter((layer) => Boolean(layer.visibleByDefault))
                .map((layer) => String(layer.id))
        );

        this.populateFilterOptions(payload.filters ?? {});
        this.renderLayerToggles();
        this.renderFeatureInfoMessage('Cliquez sur la carte pour simuler un GetFeatureInfo.');

        this.map = this.createMap(payload.map ?? {});
        this.map.on('error', (event) => {
            const message = event?.error?.message || 'erreur cartographique inconnue';
            this.setStatus(`Erreur cartographique: ${message}`);
        });
        this.map.on('load', async () => {
            if (this.basemapConfig === null) {
                this.attachLocalBasemap();
            }
            this.attachLayerSources();
            await this.refreshDataAndLegend();

            if (payload.degradedMode) {
                const message = payload.degradedModeMessage || 'Mode dégradé actif: certains services sont indisponibles.';
                this.setStatus(message);
            } else {
                this.setStatus('Carte interactive chargee.');
            }
        });

        this.map.on('click', this.handleMapClickBound);
    }

    createMap(mapConfig) {
        const center = Array.isArray(mapConfig.center) && mapConfig.center.length === 2
            ? mapConfig.center
            : [-61.551, 16.265];
        const style = this.buildMapStyle(mapConfig);

        const map = new window.maplibregl.Map({
            container: this.canvasTarget,
            center,
            zoom: Number.isFinite(mapConfig.zoom) ? Number(mapConfig.zoom) : 11,
            minZoom: Number.isFinite(mapConfig.minZoom) ? Number(mapConfig.minZoom) : 9,
            maxZoom: Number.isFinite(mapConfig.maxZoom) ? Number(mapConfig.maxZoom) : 17,
            style,
        });

        map.addControl(new window.maplibregl.NavigationControl(), 'top-right');
        map.addControl(
            new window.maplibregl.ScaleControl({
                maxWidth: 120,
                unit: 'metric',
            }),
            'bottom-left'
        );

        return map;
    }

    getCssColorVariable(name) {
        const value = window.getComputedStyle(document.documentElement).getPropertyValue(name).trim();

        return value;
    }

    buildMapStyle(mapConfig) {
        if (this.basemapConfig !== null) {
            const maxZoom = Number.isFinite(this.basemapConfig.maxZoom)
                ? Number(this.basemapConfig.maxZoom)
                : (Number.isFinite(mapConfig.maxZoom) ? Number(mapConfig.maxZoom) : 19);

            return {
                version: 8,
                sources: {
                    'rdg-raster-basemap': {
                        type: 'raster',
                        tiles: this.basemapConfig.tiles,
                        tileSize: Number(this.basemapConfig.tileSize || 256),
                        attribution: this.basemapConfig.attribution,
                        maxzoom: maxZoom,
                    },
                },
                layers: [
                    {
                        id: 'rdg-raster-basemap-layer',
                        type: 'raster',
                        source: 'rdg-raster-basemap',
                        minzoom: 0,
                    },
                ],
            };
        }

        return {
            version: 8,
            layers: [
                {
                    id: 'rdg-sea-background',
                    type: 'background',
                    paint: {
                        'background-color': this.getCssColorVariable('--color-map-sea-background'),
                    },
                },
            ],
        };
    }

    attachLocalBasemap() {
        if (!this.map) {
            return;
        }

        if (!this.map.getSource('rdg-local-basemap')) {
            this.map.addSource('rdg-local-basemap', {
                type: 'geojson',
                data: this.basemapData,
            });
        }

        if (!this.map.getLayer('rdg-land-fill')) {
            this.map.addLayer({
                id: 'rdg-land-fill',
                type: 'fill',
                source: 'rdg-local-basemap',
                filter: ['==', ['get', 'basemap_type'], 'land'],
                paint: {
                    'fill-color': this.getCssColorVariable('--color-map-land-fill'),
                    'fill-opacity': 0.96,
                },
            });
        }

        if (!this.map.getLayer('rdg-land-outline')) {
            this.map.addLayer({
                id: 'rdg-land-outline',
                type: 'line',
                source: 'rdg-local-basemap',
                filter: ['==', ['get', 'basemap_type'], 'land'],
                paint: {
                    'line-color': this.getCssColorVariable('--color-map-land-outline'),
                    'line-width': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        9,
                        0.9,
                        12,
                        1.6,
                        15,
                        2.4,
                    ],
                    'line-opacity': 0.9,
                },
            });
        }

        if (!this.map.getLayer('rdg-road-local')) {
            this.map.addLayer({
                id: 'rdg-road-local',
                type: 'line',
                source: 'rdg-local-basemap',
                filter: ['all', ['==', ['get', 'basemap_type'], 'road'], ['==', ['get', 'road_class'], 'local']],
                layout: {
                    'line-cap': 'round',
                    'line-join': 'round',
                },
                paint: {
                    'line-color': this.getCssColorVariable('--color-map-road-local'),
                    'line-width': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        9,
                        0.6,
                        12,
                        1.2,
                        15,
                        2.3,
                    ],
                    'line-opacity': 0.88,
                },
            });
        }

        if (!this.map.getLayer('rdg-road-secondary')) {
            this.map.addLayer({
                id: 'rdg-road-secondary',
                type: 'line',
                source: 'rdg-local-basemap',
                filter: ['all', ['==', ['get', 'basemap_type'], 'road'], ['==', ['get', 'road_class'], 'secondary']],
                layout: {
                    'line-cap': 'round',
                    'line-join': 'round',
                },
                paint: {
                    'line-color': this.getCssColorVariable('--color-map-road-secondary'),
                    'line-width': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        9,
                        1.1,
                        12,
                        2.1,
                        15,
                        3.8,
                    ],
                    'line-opacity': 0.92,
                },
            });
        }

        if (!this.map.getLayer('rdg-road-primary')) {
            this.map.addLayer({
                id: 'rdg-road-primary',
                type: 'line',
                source: 'rdg-local-basemap',
                filter: ['all', ['==', ['get', 'basemap_type'], 'road'], ['==', ['get', 'road_class'], 'primary']],
                layout: {
                    'line-cap': 'round',
                    'line-join': 'round',
                },
                paint: {
                    'line-color': this.getCssColorVariable('--color-map-road-primary-soft'),
                    'line-width': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        9,
                        1.5,
                        12,
                        2.9,
                        15,
                        5.0,
                    ],
                    'line-opacity': 0.96,
                },
            });
        }

        if (!this.map.getLayer('rdg-road-trunk')) {
            this.map.addLayer({
                id: 'rdg-road-trunk',
                type: 'line',
                source: 'rdg-local-basemap',
                filter: ['all', ['==', ['get', 'basemap_type'], 'road'], ['==', ['get', 'road_class'], 'trunk']],
                layout: {
                    'line-cap': 'round',
                    'line-join': 'round',
                },
                paint: {
                    'line-color': this.getCssColorVariable('--color-map-road-trunk'),
                    'line-width': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        9,
                        1.9,
                        12,
                        3.6,
                        15,
                        6.0,
                    ],
                    'line-opacity': 0.98,
                },
            });
        }

        if (!this.map.getLayer('rdg-place-points')) {
            this.map.addLayer({
                id: 'rdg-place-points',
                type: 'circle',
                source: 'rdg-local-basemap',
                filter: ['==', ['get', 'basemap_type'], 'place'],
                paint: {
                    'circle-color': this.getCssColorVariable('--color-map-place'),
                    'circle-stroke-color': this.getCssColorVariable('--color-map-stroke'),
                    'circle-stroke-width': 1,
                    'circle-radius': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        9,
                        1.8,
                        12,
                        2.8,
                        15,
                        4.2,
                    ],
                    'circle-opacity': 0.9,
                },
            });
        }
    }

    attachLayerSources() {
        if (!this.map) {
            return;
        }

        for (const layerConfig of this.layerConfigs) {
            const layerId = String(layerConfig.id || '');
            if (layerId === '') {
                continue;
            }

            const sourceId = `${layerId}-source`;
            const mapLayerId = `${layerId}-layer`;

            if (!this.map.getSource(sourceId)) {
                this.map.addSource(sourceId, {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: [],
                    },
                });
            }

            if (!this.map.getLayer(mapLayerId)) {
                const geometryType = String(layerConfig.geometryType || 'line');
                const layerDefinition = this.buildMapLayerDefinition(mapLayerId, sourceId, geometryType, layerConfig);
                this.map.addLayer(layerDefinition);
            }

            this.layerSources.set(layerId, sourceId);
        }
    }

    buildMapLayerDefinition(mapLayerId, sourceId, geometryType, layerConfig) {
        if (geometryType === 'point') {
            return {
                id: mapLayerId,
                type: 'circle',
                source: sourceId,
                paint: this.buildPointPaint(layerConfig),
                layout: {
                    visibility: this.activeLayerIds.has(String(layerConfig.id)) ? 'visible' : 'none',
                },
            };
        }

        return {
            id: mapLayerId,
            type: 'line',
            source: sourceId,
            paint: this.buildLinePaint(layerConfig),
            layout: {
                'line-cap': 'round',
                'line-join': 'round',
                visibility: this.activeLayerIds.has(String(layerConfig.id)) ? 'visible' : 'none',
            },
        };
    }

    buildLinePaint(layerConfig) {
        if (String(layerConfig.stylePreset || '') === 'roadNetwork') {
            return {
                'line-color': [
                    'match',
                    ['get', 'category'],
                    'nationale',
                    this.getCssColorVariable('--color-map-road-primary'),
                    'departementale',
                    this.getCssColorVariable('--color-map-layer-active'),
                    'communale',
                    this.getCssColorVariable('--color-map-road-secondary'),
                    this.getCssColorVariable('--color-map-road-primary'),
                ],
                'line-width': [
                    'interpolate',
                    ['linear'],
                    ['zoom'],
                    9,
                    2.2,
                    12,
                    4.2,
                    15,
                    6.2,
                ],
                'line-opacity': 0.9,
            };
        }

        return {
            'line-color': this.getCssColorVariable('--color-map-road-primary'),
            'line-width': 3.5,
            'line-opacity': 0.9,
        };
    }

    buildPointPaint(layerConfig) {
        if (String(layerConfig.stylePreset || '') === 'worksites') {
            return {
                'circle-color': [
                    'match',
                    ['get', 'status'],
                    'planned',
                    this.getCssColorVariable('--color-map-worksite-planned'),
                    'in_progress',
                    this.getCssColorVariable('--color-map-worksite-progress'),
                    'done',
                    this.getCssColorVariable('--color-map-worksite-done'),
                    this.getCssColorVariable('--color-map-worksite-default'),
                ],
                'circle-radius': [
                    'interpolate',
                    ['linear'],
                    ['zoom'],
                    9,
                    4.5,
                    12,
                    7,
                    15,
                    9.5,
                ],
                'circle-stroke-width': 1.5,
                'circle-stroke-color': this.getCssColorVariable('--color-map-stroke'),
                'circle-opacity': 0.95,
            };
        }

        return {
            'circle-color': this.getCssColorVariable('--color-map-worksite-progress'),
            'circle-radius': 6,
            'circle-stroke-width': 1.25,
            'circle-stroke-color': this.getCssColorVariable('--color-map-stroke'),
        };
    }

    async refreshDataAndLegend() {
        if (!this.map || !this.map.isStyleLoaded()) {
            return;
        }

        try {
            await this.refreshFeatures();
            await this.refreshLegend();
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            this.setStatus('Erreur lors du rafraichissement cartographique.');
        }
    }

    async refreshFeatures() {
        const activeLayerIds = this.getActiveLayerIds();
        if (activeLayerIds.length === 0) {
            this.clearAllSources();
            this.renderResultCount(0);
            this.setStatus('Aucune couche active.');
            return;
        }

        const params = new URLSearchParams();
        for (const layerId of activeLayerIds) {
            params.append('layers[]', layerId);
        }

        const statusValue = this.hasStatusFilterTarget ? this.statusFilterTarget.value : '';
        if (statusValue !== '') {
            params.set('status', statusValue);
        }

        const categoryValue = this.hasCategoryFilterTarget ? this.categoryFilterTarget.value : '';
        if (categoryValue !== '') {
            params.set('category', categoryValue);
        }

        const queryValue = this.hasSearchInputTarget ? this.searchInputTarget.value.trim() : '';
        if (queryValue !== '') {
            params.set('q', queryValue);
        }

        const url = `${this.featuresUrlValue}?${params.toString()}`;
        this.setStatus('Chargement des données cartographiques...');

        const payload = await this.fetchJson(url);
        const features = Array.isArray(payload.features) ? payload.features : [];
        const featuresByLayer = this.groupFeaturesByLayer(features);

        for (const layerConfig of this.layerConfigs) {
            const layerId = String(layerConfig.id || '');
            const sourceId = this.layerSources.get(layerId);
            if (!sourceId) {
                continue;
            }

            const source = this.map.getSource(sourceId);
            if (!source || typeof source.setData !== 'function') {
                continue;
            }

            source.setData({
                type: 'FeatureCollection',
                features: featuresByLayer.get(layerId) ?? [],
            });

            const mapLayerId = `${layerId}-layer`;
            if (this.map.getLayer(mapLayerId)) {
                this.map.setLayoutProperty(mapLayerId, 'visibility', this.activeLayerIds.has(layerId) ? 'visible' : 'none');
            }
        }

        const total = Number.isFinite(payload.total) ? Number(payload.total) : features.length;
        this.renderResultCount(total);
        this.setStatus(total === 0 ? 'Aucun résultat avec les filtres actifs.' : 'Données cartographiques à jour.');
    }

    async refreshLegend() {
        const params = new URLSearchParams();
        for (const layerId of this.getActiveLayerIds()) {
            params.append('layers[]', layerId);
        }

        const statusValue = this.hasStatusFilterTarget ? this.statusFilterTarget.value : '';
        if (statusValue !== '') {
            params.set('status', statusValue);
        }

        const categoryValue = this.hasCategoryFilterTarget ? this.categoryFilterTarget.value : '';
        if (categoryValue !== '') {
            params.set('category', categoryValue);
        }

        const queryValue = this.hasSearchInputTarget ? this.searchInputTarget.value.trim() : '';
        if (queryValue !== '') {
            params.set('q', queryValue);
        }

        const url = `${this.legendUrlValue}?${params.toString()}`;
        const payload = await this.fetchJson(url);
        const legendItems = Array.isArray(payload.items) ? payload.items : [];

        if (!this.hasLegendListTarget) {
            return;
        }

        if (legendItems.length === 0) {
            this.legendListTarget.innerHTML = '<li>Aucune légende disponible.</li>';
            return;
        }

        this.legendListTarget.innerHTML = legendItems
            .map((item) => {
                const color = this.escapeHtml(String(item.color || this.getCssColorVariable('--color-map-road-primary')));
                const label = this.escapeHtml(String(item.label || 'Element'));
                const count = Number.isFinite(item.count) ? Number(item.count) : 0;

                return `
                    <li>
                        <span class="map-legend-symbol" style="--legend-color:${color}"></span>
                        <strong>${label}</strong>
                        <small>${count} element(s)</small>
                    </li>
                `;
            })
            .join('');
    }

    async handleMapClick(event) {
        const activeLayerIds = this.getActiveLayerIds();
        if (activeLayerIds.length === 0) {
            return;
        }

        const params = new URLSearchParams();
        params.set('lng', String(event.lngLat.lng));
        params.set('lat', String(event.lngLat.lat));
        for (const layerId of activeLayerIds) {
            params.append('layers[]', layerId);
        }

        const url = `${this.featureInfoUrlValue}?${params.toString()}`;
        try {
            const payload = await this.fetchJson(url);
            if (!payload || !payload.feature) {
                this.renderFeatureInfoMessage('Aucun élément significatif à proximité du clic.');
                return;
            }

            const feature = payload.feature;
            const popupHtml = this.buildPopupHtml(feature, payload.distanceMeters);
            new window.maplibregl.Popup({ offset: 14 })
                .setLngLat([event.lngLat.lng, event.lngLat.lat])
                .setHTML(popupHtml)
                .addTo(this.map);

            this.renderFeatureInfoPanel(feature, payload.distanceMeters);
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            this.renderFeatureInfoMessage('Impossible de récupérer la fiche détaillée pour ce point.');
        }
    }

    renderLayerToggles() {
        if (!this.hasLayerListTarget) {
            return;
        }

        if (this.layerConfigs.length === 0) {
            this.layerListTarget.innerHTML = '<ul class="status-list"><li>Aucune couche configurée.</li></ul>';
            return;
        }

        const rows = this.layerConfigs
            .map((layer) => {
                const layerId = String(layer.id || '');
                const checked = this.activeLayerIds.has(layerId) ? 'checked' : '';
                const label = this.escapeHtml(String(layer.label || layerId));
                const geometryType = this.escapeHtml(String(layer.geometryType || 'line'));

                return `
                    <li>
                        <label class="map-layer-toggle">
                            <input type="checkbox" data-layer-id="${this.escapeHtml(layerId)}" ${checked}>
                            <span><strong>${label}</strong><br><small>${geometryType.toUpperCase()}</small></span>
                        </label>
                    </li>
                `;
            })
            .join('');

        this.layerListTarget.innerHTML = `<ul class="status-list">${rows}</ul>`;
        this.layerListTarget.querySelectorAll('input[type="checkbox"][data-layer-id]').forEach((checkbox) => {
            checkbox.addEventListener('change', (event) => {
                const target = event.currentTarget;
                if (!(target instanceof HTMLInputElement)) {
                    return;
                }

                const layerId = target.dataset.layerId;
                if (!layerId) {
                    return;
                }

                if (target.checked) {
                    this.activeLayerIds.add(layerId);
                } else {
                    this.activeLayerIds.delete(layerId);
                }

                this.refreshDataAndLegend();
            });
        });
    }

    renderResultCount(total) {
        if (!this.hasResultCountTarget) {
            return;
        }

        this.resultCountTarget.textContent = `${total} element(s) visible(s)`;
    }

    renderFeatureInfoMessage(message) {
        if (!this.hasFeatureInfoTarget) {
            return;
        }

        this.featureInfoTarget.innerHTML = `<p class="mb-0">${this.escapeHtml(message)}</p>`;
    }

    renderFeatureInfoPanel(feature, distanceMeters) {
        if (!this.hasFeatureInfoTarget) {
            return;
        }

        const metadata = feature.metadata && typeof feature.metadata === 'object' ? feature.metadata : {};
        const metadataRows = Object.entries(metadata)
            .map(([key, value]) => `<li><strong>${this.escapeHtml(key)}</strong>: ${this.escapeHtml(String(value))}</li>`)
            .join('');

        this.featureInfoTarget.innerHTML = `
            <p class="mb-2"><strong>${this.escapeHtml(String(feature.title || 'Element cartographique'))}</strong></p>
            <p class="mb-2">Commune: ${this.escapeHtml(String(feature.commune || 'n/a'))}</p>
            <p class="mb-2">Categorie: ${this.escapeHtml(String(feature.category || 'n/a'))} | Statut: ${this.escapeHtml(String(feature.status || 'n/a'))}</p>
            <p class="mb-2">${this.escapeHtml(String(feature.description || ''))}</p>
            <p class="mb-2">Distance du clic: ${this.escapeHtml(String(distanceMeters))} m</p>
            ${metadataRows !== '' ? `<ul class="map-feature-meta">${metadataRows}</ul>` : ''}
        `;
    }

    buildPopupHtml(feature, distanceMeters) {
        return `
            <div class="map-popup">
                <p><strong>${this.escapeHtml(String(feature.title || 'Element cartographique'))}</strong></p>
                <p>${this.escapeHtml(String(feature.commune || 'n/a'))}</p>
                <p>${this.escapeHtml(String(feature.category || 'n/a'))} | ${this.escapeHtml(String(feature.status || 'n/a'))}</p>
                <p>${this.escapeHtml(String(feature.description || ''))}</p>
                <p>Distance: ${this.escapeHtml(String(distanceMeters))} m</p>
            </div>
        `;
    }

    populateFilterOptions(filters) {
        if (this.hasStatusFilterTarget) {
            const statuses = Array.isArray(filters.statuses) ? filters.statuses : [];
            this.statusFilterTarget.innerHTML = `
                <option value="">Tous statuts</option>
                ${statuses.map((status) => `<option value="${this.escapeHtml(String(status))}">${this.escapeHtml(String(status))}</option>`).join('')}
            `;
        }

        if (this.hasCategoryFilterTarget) {
            const categories = Array.isArray(filters.categories) ? filters.categories : [];
            this.categoryFilterTarget.innerHTML = `
                <option value="">Toutes catégories</option>
                ${categories.map((category) => `<option value="${this.escapeHtml(String(category))}">${this.escapeHtml(String(category))}</option>`).join('')}
            `;
        }
    }

    clearAllSources() {
        if (!this.map) {
            return;
        }

        for (const [layerId, sourceId] of this.layerSources.entries()) {
            const source = this.map.getSource(sourceId);
            if (source && typeof source.setData === 'function') {
                source.setData({
                    type: 'FeatureCollection',
                    features: [],
                });
            }

            const mapLayerId = `${layerId}-layer`;
            if (this.map.getLayer(mapLayerId)) {
                this.map.setLayoutProperty(mapLayerId, 'visibility', 'none');
            }
        }
    }

    getActiveLayerIds() {
        return Array.from(this.activeLayerIds.values());
    }

    groupFeaturesByLayer(features) {
        const grouped = new Map();
        for (const feature of features) {
            const layerId = String(feature?.properties?.layer_id || '');
            if (layerId === '') {
                continue;
            }

            if (!grouped.has(layerId)) {
                grouped.set(layerId, []);
            }

            grouped.get(layerId).push(feature);
        }

        return grouped;
    }

    normalizeBasemapPayload(payload) {
        if (payload && payload.type === 'FeatureCollection' && Array.isArray(payload.features)) {
            return payload;
        }

        return EMPTY_FEATURE_COLLECTION;
    }

    normalizeBasemapConfig(rawConfig) {
        if (!rawConfig || String(rawConfig.type || '').toLowerCase() !== 'raster') {
            return null;
        }

        const tiles = Array.isArray(rawConfig.tiles)
            ? rawConfig.tiles.map((tileUrl) => String(tileUrl || '').trim()).filter((tileUrl) => tileUrl !== '')
            : [];
        if (tiles.length === 0) {
            return null;
        }

        return {
            provider: String(rawConfig.provider || 'custom'),
            type: 'raster',
            tiles,
            tileSize: Number.isFinite(rawConfig.tileSize) ? Number(rawConfig.tileSize) : 256,
            maxZoom: Number.isFinite(rawConfig.maxZoom) ? Number(rawConfig.maxZoom) : 19,
            attribution: String(rawConfig.attribution || ''),
        };
    }

    async fetchJson(url, options = {}) {
        const abortable = options.abortable !== false;
        let signal = undefined;

        if (abortable) {
            if (this.abortController) {
                this.abortController.abort();
            }
            this.abortController = new AbortController();
            signal = this.abortController.signal;
        }

        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            signal,
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    }

    async ensureMapLibreReady() {
        if (window.maplibregl) {
            if (typeof window.maplibregl.setWorkerUrl === 'function') {
                window.maplibregl.setWorkerUrl(MAPLIBRE_WORKER_URL);
            }
            return;
        }

        if (!document.querySelector('link[data-maplibre-css]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = MAPLIBRE_CSS_URL;
            link.setAttribute('data-maplibre-css', '1');
            document.head.appendChild(link);
        }

        if (!maplibreLoaderPromise) {
            maplibreLoaderPromise = new Promise((resolve, reject) => {
                const existingScript = document.querySelector('script[data-maplibre-js]');
                if (existingScript instanceof HTMLScriptElement) {
                    existingScript.addEventListener('load', () => resolve());
                    existingScript.addEventListener('error', () => reject(new Error('Impossible de charger MapLibre.')));
                    return;
                }

                const script = document.createElement('script');
                script.src = MAPLIBRE_JS_URL;
                script.async = true;
                script.defer = true;
                script.setAttribute('data-maplibre-js', '1');
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('Impossible de charger MapLibre.'));
                document.head.appendChild(script);
            });
        }

        await maplibreLoaderPromise;

        if (window.maplibregl && typeof window.maplibregl.setWorkerUrl === 'function') {
            window.maplibregl.setWorkerUrl(MAPLIBRE_WORKER_URL);
        }
    }

    setStatus(message) {
        if (!this.hasStatusTarget) {
            return;
        }

        this.statusTarget.textContent = message;
    }

    escapeHtml(value) {
        return value
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
}
