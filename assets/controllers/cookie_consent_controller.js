import { Controller } from '@hotwired/stimulus';

const CONSENT_COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24 * 180;
const CONSENT_VERSION = 1;

export default class extends Controller {
    static targets = ['banner', 'manageButton', 'analyticsInput', 'servicesInput', 'details', 'firstAction'];
    static values = {
        enabled: Boolean,
        cookieName: String,
        matomoEnabled: Boolean,
        matomoSiteId: Number,
        matomoBaseUrl: String,
    };

    connect() {
        if (!this.enabledValue) {
            this.element.remove();
            return;
        }

        this.openFromEventHandler = this.openPreferences.bind(this);
        this.onKeydownHandler = this.onKeydown.bind(this);
        this.onOutsideClickHandler = this.onOutsideClick.bind(this);
        window.addEventListener('cookie-consent:open', this.openFromEventHandler);
        window.addEventListener('keydown', this.onKeydownHandler);
        this.element.addEventListener('mousedown', this.onOutsideClickHandler);

        const consent = this.readConsent();
        if (consent === null) {
            this.openBanner({ showDetails: false });
            this.toggleManageButton(false);
            return;
        }

        this.applyConsent(consent);
        this.closeBanner();
        this.toggleManageButton(true);
    }

    disconnect() {
        if (this.openFromEventHandler) {
            window.removeEventListener('cookie-consent:open', this.openFromEventHandler);
        }
        if (this.onKeydownHandler) {
            window.removeEventListener('keydown', this.onKeydownHandler);
        }
        if (this.onOutsideClickHandler) {
            this.element.removeEventListener('mousedown', this.onOutsideClickHandler);
        }
    }

    acceptAll() {
        this.saveConsent({
            analytics: true,
            services: true,
        });
    }

    rejectOptional() {
        this.saveConsent({
            analytics: false,
            services: false,
        });
    }

    openPreferences(event) {
        const showDetailsFromEvent = event?.detail?.showDetails;
        const consent = this.readConsent() ?? this.defaultConsent();
        this.syncForm(consent);
        this.openBanner({
            showDetails: typeof showDetailsFromEvent === 'boolean' ? showDetailsFromEvent : true,
        });
    }

    showDetails() {
        if (this.hasDetailsTarget) {
            this.detailsTarget.hidden = false;
        }
    }

    savePreferences() {
        this.saveConsent({
            analytics: this.hasAnalyticsInputTarget ? this.analyticsInputTarget.checked : false,
            services: this.hasServicesInputTarget ? this.servicesInputTarget.checked : false,
        });
    }

    closePreferences() {
        const consent = this.readConsent();
        if (consent === null) {
            this.saveConsent({
                analytics: false,
                services: false,
            });
            return;
        }

        this.closeBanner();
    }

    saveConsent(rawConsent) {
        const consent = this.normalizeConsent(rawConsent);
        this.writeConsent(consent);
        this.applyConsent(consent);
        this.closeBanner();
        this.toggleManageButton(true);
    }

    applyConsent(consent) {
        if (consent.analytics) {
            this.enableMatomoTracking();
        }

        document.dispatchEvent(
            new CustomEvent('cookie-consent:updated', {
                detail: consent,
            }),
        );
    }

    openBanner({ showDetails }) {
        this.lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        this.element.classList.add('is-open');
        document.body.classList.add('has-cookie-modal-open');
        if (this.hasBannerTarget) {
            this.bannerTarget.hidden = false;
        }
        if (this.hasDetailsTarget) {
            this.detailsTarget.hidden = !showDetails;
        }

        this.focusFirstAction();
    }

    closeBanner() {
        this.element.classList.remove('is-open');
        document.body.classList.remove('has-cookie-modal-open');
        if (this.hasBannerTarget) {
            this.bannerTarget.hidden = true;
        }
        if (this.hasDetailsTarget) {
            this.detailsTarget.hidden = true;
        }

        if (this.lastFocusedElement && document.contains(this.lastFocusedElement)) {
            this.lastFocusedElement.focus();
        }
    }

