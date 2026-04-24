import { Controller } from '@hotwired/stimulus';

const DEBOUNCE_MS = 280;

export default class extends Controller {
    static targets = ['form', 'results', 'status', 'suggestions'];

    connect() {
        this.timeout = null;
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

    async changeFilter() {
        await this.refresh(true);
    }

    async paginate(event) {
        if (!(event.target instanceof HTMLAnchorElement)) {
            return;
        }

        if (!event.target.matches('[data-page-link]')) {
            return;
        }

        event.preventDefault();
        const url = new URL(event.target.href);

        this.refreshFromUrl(url);
    }

    async refresh(pushState = false) {
        const form = this.formTarget;
        const formData = new FormData(form);
        const url = new URL(form.action || window.location.href, window.location.origin);

        for (const [key, value] of formData.entries()) {
            if (typeof value !== 'string' || value.trim() === '') {
                continue;
            }
            url.searchParams.set(key, value);
        }

        await this.refreshFromUrl(url, pushState);
    }

    async refreshFromUrl(url, pushState = true) {
        this.setStatus('Chargement...');
        url.searchParams.set('partial', '1');

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            this.setStatus('Erreur de chargement.');
            return;
        }

        const html = await response.text();
        this.resultsTarget.innerHTML = html;
        this.setStatus('');

        if (pushState) {
            const cleanUrl = new URL(url.toString());
            cleanUrl.searchParams.delete('partial');
            window.history.replaceState({}, '', cleanUrl);
        }
    }

    async fetchSuggestions() {
        if (!this.hasSuggestionsTarget) {
            return;
        }

        const query = this.formTarget.querySelector('[name="q"]')?.value?.trim();
        if (!query || query.length < 2) {
            this.suggestionsTarget.innerHTML = '';
            return;
        }

        const url = new URL('/api/static-maps/autocomplete', window.location.origin);
        url.searchParams.set('q', query);

        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        this.suggestionsTarget.innerHTML = payload.suggestions
            .slice(0, 6)
            .map(
                (item) =>
                    `<li><a href="/cartotheque/${item.slug}">${item.title}</a>${item.theme ? ` <span class="badge">${item.theme}</span>` : ''}</li>`
            )
            .join('');
    }

    setStatus(message) {
        if (!this.hasStatusTarget) {
            return;
        }

        this.statusTarget.textContent = message;
    }
}
