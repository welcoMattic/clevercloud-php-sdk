import { Controller } from '@hotwired/stimulus';

/*
 * Subscribes to a Server-Sent Events stream of log entries (proxied by
 * `LogsController::stream()` from the SDK's LogStream) and prepends each
 * one to the viewport. Auto-scroll stays at the bottom unless the user
 * scrolls up; a button toggles it. Disconnects cleanly on page navigation
 * via Stimulus' `disconnect()` lifecycle so the server-side connection
 * tears down.
 */
export default class extends Controller {
    static targets = ['lines', 'viewport', 'status', 'autoscroll'];
    static values = { streamUrl: String };

    connect() {
        this.autoscroll = true;
        this._refreshAutoscrollButton();
        this._open();

        this.viewportTarget.addEventListener('scroll', () => {
            const v = this.viewportTarget;
            const atBottom = v.scrollHeight - v.scrollTop - v.clientHeight < 32;
            if (!atBottom && this.autoscroll) {
                this.autoscroll = false;
                this._refreshAutoscrollButton();
            }
        });
    }

    disconnect() { this._close(); }

    _open() {
        this._close();
        this._source = new EventSource(this.streamUrlValue);
        this._setStatus('Connecté', 'ok');

        this._source.onmessage = (e) => {
            try {
                const entry = JSON.parse(e.data);
                this._append(entry);
            } catch (err) {
                console.warn('logs: bad frame', e.data);
            }
        };

        this._source.addEventListener('error', (e) => {
            if (e.data) {
                try {
                    const payload = JSON.parse(e.data);
                    this._append({ severity: 'ERROR', message: '[stream error] ' + payload.error });
                } catch (_) {}
            }
            this._setStatus('Déconnecté', 'fail');
        });
    }

    _close() {
        if (this._source) {
            this._source.close();
            this._source = null;
        }
    }

    stop() {
        this._close();
        this._setStatus('Arrêté', 'neutral');
    }

    clear() {
        this.linesTarget.textContent = '';
    }

    toggleAutoscroll() {
        this.autoscroll = !this.autoscroll;
        this._refreshAutoscrollButton();
        if (this.autoscroll) this._scrollToBottom();
    }

    _append(entry) {
        const sev = (entry.severity || 'INFO').toUpperCase();
        const line = document.createElement('span');
        line.className = 'logs-line logs-line--' + this._severityClass(sev);
        const ts = entry.date ? `[${entry.date}] ` : '';
        const inst = entry.instanceId ? ` ${entry.instanceId.slice(0, 8)}` : '';
        line.textContent = ts + sev + inst + ' · ' + (entry.message ?? '') + '\n';
        this.linesTarget.appendChild(line);

        // Keep the buffer bounded (avoid memory blow-up on long sessions)
        const MAX = 2000;
        while (this.linesTarget.childElementCount > MAX) {
            this.linesTarget.removeChild(this.linesTarget.firstChild);
        }

        if (this.autoscroll) this._scrollToBottom();
    }

    _severityClass(sev) {
        if (sev === 'ERROR' || sev === 'FATAL') return 'error';
        if (sev === 'WARN' || sev === 'WARNING') return 'warn';
        if (sev === 'DEBUG' || sev === 'TRACE') return 'muted';
        return 'info';
    }

    _scrollToBottom() {
        this.viewportTarget.scrollTop = this.viewportTarget.scrollHeight;
    }

    _setStatus(text, variant) {
        if (!this.hasStatusTarget) return;
        this.statusTarget.textContent = text;
        this.statusTarget.classList.remove('ok', 'warn', 'fail', 'neutral');
        this.statusTarget.classList.add(variant);
    }

    _refreshAutoscrollButton() {
        if (!this.hasAutoscrollTarget) return;
        this.autoscrollTarget.classList.toggle('btn--primary', this.autoscroll);
    }
}
