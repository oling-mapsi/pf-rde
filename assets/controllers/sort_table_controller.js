import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['table'];

    connect() {
        this.sortedColumnIndex = null;
        this.sortedDirection = 'asc';
    }

    sort(event) {
        const columnIndex = Number(event.params.index);
        const type = String(event.params.type || 'text');
        if (!Number.isInteger(columnIndex) || columnIndex < 0 || !this.hasTableTarget) {
            return;
        }

        const table = this.tableTarget;
        const tbody = table.tBodies.item(0);
        if (!tbody) {
            return;
        }

        const rows = Array.from(tbody.rows);
        if (rows.length < 2) {
            return;
        }

        const nextDirection = this.sortedColumnIndex === columnIndex && this.sortedDirection === 'asc' ? 'desc' : 'asc';
        const directionFactor = nextDirection === 'asc' ? 1 : -1;

        rows.sort((rowA, rowB) => {
            const valueA = this.getCellValue(rowA, columnIndex, type);
            const valueB = this.getCellValue(rowB, columnIndex, type);

            if (typeof valueA === 'number' && typeof valueB === 'number') {
                if (valueA === valueB) {
                    return 0;
                }
                return valueA > valueB ? directionFactor : -directionFactor;
            }

            const comparison = String(valueA).localeCompare(String(valueB), 'fr', {
                sensitivity: 'base',
                numeric: true,
            });
            return comparison * directionFactor;
        });

        rows.forEach((row) => tbody.appendChild(row));

        this.sortedColumnIndex = columnIndex;
        this.sortedDirection = nextDirection;
        this.updateHeaderState(columnIndex, nextDirection);
    }

    getCellValue(row, columnIndex, type) {
        const cell = row.cells.item(columnIndex);
        const raw = cell?.textContent?.trim() ?? '';
        if (type === 'number') {
            const numeric = Number.parseFloat(raw.replace(/[^\d.-]/g, ''));
            return Number.isFinite(numeric) ? numeric : Number.NEGATIVE_INFINITY;
        }

        if (type === 'date') {
            const timestamp = this.toTimestamp(raw);
            return timestamp ?? Number.NEGATIVE_INFINITY;
        }

        return raw.toLowerCase();
    }

    toTimestamp(raw) {
        const match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}))?$/);
        if (!match) {
            return null;
        }

        const [, day, month, year, hour = '00', minute = '00'] = match;
        const parsed = new Date(
            Number(year),
            Number(month) - 1,
            Number(day),
            Number(hour),
            Number(minute),
            0
        ).getTime();

        return Number.isNaN(parsed) ? null : parsed;
    }

    updateHeaderState(activeIndex, direction) {
        const headers = this.tableTarget.querySelectorAll('thead th');
        headers.forEach((header, index) => {
            const button = header.querySelector('.table-sort__button');
            if (!button) {
                return;
            }

            if (index === activeIndex) {
                header.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');
                button.setAttribute('aria-sort-state', direction);
                button.setAttribute('aria-pressed', 'true');
                return;
            }

            header.setAttribute('aria-sort', 'none');
            button.setAttribute('aria-sort-state', 'none');
            button.setAttribute('aria-pressed', 'false');
        });
    }
}
