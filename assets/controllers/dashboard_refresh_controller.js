import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['list'];
    static values = { url: String };

    connect() {
        this.refresh();
        this.interval = setInterval(() => this.refresh(), 20000);
    }

    disconnect() {
        clearInterval(this.interval);
    }

    async refresh() {
        const response = await fetch(this.urlValue, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        this.listTarget.innerHTML = payload.metrics
            .slice(0, 8)
            .map(
                (metric) => `<li><strong>${metric.metricKey}</strong> <span>${metric.valueInteger ?? metric.valueNumeric ?? '-'} </span></li>`
            )
            .join('');
    }
}
