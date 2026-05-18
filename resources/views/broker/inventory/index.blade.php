@extends('layouts.broker')

@php
    $currentTab = $currentTab ?? request('tab', 'fishBoxes');
    $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;

    $tabTitles = [
        'fishBoxes' => 'Fish Boxes',
        'fishTypes' => 'Fish',
        'fishPrices' => 'Fish Prices',
    ];

    $breadcrumbs = [
        ['title' => 'Inventory & Pricing']
    ];

    if (isset($tabTitles[$currentTab])) {
        $breadcrumbs[] = ['title' => $tabTitles[$currentTab]];
    }

    $inventoryModalBreadcrumbs = [
        'create' => [
            'fishBoxes' => 'Add Stock',
            'fishTypes' => 'Add Fish',
            'fishPrices' => 'Add Fish Price',
        ],
        'edit' => [
            'fishTypes' => 'Edit Fish',
            'fishPrices' => 'Edit Fish Price',
        ],
        'bulk-restock' => [
            'fishBoxes' => 'Bulk Assign / Daily Restock',
        ],
    ];

    if (request()->filled('modal') && isset($inventoryModalBreadcrumbs[request('modal')][$currentTab])) {
        $breadcrumbs[] = ['title' => $inventoryModalBreadcrumbs[request('modal')][$currentTab]];
    }
@endphp

@section('content')
<!-- Meta tags for QR scanner functionality -->
<meta name="fish-box-update-url" content="{{ route('broker.fish-boxes.return-via-qr') }}">

<div class="relative w-full workspace-modal-host">
                <!-- Tab Navigation -->
                <div class="bg-white rounded-xl shadow-lg mb-3">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-8 px-4 md:px-6" aria-label="Tabs">
                            <a href="{{ route('broker.inventory.index') }}?tab=fishBoxes"
                               class="whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ ($currentTab ?? request('tab', 'fishBoxes')) === 'fishBoxes' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-archive-box class="w-5 h-5" />
                                    <span>Fish Boxes</span>
                                </div>
                            </a>
                            <a href="{{ route('broker.inventory.index') }}?tab=fishTypes"
                               class="whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ ($currentTab ?? request('tab')) === 'fishTypes' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-tag class="w-5 h-5" />
                                    <span>Fish</span>
                                </div>
                            </a>
                            <a href="{{ route('broker.inventory.index') }}?tab=fishPrices"
                               class="whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ ($currentTab ?? request('tab')) === 'fishPrices' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-currency-dollar class="w-5 h-5" />
                                    <span>Fish Prices</span>
                                </div>
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="tab-content" data-inventory-tab-content>
                    @if($currentTab === 'fishBoxes')
                        @include('broker.inventory.fish-boxes')
                    @elseif($currentTab === 'fishTypes')
                        @include('broker.inventory.fish-types', ['fishTypes' => $fishTypes ?? collect()])
                    @elseif($currentTab === 'fishPrices')
                        @include('broker.inventory.fish-prices')
                    @endif
                </div>
            </div>
<!-- Inventory page specific JS -->
<script src="{{ asset('js/inventory.js') }}" defer></script>
@if($currentTab === 'fishBoxes')
    <script src="{{ asset('js/inventory-async.js') }}" defer></script>
    <script src="{{ asset('js/qr-code.js') }}?v={{ filemtime(public_path('js/qr-code.js')) }}" defer></script>
    <script src="{{ asset('js/qr-scanner-legacy.min.js') }}" defer></script>
    <script src="{{ asset('js/qr-backend-handler.js') }}" defer></script>
@endif

