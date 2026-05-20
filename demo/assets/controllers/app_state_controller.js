import { Controller } from '@hotwired/stimulus';

/*
 * Polls the application's state endpoint (returning JSON `{state: "..."}`)
 * while the state is transient — typically right after a redeploy. Updates
 * the badge text + class in place and shows a spinner. Stops polling when
 * the state settles on a stable value.
 *
 * Markup:
 *   <span data-controller="app-state"
 *         data-app-state-url-value="/applications/app_xxx/state.json?owner=..."
 *         data-app-state-current-value="SHOULD_BE_UP"
 *         data-app-state-poll-on-load-value="true">
 *       <span data-app-state-target="badge" class="badge ok">SHOULD_BE_UP</span>
 *       <span class="state__spinner"></span>
 *   </span>
 */
export default class extends Controller {
    static targets = ['badge'];

    static values = {
        url: String,
        current: String,
        pollOnLoad: { type: Boolean, default: false },
        intervalMs: { type: Number, default: 3000 },
    };

    // States that are stable: no polling needed unless a user-triggered
    // redeploy explicitly opts back in via `start()`.
    static STABLE_STATES = ['SHOULD_BE_UP', 'SHOULD_BE_DOWN'];

    // Mirrors App\Twig\CleverCloudExtension::stateLabel().
    static LABELS = {
        SHOULD_BE_UP: 'En ligne',
        WANTS_TO_BE_UP: 'Démarrage',
        SHOULD_BE_DOWN: 'Arrêtée',
        WANTS_TO_BE_DOWN: 'Arrêt en cours',
        RESTART: 'Redémarrage',
        RESTART_REQUESTED: 'Redémarrage demandé',
        RESTART_FAILED: 'Redémarrage échoué',
        DEPLOYING: 'Déploiement',
        DEPLOYMENT_PENDING: 'Déploiement en attente',
    };

    connect() {
        this._timer = null;
        if (this.pollOnLoadValue && !this._isStable(this.currentValue)) {
            this.start();
        }
    }

    disconnect() {
        this._stopTimer();
    }

    /** Public: start polling. Called by other controllers (deploy form). */
    start() {
        this.element.classList.add('is-polling');
        this._poll();
    }

    /** Public: stop polling immediately. */
    stop() {
        this._stopTimer();
        this.element.classList.remove('is-polling');
    }

    async _poll() {
        this._stopTimer();
        try {
            const res = await fetch(this.urlValue, { headers: { Accept: 'application/json' } });
            if (res.ok) {
                const data = await res.json();
                const next = data.state || 'UNKNOWN';
                this._updateBadge(next);
                if (this._isStable(next)) {
                    this.stop();
                    return;
                }
            }
        } catch (e) {
            // network blip — retry on schedule
        }
        this._timer = setTimeout(() => this._poll(), this.intervalMsValue);
    }

    _stopTimer() {
        if (this._timer) {
            clearTimeout(this._timer);
            this._timer = null;
        }
    }

    _updateBadge(state) {
        if (!this.hasBadgeTarget) return;
        this.badgeTarget.textContent = this.constructor.LABELS[state] ?? state;
        this.badgeTarget.classList.remove('ok', 'warn', 'fail', 'neutral');
        this.badgeTarget.classList.add(this._badgeClass(state));
        this.currentValue = state;
    }

    _badgeClass(state) {
        if (state === 'SHOULD_BE_UP') return 'ok';
        if (state === 'SHOULD_BE_DOWN') return 'neutral';
        if (state === 'WANTS_TO_BE_UP' || state === 'RESTART' || state === 'DEPLOYING') return 'warn';
        return 'fail';
    }

    _isStable(state) {
        return this.constructor.STABLE_STATES.includes(state);
    }
}
