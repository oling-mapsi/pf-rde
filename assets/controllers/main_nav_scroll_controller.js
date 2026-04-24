import { Controller } from '@hotwired/stimulus';

const DEFAULT_MIN_DELTA = 8;
const DEFAULT_REVEAL_AT = 28;

export default class extends Controller {
    static targets = ['menu'];
    static values = {
        minDelta: Number,
        revealAt: Number,
    };

    connect() {
        this.lastScrollY = window.scrollY;
        this.rafId = null;
        this.isMenuHidden = false;

        this.onScrollHandler = this.onScroll.bind(this);
        this.onFocusInHandler = this.showMenu.bind(this);

        window.addEventListener('scroll', this.onScrollHandler, { passive: true });
        this.element.addEventListener('focusin', this.onFocusInHandler);
    }

    disconnect() {
        window.removeEventListener('scroll', this.onScrollHandler);
        this.element.removeEventListener('focusin', this.onFocusInHandler);

        if (this.rafId !== null) {
            window.cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
    }

    onScroll() {
        if (this.rafId !== null) {
            return;
        }

        this.rafId = window.requestAnimationFrame(() => {
            this.updateMenuVisibility();
            this.rafId = null;
        });
    }

    updateMenuVisibility() {
        if (!this.hasMenuTarget) {
            return;
        }

        const currentScrollY = window.scrollY;
        const delta = currentScrollY - this.lastScrollY;
        const minDelta = this.hasMinDeltaValue ? this.minDeltaValue : DEFAULT_MIN_DELTA;
        const revealAt = this.hasRevealAtValue ? this.revealAtValue : DEFAULT_REVEAL_AT;

        if (currentScrollY <= revealAt) {
            this.showMenu();
            this.lastScrollY = currentScrollY;
            return;
        }

        if (delta > minDelta) {
            this.hideMenu();
        } else if (delta < -minDelta) {
            this.showMenu();
        }

        this.lastScrollY = currentScrollY;
    }

    hideMenu() {
        if (!this.hasMenuTarget || this.isMenuHidden) {
            return;
        }

        this.menuTarget.classList.add('is-collapsed');
        this.isMenuHidden = true;
    }

    showMenu() {
        if (!this.hasMenuTarget || !this.isMenuHidden) {
            return;
        }

        this.menuTarget.classList.remove('is-collapsed');
        this.isMenuHidden = false;
    }
}