    toggleManageButton(visible) {
        if (!this.hasManageButtonTarget) {
            return;
        }

        this.manageButtonTarget.hidden = !visible;
    }

    syncForm(consent) {
        if (this.hasAnalyticsInputTarget) {
            this.analyticsInputTarget.checked = Boolean(consent.analytics);
        }
        if (this.hasServicesInputTarget) {
            this.servicesInputTarget.checked = Boolean(consent.services);
        }
    }

    readConsent() {
        const rawCookie = this.readCookieValue(this.resolvedCookieName());
        if (rawCookie === null) {
            return null;
        }

        try {
            const parsed = JSON.parse(decodeURIComponent(rawCookie));
            if (typeof parsed !== 'object' || parsed === null) {
                return null;
            }
            return this.normalizeConsent(parsed);
        } catch (_error) {
            return null;
        }
    }

    writeConsent(consent) {
        const secureSuffix = window.location.protocol === 'https:' ? '; Secure' : '';
        const value = encodeURIComponent(JSON.stringify(consent));
        document.cookie = `${this.resolvedCookieName()}=${value}; Max-Age=${CONSENT_COOKIE_MAX_AGE_SECONDS}; Path=/; SameSite=Lax${secureSuffix}`;
    }

    readCookieValue(name) {
        const encodedName = `${name}=`;
        const cookies = document.cookie.split(';');
        for (const cookiePart of cookies) {
            const trimmed = cookiePart.trim();
            if (trimmed.startsWith(encodedName)) {
                return trimmed.substring(encodedName.length);
            }
        }

        return null;
    }

    normalizeConsent(source) {
        return {
            version: CONSENT_VERSION,
            necessary: true,
            analytics: Boolean(source.analytics),
            services: Boolean(source.services),
            timestamp: new Date().toISOString(),
        };
    }

    defaultConsent() {
        return {
            version: CONSENT_VERSION,
            necessary: true,
            analytics: false,
            services: false,
            timestamp: new Date().toISOString(),
        };
    }

    resolvedCookieName() {
        return this.cookieNameValue || 'rdg_cookie_consent';
    }

    focusFirstAction() {
        if (this.hasFirstActionTarget) {
            this.firstActionTarget.focus();
            return;
        }

        const focusable = this.bannerTarget?.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable instanceof HTMLElement) {
            focusable.focus();
        }
    }

    onKeydown(event) {
        if (!this.element.classList.contains('is-open')) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            this.closePreferences();
        }
    }

    onOutsideClick(event) {
        if (!this.element.classList.contains('is-open') || !this.hasBannerTarget) {
            return;
        }

        if (this.bannerTarget.contains(event.target)) {
            return;
        }

        this.closePreferences();
    }

    enableMatomoTracking() {
        if (!this.matomoEnabledValue) {
            return;
        }

        if (document.querySelector('script[data-cookie-consent-matomo]')) {
            return;
        }

        const baseUrl = (this.matomoBaseUrlValue || '').trim();
        const siteId = this.matomoSiteIdValue;
        if (baseUrl === '' || !Number.isInteger(siteId) || siteId <= 0) {
            return;
        }

        const normalizedBaseUrl = baseUrl.endsWith('/') ? baseUrl : `${baseUrl}/`;
        window._paq = window._paq || [];
        window._paq.push(['trackPageView']);
        window._paq.push(['enableLinkTracking']);
        window._paq.push(['setTrackerUrl', `${normalizedBaseUrl}matomo.php`]);
        window._paq.push(['setSiteId', String(siteId)]);

        const script = document.createElement('script');
        script.async = true;
        script.src = `${normalizedBaseUrl}matomo.js`;
        script.setAttribute('data-cookie-consent-matomo', '1');
        document.head.appendChild(script);
    }
}
