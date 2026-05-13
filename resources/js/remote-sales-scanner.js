import QRCodeStyling from 'qr-code-styling';

(function () {
    const SESSION_STORAGE_KEY = 'broker.remoteSalesScanner.activeSession';
    let activeSession = null;
    let pollTimer = null;
    let modal = null;
    let qrCode = null;

    function getConfig() {
        return window.remoteSalesScannerConfig || {};
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function getAppBasePath() {
        const brokerMarker = '/broker/';
        const markerIndex = window.location.pathname.indexOf(brokerMarker);

        if (markerIndex === -1) {
            return '';
        }

        return window.location.pathname.slice(0, markerIndex);
    }

    function withAppBasePath(url) {
        if (!url || /^(https?:)?\/\//i.test(url)) {
            return url;
        }

        if (!url.startsWith('/')) {
            return url;
        }

        const appBasePath = getAppBasePath();

        if (!appBasePath || url.startsWith(`${appBasePath}/`)) {
            return url;
        }

        return `${appBasePath}${url}`;
    }

    function toBrowserUrl(url) {
        return new URL(withAppBasePath(url), window.location.href).href;
    }

    function normalizeSession(session) {
        if (!session || !session.token || !session.poll_url || !session.scanner_url) {
            return null;
        }

        return {
            ...session,
            poll_url: toBrowserUrl(session.poll_url),
            scanner_url: toBrowserUrl(session.scanner_url),
        };
    }

    function saveSession(session) {
        try {
            sessionStorage.setItem(SESSION_STORAGE_KEY, JSON.stringify(session));
        } catch (error) {
            console.warn('Unable to remember phone scanner session.', error);
        }
    }

    function forgetSession() {
        try {
            sessionStorage.removeItem(SESSION_STORAGE_KEY);
        } catch (error) {
            console.warn('Unable to clear remembered phone scanner session.', error);
        }
    }

    function restoreSession() {
        if (activeSession) {
            return activeSession;
        }

        try {
            activeSession = normalizeSession(JSON.parse(sessionStorage.getItem(SESSION_STORAGE_KEY) || 'null'));
        } catch (error) {
            activeSession = null;
            forgetSession();
        }

        return activeSession;
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
            ...options,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || 'Request failed.');
        }

        return payload;
    }

    function notify(message, type = 'info') {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
            return;
        }

        console[type === 'error' ? 'error' : 'log'](message);
    }

    function ensureModal() {
        if (modal) {
            return modal;
        }

        document.body.insertAdjacentHTML('beforeend', `
            <div id="remoteSalesScannerModal" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 135;">
                <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
                    <button type="button" data-remote-scanner-close class="fixed inset-0 bg-slate-900/35 backdrop-blur-[2px]" aria-label="Close phone scanner session"></button>
                    <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                        <div class="bg-slate-900 px-6 py-5 text-white">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-2xl font-semibold leading-none">Phone Scanner</h3>
                                    <p class="mt-2 text-sm text-white/75">Open this scanner on your phone. Every fish box scan will appear here.</p>
                                </div>
                                <button type="button" data-remote-scanner-close class="rounded-full p-2 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Close">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-5 px-6 py-6">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div id="remoteScannerQr" class="mx-auto flex min-h-[220px] items-center justify-center"></div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone link</label>
                                <div class="flex gap-2">
                                    <input id="remoteScannerUrl" type="text" readonly class="h-12 min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                                    <button type="button" id="remoteScannerCopy" class="rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white">Copy</button>
                                </div>
                            </div>
                            <div id="remoteScannerStatus" class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                Waiting for phone scans...
                            </div>
                            <button type="button" data-remote-scanner-close class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Hide Phone Scanner
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        modal = document.getElementById('remoteSalesScannerModal');
        modal.querySelectorAll('[data-remote-scanner-close]').forEach((button) => {
            button.addEventListener('click', closeSession);
        });

        modal.querySelector('#remoteScannerCopy')?.addEventListener('click', async () => {
            const urlInput = modal.querySelector('#remoteScannerUrl');
            try {
                await navigator.clipboard.writeText(urlInput.value);
                notify('Phone scanner link copied.', 'success');
            } catch (error) {
                urlInput.select();
                notify('Copy failed. You can manually copy the selected link.', 'info');
            }
        });

        return modal;
    }

    function setStatus(message, tone = 'blue') {
        const status = modal?.querySelector('#remoteScannerStatus');
        if (!status) {
            return;
        }

        const toneClass = tone === 'green'
            ? 'border-green-100 bg-green-50 text-green-700'
            : tone === 'red'
                ? 'border-red-100 bg-red-50 text-red-700'
                : 'border-blue-100 bg-blue-50 text-blue-700';

        status.className = `rounded-2xl border px-4 py-3 text-sm ${toneClass}`;
        status.textContent = message;
    }

    function renderQr(scannerUrl) {
        const target = modal.querySelector('#remoteScannerQr');
        target.innerHTML = '';

        qrCode = new QRCodeStyling({
            width: 220,
            height: 220,
            type: 'svg',
            data: scannerUrl,
            dotsOptions: {
                color: '#0f172a',
                type: 'rounded',
            },
            cornersSquareOptions: {
                color: '#2563eb',
                type: 'extra-rounded',
            },
            backgroundOptions: {
                color: '#f8fafc',
            },
        });

        qrCode.append(target);
    }

    function addRemoteScanToTransaction(fishBox) {
        if (!window.salesQrScanner && typeof window.SalesQRScanner === 'function') {
            window.salesQrScanner = new window.SalesQRScanner();
        }

        if (!window.salesQrScanner || typeof window.salesQrScanner.handleSalesQRScanSuccess !== 'function') {
            notify('Transaction scanner is not ready. Refresh the page and try again.', 'error');
            return false;
        }

        if (isFishBoxAlreadyInTransaction(fishBox.id)) {
            notify(`${fishBox.name || 'Fish box'} is already in this transaction.`, 'info');
            return false;
        }

        window.salesQrScanner.handleSalesQRScanSuccess(fishBox);
        return true;
    }

    function isFishBoxAlreadyInTransaction(fishBoxId) {
        if (!fishBoxId) {
            return false;
        }

        const root = document.querySelector('[data-sales-form-root]') || document;
        const selectors = [
            '.fish-box-hidden-input',
            'input[type="hidden"][name*="[box_id]"]',
        ];

        return Array.from(root.querySelectorAll(selectors.join(',')))
            .some((input) => String(input.value) === String(fishBoxId));
    }

    async function pollItems() {
        if (!activeSession) {
            return;
        }

        try {
            const payload = await fetchJson(activeSession.poll_url);
            let acceptedCount = 0;

            (payload.items || []).forEach((item) => {
                if (item.status === 'accepted' && item.data) {
                    if (addRemoteScanToTransaction(item.data)) {
                        acceptedCount += 1;
                    }
                    return;
                }

                if (item.message) {
                    notify(item.message, item.status === 'error' ? 'error' : 'info');
                }
            });

            if (acceptedCount > 0) {
                setStatus(`${acceptedCount} fish box scan${acceptedCount === 1 ? '' : 's'} added to this transaction.`, 'green');
            }
        } catch (error) {
            setStatus(error.message || 'Phone scanner session stopped.', 'red');
            activeSession = null;
            forgetSession();
            stopPolling();
        }
    }

    function startPolling() {
        stopPolling();
        pollItems();
        pollTimer = setInterval(pollItems, 1500);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    async function openSession() {
        const config = getConfig();
        if (!config.createUrl) {
            notify('Phone scanner is not configured.', 'error');
            return;
        }

        restoreSession();
        ensureModal();
        modal.classList.remove('hidden');

        if (activeSession) {
            modal.querySelector('#remoteScannerUrl').value = activeSession.scanner_url;
            renderQr(activeSession.scanner_url);
            setStatus('Waiting for phone scans...');
            startPolling();
            return;
        }

        setStatus('Creating phone scanner session...');

        try {
            activeSession = normalizeSession(await fetchJson(withAppBasePath(config.createUrl), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            }));

            saveSession(activeSession);
            modal.querySelector('#remoteScannerUrl').value = activeSession.scanner_url;
            renderQr(activeSession.scanner_url);
            setStatus('Waiting for phone scans...');
            startPolling();
        } catch (error) {
            setStatus(error.message || 'Unable to create phone scanner session.', 'red');
        }
    }

    async function closeSession() {
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function bindButtons() {
        document.querySelectorAll('[data-remote-sales-scanner-open]').forEach((button) => {
            if (button.dataset.remoteScannerBound === 'true') {
                return;
            }

            button.dataset.remoteScannerBound = 'true';
            button.addEventListener('click', openSession);
        });
    }

    function resumeSessionIfPresent() {
        if (!restoreSession()) {
            return;
        }

        startPolling();
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindButtons();
        resumeSessionIfPresent();
    });
    window.bindRemoteSalesScannerButtons = bindButtons;
    window.openRemoteSalesScannerSession = openSession;
})();
