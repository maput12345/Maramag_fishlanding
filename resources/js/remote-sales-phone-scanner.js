(function () {
    let scanner = null;
    let isProcessing = false;
    let restartTimer = null;

    function getConfig() {
        return window.remoteSalesPhoneScannerConfig || {};
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
        if (!url || /^(https?:)?\/\//i.test(url) || !url.startsWith('/')) {
            return url;
        }

        const appBasePath = getAppBasePath();

        if (!appBasePath || url.startsWith(`${appBasePath}/`)) {
            return url;
        }

        return `${appBasePath}${url}`;
    }

    function setStatus(message, tone = 'slate') {
        const status = document.querySelector('[data-phone-scanner-status]');
        if (!status) {
            return;
        }

        const toneClass = tone === 'green'
            ? 'border-green-200 bg-green-50 text-green-700'
            : tone === 'red'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-slate-200 bg-white text-slate-700';

        status.className = `rounded-2xl border px-4 py-3 text-center text-sm font-medium ${toneClass}`;
        status.textContent = message;
    }

    async function startScanner() {
        if (!getConfig().scanUrl) {
            setStatus('Phone scanner session is not active.', 'red');
            return;
        }

        if (typeof window.QrScanner !== 'function') {
            setStatus('QR scanner could not load. Refresh this page.', 'red');
            return;
        }

        const video = document.getElementById('remotePhoneScannerVideo');
        if (!video) {
            return;
        }

        await stopScanner();
        setStatus('Starting camera...');

        scanner = new window.QrScanner(
            video,
            (result) => {
                const qrCode = typeof result === 'string' ? result : (result?.data || '');
                handleScan(qrCode);
            },
            {
                preferredCamera: 'environment',
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 8,
            }
        );

        try {
            await scanner.start();
            setStatus('Camera active. Scan a fish box QR code.');
        } catch (error) {
            setStatus('Unable to start camera. Allow camera access and refresh.', 'red');
        }
    }

    async function stopScanner() {
        clearTimeout(restartTimer);
        restartTimer = null;

        if (scanner) {
            try {
                await scanner.stop();
                scanner.destroy();
            } catch (error) {
                console.warn('Unable to stop remote phone scanner cleanly.', error);
            }
            scanner = null;
        }
    }

    async function handleScan(qrCode) {
        if (!qrCode || isProcessing) {
            return;
        }

        isProcessing = true;
        setStatus('Sending fish box to laptop...');

        try {
            const response = await fetch(withAppBasePath(getConfig().scanUrl), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ qr_code: qrCode }),
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Scan failed.');
            }

            setStatus(payload.message || 'Fish box sent to laptop.', 'green');
        } catch (error) {
            setStatus(error.message || 'Unable to send scan.', 'red');
        } finally {
            restartTimer = setTimeout(() => {
                isProcessing = false;
                setStatus('Ready for next fish box QR code.');
            }, 900);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('[data-phone-scanner-start]')?.addEventListener('click', startScanner);
        startScanner();
    });
})();
