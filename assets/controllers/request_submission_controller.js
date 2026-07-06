import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['requesterType', 'professionalField', 'professionalSection', 'requestKindInput', 'dataSection', 'mapSection', 'deliverySection'];

    connect() {
        this.refresh();
    }

    refresh() {
        const isProfessional = this.currentRequesterType() === 'professional';
        this.professionalFieldTargets.forEach((field) => {
            field.hidden = !isProfessional;
        });
        this.professionalSectionTargets.forEach((section) => {
            section.hidden = !isProfessional;
        });

        const selectedKinds = this.selectedRequestKinds();
        const wantsData = selectedKinds.includes('data');
        const wantsMap = selectedKinds.includes('map');
        const hasDeliveryOptions = wantsData || wantsMap;

        this.dataSectionTargets.forEach((field) => {
            field.hidden = !wantsData;
        });

        this.mapSectionTargets.forEach((field) => {
            field.hidden = !wantsMap;
        });

        this.deliverySectionTargets.forEach((section) => {
            section.hidden = !hasDeliveryOptions;
        });
    }

    currentRequesterType() {
        if (!this.hasRequesterTypeTarget) {
            return 'professional';
        }

        return this.requesterTypeTarget.value || 'usager';
    }

    selectedRequestKinds() {
        if (!this.hasRequestKindInputTarget) {
            return [];
        }

        return this.requestKindInputTargets
            .filter((input) => input.checked)
            .map((input) => input.value);
    }
}
