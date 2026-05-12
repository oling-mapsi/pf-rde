import { Controller } from '@hotwired/stimulus';

const DEBOUNCE_MS = 280;

export default class extends Controller {
    static targets = ['form', 'results', 'status', 'suggestions', 'activeFilters'];
    static values = {
        autocompleteUrl: String,
        suggestionsLimit: { type: Number, default: 6 },
    };

    connect() {
        this.timeout = null;
        this.requestId = 0;
        this.suggestionRequestId = 0;
        this.activeFetchController = null;
        this.suggestionFetchController = null;
        this.lastRequestUrl = null;
        this.syncActiveFilters();
    }

    disconnect() {
        clearTimeout(this.timeout);
        if (this.activeFetchController) {
            this.activeFetchController.abort();
        }
        if (this.suggestionFetchController) {
            this.suggestionFetchController.abort();
        }
    }

    submit(event) {
        event.preventDefault();
        this.refresh(true);
    }

    typeSearch() {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.refresh(true);
            this.fetchSuggestions();
        }, DEBOUNCE_MS);
    }

    async changeFilter(event) {
        if (event?.target instanceof HTMLInputElement && event.target.name === 'q') {
            return;
        }

        await this.refresh(true);

        if (
            event?.target instanceof HTMLInputElement
            && ['type[]', 'theme[]', 'category[]'].includes(event.target.name)
        ) {
            this.fetchSuggestions();
        }
    }

    async resetFilters(event) {
        event.preventDefault();
        this.formTarget.reset();
        if (this.hasSuggestionsTarget) {
            this.suggestionsTarget.innerHTML = '';
        }
        await this.refreshFromUrl(new URL(this.formTarget.action, window.location.origin), true);
    }

    async retry(event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest('[data-catalog-retry]');
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        event.preventDefault();
        if (this.lastRequestUrl instanceof URL) {
            await this.refreshFromUrl(new URL(this.lastRequestUrl.toString()), true);
            return;
        }

        await this.refresh(true);
    }

    async paginate(event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('[data-page-link]');
        if (!(link instanceof HTMLAnchorElement)) {
            return;
        }

        event.preventDefault();
        const url = new URL(link.href);

        await this.refreshFromUrl(url, true);
    }

    async refresh(pushState = false) {
        const form = this.formTarget;
        const formData = new FormData(form);
        const url = new URL(form.action || window.location.href, window.location.origin);
        url.search = '';

        for (const [key, value] of formData.entries()) {
            if (typeof value !== 'string' || value.trim() === '') {
                continue;
            }
            if (key.endsWith('[]')) {
                url.searchParams.append(key, value);
            } else {
                url.searchParams.set(key, value);
            }
        }

        await this.refreshFromUrl(url, pushState);
    }

    async refreshFromUrl(url, pushState = true) {
        this.setStatus('Chargement...');
        this.resultsTarget.setAttribute('aria-busy', 'true');
        url.searchParams.set('partial', '1');
        const currentRequestId = ++this.requestId;
        this.lastRequestUrl = new URL(url.toString());
        this.renderSkeletonState();

        if (this.activeFetchController) {
            this.activeFetchController.abort();
        }
        this.activeFetchController = new AbortController();

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: this.activeFetchController.signal,
            });

            if (!response.ok) {
                this.setStatus('Erreur de chargement.');
                if (currentRequestId === this.requestId) {
                    this.renderErrorState();
                }
                return;
            }

            const html = await response.text();
            if (currentRequestId !== this.requestId) {
                return;
            }

            this.resultsTarget.innerHTML = html;
            this.setStatus('');
            this.syncActiveFilters();

            if (pushState) {
                const cleanUrl = new URL(url.toString());
                cleanUrl.searchParams.delete('partial');
                window.history.replaceState({}, '', cleanUrl);
            }
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }
            this.setStatus('Erreur de chargement.');
            if (currentRequestId === this.requestId) {
                this.renderErrorState();
            }
        } finally {
            if (currentRequestId === this.requestId) {
                this.resultsTarget.removeAttribute('aria-busy');
            }
        }
    }

    async fetchSuggestions() {
        if (!this.hasSuggestionsTarget) {
            return;
        }

        const query = this.formTarget.querySelector('[name="q"]')?.value?.trim();
        if (!query || query.length < 2) {
            if (this.suggestionFetchController) {
                this.suggestionFetchController.abort();
            }
            this.suggestionsTarget.innerHTML = '';
            return;
        }

        const autocompleteUrl = this.hasAutocompleteUrlValue && this.autocompleteUrlValue !== ''
            ? this.autocompleteUrlValue
            : '/api/static-maps/autocomplete';
        const url = new URL(autocompleteUrl, window.location.origin);
        url.searchParams.set('q', query);

        this.formTarget.querySelectorAll('input[name="type[]"]:checked').forEach((input) => {
            url.searchParams.append('type[]', input.value);
        });
        this.formTarget.querySelectorAll('input[name="theme[]"]:checked').forEach((input) => {
            url.searchParams.append('theme[]', input.value);
        });
        this.formTarget.querySelectorAll('input[name="category[]"]:checked').forEach((input) => {
            url.searchParams.append('category[]', input.value);
        });

        const currentSuggestionRequestId = ++this.suggestionRequestId;

        if (this.suggestionFetchController) {
            this.suggestionFetchController.abort();
        }
        this.suggestionFetchController = new AbortController();

        try {
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                signal: this.suggestionFetchController.signal,
            });
            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            if (currentSuggestionRequestId !== this.suggestionRequestId) {
                return;
            }

            const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];
            this.suggestionsTarget.innerHTML = suggestions
                .slice(0, this.suggestionsLimitValue)
                .map(
                    (item) => {
                        const href = item.url ? String(item.url) : '#';
                        const title = this.escapeHtml(item.title || 'Ressource');
                        const typeLabel = this.escapeHtml(item.typeLabel || 'Ressource');
                        const themeBadge = item.theme
                            ? ` <span class="badge badge--theme">${this.escapeHtml(item.theme)}</span>`
                            : '';
                        return `<li><a href="${this.escapeHtml(href)}">${title}</a> <span class="badge badge--neutral">${typeLabel}</span>${themeBadge}</li>`;
                    }
                )
                .join('');
        } catch (error) {
            if (!(error instanceof DOMException && error.name === 'AbortError')) {
                this.suggestionsTarget.innerHTML = '';
            }
        }
    }

    setStatus(message) {
        if (!this.hasStatusTarget) {
            return;
        }

        this.statusTarget.textContent = message;
    }

    syncActiveFilters() {
        if (!this.hasActiveFiltersTarget) {
            return;
        }

        const query = this.formTarget.querySelector('[name="q"]')?.value?.trim() ?? '';
        const themes = Array.from(this.formTarget.querySelectorAll('input[name="theme[]"]:checked'))
            .map((input) => input.value?.trim() ?? '')
            .filter((value) => value !== '');
        const types = Array.from(this.formTarget.querySelectorAll('input[name="type[]"]:checked'))
            .map((input) => input.value?.trim() ?? '')
            .filter((value) => value !== '');
        const categories = Array.from(this.formTarget.querySelectorAll('input[name="category[]"]:checked'))
            .map((input) => ({
                value: input.value?.trim() ?? '',
                label: input.closest('label')?.querySelector('span')?.textContent?.trim() ?? '',
            }))
            .filter((category) => category.value !== '');
        const perPage = this.formTarget.querySelector('[name="per_page"]')?.value?.trim() ?? '';

        const chips = [];
        if (query !== '') {
            chips.push(`<span class="filter-chip"><span class="filter-chip__key">Recherche:</span> ${this.escapeHtml(query)}</span>`);
        }
        const typeLabels = {
            cartography_link: 'Lien vers cartographie',
            interactive: 'Lien vers cartographie',
            wms: 'WMS',
            wfs: 'WFS',
            data_file: 'Fichier de données',
            static_map: 'Carte statique',
            static: 'Carte statique',
        };
        types.forEach((type) => {
            const typeLabel = typeLabels[type] || type;
            chips.push(`<span class="filter-chip"><span class="filter-chip__key">Type:</span> ${this.escapeHtml(typeLabel)}</span>`);
        });
        categories.forEach((category) => {
            const label = category.label.replace(/\s+\(\d+\)$/, '');
            chips.push(`<span class="filter-chip"><span class="filter-chip__key">Catégorie:</span> ${this.escapeHtml(label)}</span>`);
        });
        themes.forEach((theme) => {
            chips.push(`<span class="filter-chip"><span class="filter-chip__key">Thème:</span> ${this.escapeHtml(theme)}</span>`);
        });
        if (perPage !== '' && perPage !== '9') {
            chips.push(`<span class="filter-chip"><span class="filter-chip__key">Par page:</span> ${this.escapeHtml(perPage)}</span>`);
        }

        this.activeFiltersTarget.innerHTML = chips.length > 0 ? chips.join('') : '<span class="text-muted">Aucun filtre actif.</span>';
    }

    renderSkeletonState() {
        const cards = new Array(6)
            .fill(0)
            .map(
                () => `
                    <article class="card card--dataset skeleton-card" aria-hidden="true">
                        <div class="skeleton-block skeleton-block--chip"></div>
                        <div class="skeleton-block skeleton-block--title"></div>
                        <div class="skeleton-block skeleton-block--line"></div>
                        <div class="skeleton-block skeleton-block--line short"></div>
                        <div class="skeleton-block skeleton-block--meta"></div>
                    </article>`
            )
            .join('');

        this.resultsTarget.innerHTML = `<div class="grid grid-3 skeleton-grid">${cards}</div>`;
    }

    renderErrorState() {
        this.resultsTarget.innerHTML = `
            <div class="data-state data-state--error" role="status" aria-live="polite">
                <h2>Chargement indisponible</h2>
                <p>Impossible de récupérer les résultats pour le moment. Vérifiez votre connexion puis réessayez.</p>
                <p class="data-state__actions">
                    <a href="#" class="btn btn-outline" data-catalog-retry>Réessayer</a>
                </p>
            </div>`;
    }

    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }
}