<script>
(function () {
    function createInventoryQrController() {
        return {
            scanner: null,
            modal: null,
            keepScanning: false,
            isProcessing: false,
            restartTimeout: null,
            lastSuccessfulQrCode: null,
            lastSuccessfulAt: null,

            ensureBackend() {
                if (typeof window.QRBackendHandler !== 'function') {
                    throw new Error('QR backend handler is not available.');
                }

                window.qrBackendHandler = window.qrBackendHandler || new QRBackendHandler();
                window.qrBackendHandler.initialize();

                return window.qrBackendHandler;
            },

            createModal() {
                if (this.modal) {
                    return;
                }

                document.body.insertAdjacentHTML('beforeend', `
                    <div id="qrScannerModal" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 140;">
                        <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
                            <button type="button" data-qr-close class="fixed inset-0 bg-slate-900/35 backdrop-blur-[2px]" aria-label="Close QR scanner"></button>

                            <div class="relative z-10 w-full overflow-hidden bg-white" style="max-width: 35rem; border-radius: 2rem; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 30px 80px rgba(15, 23, 42, 0.18);">
                                <div class="px-6 py-6 text-white" style="background: linear-gradient(90deg, #2f66f5 0%, #89adff 58%, #ffffff 100%);">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-[2rem] font-semibold leading-none tracking-tight">QR Code Scanner</h3>
                                            <p class="mt-3 text-base text-white/90">Scan a fish box QR code to return it.</p>
                                        </div>
                                        <button type="button" data-qr-close class="rounded-full p-2 text-white/80 transition hover:bg-white/15 hover:text-white" aria-label="Close QR scanner">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-5 bg-white px-6 pb-6 pt-5">
                                    <div class="relative overflow-hidden border border-slate-200 bg-slate-100 shadow-inner" style="border-radius: 1.75rem;">
                                        <video id="qr-reader" class="w-full" style="display: block; height: min(27rem, 52vh); object-fit: cover; background: #0f172a;" autoplay muted playsinline></video>
                                        <div class="pointer-events-none absolute inset-0">
                                            <div class="absolute left-[23%] top-[16%] h-11 w-11 rounded-tl-2xl border-l-[5px] border-t-[5px] border-[#f4c117]"></div>
                                            <div class="absolute right-[23%] top-[16%] h-11 w-11 rounded-tr-2xl border-r-[5px] border-t-[5px] border-[#f4c117]"></div>
                                            <div class="absolute bottom-[16%] left-[23%] h-11 w-11 rounded-bl-2xl border-b-[5px] border-l-[5px] border-[#f4c117]"></div>
                                            <div class="absolute bottom-[16%] right-[23%] h-11 w-11 rounded-br-2xl border-b-[5px] border-r-[5px] border-[#f4c117]"></div>
                                        </div>
                                    </div>

                                    <div id="qr-status" class="rounded-[1.5rem] border border-slate-200 bg-white px-6 py-4 text-center shadow-sm">
                                        Preparing scanner...
                                    </div>

                                    <div class="flex flex-col gap-3">
                                        <button type="button" id="retry-camera" class="hidden w-full rounded-[1.25rem] border border-blue-200 bg-blue-50 px-4 py-4 text-base font-semibold text-blue-700 transition hover:bg-blue-100">
                                            Try Again
                                        </button>
                                        <button type="button" data-qr-close class="w-full rounded-[1.25rem] bg-slate-900 px-4 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-slate-800">
                                            Close Scanner
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);

                this.modal = document.getElementById('qrScannerModal');

                this.modal.querySelectorAll('[data-qr-close]').forEach((button) => {
                    button.addEventListener('click', () => this.closeModal());
                });

                const retryButton = document.getElementById('retry-camera');
                if (retryButton) {
                    retryButton.addEventListener('click', () => {
                        retryButton.classList.add('hidden');
                        this.startScanner().catch(() => {});
                    });
                }
            },

            updateStatus(html) {
                const statusElement = document.getElementById('qr-status');
                if (statusElement) {
                    statusElement.innerHTML = html;
                }
            },

            showReadyState() {
                this.updateStatus(`
                    <div class="text-center">
                        <p class="text-[1.35rem] font-semibold text-emerald-600">Camera active</p>
                        <p class="mt-1 text-base text-slate-500">Point your camera at a fish box QR code.</p>
                    </div>
                `);
            },

            showLoadingState(message) {
                this.updateStatus(`
                    <div class="flex items-center justify-center gap-3 text-left">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-blue-100 border-t-blue-600"></span>
                        <div>
                            <p class="text-base font-semibold text-blue-700">${message}</p>
                            <p class="text-sm text-slate-500">Hold steady while we prepare the scanner.</p>
                        </div>
                    </div>
                `);
            },

            showErrorState(message) {
                this.updateStatus(`
                    <div class="text-center">
                        <p class="text-lg font-semibold text-red-600">${message}</p>
                        <p class="mt-1 text-sm text-slate-500">Allow camera access and try again.</p>
                    </div>
                `);

                const retryButton = document.getElementById('retry-camera');
                if (retryButton) {
                    retryButton.classList.remove('hidden');
                }
            },

            getCameraErrorMessage(error) {
                const errorName = error && error.name ? error.name : '';
                const errorMessage = error && error.message ? error.message.toLowerCase() : '';

                if (errorName === 'NotAllowedError' || errorName === 'PermissionDeniedError') {
                    return 'Camera access was denied.';
                }

                if (errorName === 'NotReadableError' || errorMessage.includes('already in use')) {
                    return 'Camera is already in use by another app.';
                }

                if (errorName === 'NotFoundError' || errorMessage.includes('camera not found')) {
                    return 'No camera was found on this device.';
                }

                if (errorName === 'NotSupportedError' || errorMessage.includes('secure context')) {
                    return 'This browser cannot start the camera here.';
                }

                return 'Unable to start the camera.';
            },

            async openModalAndStart() {
                if (typeof window.QrScanner !== 'function') {
                    throw new Error('Legacy QR scanner library is not available.');
                }

                this.ensureBackend();
                this.createModal();
                this.keepScanning = true;
                this.modal.classList.remove('hidden');
                await this.startScanner();
            },

            async startScanner() {
                if (!this.keepScanning) {
                    return;
                }

                const videoElement = document.getElementById('qr-reader');
                if (!videoElement) {
                    throw new Error('QR video element is missing.');
                }

                const retryButton = document.getElementById('retry-camera');
                if (retryButton) {
                    retryButton.classList.add('hidden');
                }

                this.showLoadingState('Starting camera...');

                await this.stopScanner();

                this.scanner = new window.QrScanner(
                    videoElement,
                    (result) => this.handleScanResult(typeof result === 'string' ? result : (result && result.data ? result.data : '')),
                    {
                        preferredCamera: 'environment',
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                        maxScansPerSecond: 10,
                    }
                );

                try {
                    await this.scanner.start();
                    this.showReadyState();
                } catch (error) {
                    this.showErrorState(this.getCameraErrorMessage(error));
                    throw error;
                }
            },

            async stopScanner() {
                if (!this.scanner) {
                    return;
                }

                try {
                    await this.scanner.stop();
                } catch (error) {
                    console.warn('Unable to stop QR scanner cleanly.', error);
                }

                try {
                    this.scanner.destroy();
                } catch (error) {
                    console.warn('Unable to destroy QR scanner cleanly.', error);
                }

                this.scanner = null;
            },

            clearRestartTimeout() {
                if (this.restartTimeout) {
                    clearTimeout(this.restartTimeout);
                    this.restartTimeout = null;
                }
            },

            isDuplicateSuccessfulScan(qrCode) {
                return this.lastSuccessfulQrCode === qrCode
                    && this.lastSuccessfulAt !== null
                    && Date.now() - this.lastSuccessfulAt < 4000;
            },

            markSuccessfulScan(qrCode) {
                this.lastSuccessfulQrCode = qrCode;
                this.lastSuccessfulAt = Date.now();
            },

            summaryKeyForStatus(status) {
                return {
                    'Unassigned': 'unassigned',
                    'In Stock': 'in_stock',
                    'Sold': 'sold',
                    'Returned': 'returned',
                }[status] || null;
            },

            updateSummaryCount(status, delta) {
                const summaryKey = this.summaryKeyForStatus(status);

                if (!summaryKey) {
                    return;
                }

                const summaryElement = document.querySelector(`[data-fish-box-summary="${summaryKey}"]`);

                if (!summaryElement) {
                    return;
                }

                const currentValue = Number.parseInt((summaryElement.textContent || '0').replace(/[^0-9-]/g, ''), 10) || 0;
                const nextValue = Math.max(0, currentValue + delta);

                summaryElement.textContent = new Intl.NumberFormat('en-US').format(nextValue);
            },

            updateReturnedFishBoxCard(result) {
                const data = result && result.data ? result.data : {};
                const fishBoxId = data.fish_box_id ? String(data.fish_box_id) : '';
                const newStatus = data.new_status || 'Returned';

                if (!fishBoxId) {
                    return;
                }

                const card = Array.from(document.querySelectorAll('[data-fish-box-card]'))
                    .find((element) => element.dataset.fishBoxId === fishBoxId);

                if (!card) {
                    return;
                }

                const oldStatus = data.old_status || card.dataset.fishBoxStatus || '';

                if (oldStatus && oldStatus !== newStatus) {
                    this.updateSummaryCount(oldStatus, -1);
                    this.updateSummaryCount(newStatus, 1);
                }

                card.dataset.fishBoxStatus = newStatus;

                const badge = card.querySelector('[data-fish-box-status-badge]');
                const statusClasses = {
                    'Unassigned': ['status-badge--neutral'],
                    'In Stock': ['status-badge--success'],
                    'Sold': ['status-badge--open'],
                    'Returned': ['status-badge--warning'],
                    'Missing': ['status-badge--danger'],
                };

                if (badge) {
                    badge.className = [
                        'status-badge',
                        'status-badge--sm',
                        ...(statusClasses[newStatus] || ['status-badge--neutral']),
                    ].join(' ');
                    badge.textContent = newStatus;
                }
                card.querySelectorAll('form[data-inventory-async="return-fish-box"], form[data-inventory-async="mark-missing"]')
                    .forEach((form) => form.remove());

            },

            async closeModal() {
                this.keepScanning = false;
                this.isProcessing = false;
                this.clearRestartTimeout();
                await this.stopScanner();

                if (this.modal) {
                    this.modal.classList.add('hidden');
                }
            },

            restartScannerAfterSuccess(message) {
                this.showLoadingState(message || 'Fish box returned successfully.');
                this.clearRestartTimeout();

                this.restartTimeout = window.setTimeout(() => {
                    this.restartTimeout = null;

                    if (!this.keepScanning || !this.modal || this.modal.classList.contains('hidden')) {
                        return;
                    }

                    this.startScanner().catch(() => {});
                }, 900);
            },

            async handleScanResult(qrCode) {
                if (!qrCode || this.isProcessing || this.isDuplicateSuccessfulScan(qrCode)) {
                    return;
                }

                this.isProcessing = true;
                this.clearRestartTimeout();
                await this.stopScanner();

                this.showLoadingState('Processing scanned QR code...');

                const backendHandler = this.ensureBackend();

                backendHandler.handleQRScanResult(
                    qrCode,
                    (result) => {
                        this.isProcessing = false;
                        this.markSuccessfulScan(qrCode);
                        this.updateReturnedFishBoxCard(result);
                        this.restartScannerAfterSuccess(result.message || 'Fish box returned successfully.');
                    },
                    () => {
                        this.isProcessing = false;
                        this.closeModal();
                    }
                );
            },
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        const scanQRBtn = document.querySelector('[data-scan-qr-return-shortcut]');
        const shouldAutoOpenScanner = new URLSearchParams(window.location.search).get('scan_return') === '1';
        const canOpenScannerHere = @json($currentTab === 'fishBoxes');

        if (!canOpenScannerHere || ((!scanQRBtn && !shouldAutoOpenScanner) || scanQRBtn?.dataset.qrBound === 'true')) {
            return;
        }

        window.qrScanner = window.qrScanner || createInventoryQrController();

        async function openReturnScanner(event) {
            event?.preventDefault();

            if (scanQRBtn) {
                scanQRBtn.setAttribute('aria-busy', 'true');
                scanQRBtn.classList.add('pointer-events-none', 'opacity-75');
            }

            try {
                await window.qrScanner.openModalAndStart();
            } catch (error) {
                const errorMessage = error && error.message
                    ? error.message
                    : 'QR Scanner could not be loaded. Please refresh and try again.';
                if (window.toastr) {
                    window.toastr.error(errorMessage);
                } else if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'QR Scanner',
                        text: errorMessage,
                        confirmButtonColor: '#dc2626',
                    });
                } else {
                    const notification = document.createElement('div');
                    notification.textContent = errorMessage;
                    notification.setAttribute('role', 'status');
                    notification.style.cssText = 'position:fixed;right:1rem;top:1rem;z-index:9999;max-width:22rem;padding:0.85rem 1rem;border-radius:0.75rem;background:#991b1b;color:#fff;box-shadow:0 16px 36px rgba(15,23,42,0.22);font:600 0.875rem system-ui,sans-serif';
                    document.body.appendChild(notification);
                    window.setTimeout(() => notification.remove(), 2800);
                }
            } finally {
                if (scanQRBtn) {
                    scanQRBtn.removeAttribute('aria-busy');
                    scanQRBtn.classList.remove('pointer-events-none', 'opacity-75');
                }
            }
        }

        if (scanQRBtn) {
            scanQRBtn.dataset.qrBound = 'true';
            scanQRBtn.addEventListener('click', openReturnScanner);
        }

        if (shouldAutoOpenScanner) {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('scan_return');
            window.history.replaceState({}, '', cleanUrl.toString());
            window.setTimeout(() => openReturnScanner(), 250);
        }
    });
})();
</script>
@endsection
