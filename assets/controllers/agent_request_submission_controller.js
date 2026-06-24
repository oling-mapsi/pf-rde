import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['structureType', 'centerField', 'urgencyLevel', 'urgencyField', 'requestKindInput', 'dataSection', 'mapSection'];

    connect() {
        this.refresh();
    }

    refresh() {
        const structureType = this.hasStructureTypeTarget ? this.structureTypeTarget.value : '';
        const urgencyLevel = this.hasUrgencyLevelTarget ? this.urgencyLevelTarget.value : 'normal';
        const requestKinds = this.hasRequestKindInputTarget
            ? this.requestKindInputTargets.filter((input) => input.checked).map((input) => input.value)
            : [];

        this.centerFieldTargets.forEach((field) => {
            field.hidden = structureType !== 'Centre Routier';
        });

        this.urgencyFieldTargets.forEach((field) => {
            field.hidden = !['urgent', 'very_urgent'].includes(urgencyLevel);
        });

        this.dataSectionTargets.forEach((field) => {
            field.hidden = !requestKinds.includes('data');
        });

        this.mapSectionTargets.forEach((field) => {
            field.hidden = !requestKinds.includes('map');
        });
    }
}
