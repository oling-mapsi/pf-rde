import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['feedback'];

    async submit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const response = await fetch(form.action, {
            method: form.method,
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json();

        if (!response.ok || !payload.ok) {
            this.feedbackTarget.className = 'notice notice-error';
            this.feedbackTarget.textContent = (payload.errors || ['Erreur de soumission.']).join(' ');
            return;
        }

        this.feedbackTarget.className = 'notice notice-success';
        this.feedbackTarget.textContent = payload.message || 'Envoye.';
        form.reset();
    }
}
