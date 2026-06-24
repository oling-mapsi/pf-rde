import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['requesterType', 'professionalField', 'requestKindInput', 'dataSection', 'mapSection'];

    connect() {
        this.refresh();
    }

    refresh() {
        const isProfessional = this.currentRequesterType() === 'professional';
        this.professionalFieldTargets.forEach((field) => {
            field.hidden = !isProfessional;
        });

        const selectedKinds = this.selectedRequestKinds();
        const wantsData = selectedKinds.includes('data');
        const wantsMap = selectedKinds.includes('map');

        this.dataSectionTargets.forEach((field) => {
            field.hidden = !wantsData;
        });

        this.mapSectionTargets.forEach((field) => {
            field.hidden = !wantsMap;
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
