class SalesQRScanner {
    constructor() {
        this.scanner = null;
        this.modal = null;
        this.isModalCreated = false;
        this.isProcessing = false;
        this.onScanSuccess = null;
        this.restartTimeout = null;
        this.lookupUrlTemplate = window.salesQrScannerConfig?.lookupUrlTemplate || null;
        this.handleEscape = this.handleEscape.bind(this);
    }

    setScanSuccessCallback(callback) {
        this.onScanSuccess = callback;
    }

    openModal() {
        if (typeof window.QrScanner !== 'function') {
            this.notifyScannerUnavailable();
            return;
        }

        this.createModal();

        if (this.modal) {
            this.modal.classList.remove('hidden');
        }

        this.startScanner().catch((error) => {
            this.showErrorState(this.getCameraErrorMessage(error));
        });
    }

    async closeModal() {
        this.isProcessing = false;
        this.clearRestartTimeout();
        document.removeEventListener('keydown', this.handleEscape);
        await this.stopScanner();

        if (this.modal) {
            this.modal.classList.add('hidden');

            setTimeout(() => {
                if (this.modal && this.modal.parentNode) {
                    this.modal.parentNode.removeChild(this.modal);
                    this.modal = null;
                    this.isModalCreated = false;
                }
            }, 200);
        }
    }

    handleEscape(event) {
        if (event.key === 'Escape') {
            this.closeModal().catch(() => {});
        }
    }

    createModal() {
        if (this.isModalCreated) {
            return;
        }

        document.body.insertAdjacentHTML('beforeend', `
            <div id="salesQrScannerModal" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 140;">
                <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
                    <button type="button" data-sales-qr-close class="fixed inset-0 bg-slate-900/35 backdrop-blur-[2px]" aria-label="Close QR scanner"></button>

                    <div class="relative z-10 w-full overflow-hidden bg-white" style="max-width: 35rem; border-radius: 2rem; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 30px 80px rgba(15, 23, 42, 0.18);" role="dialog" aria-modal="true" aria-labelledby="salesQrScannerTitle">
                        <div class="px-6 py-6 text-white" style="background: linear-gradient(90deg, #2f66f5 0%, #89adff 58%, #ffffff 100%);">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 id="salesQrScannerTitle" class="text-[2rem] font-semibold leading-none tracking-tight">QR Code Scanner</h3>
                                    <p class="mt-3 text-base text-white/90">Scan a fish box QR code to add it to this sale.</p>
                                </div>

                                <button type="button" data-sales-qr-close class="rounded-full p-2 text-white/80 transition hover:bg-white/15 hover:text-white" aria-label="Close QR scanner">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-5 bg-white px-6 pb-6 pt-5">
                            <div class="relative overflow-hidden border border-slate-200 bg-slate-100 shadow-inner" style="border-radius: 1.75rem;">
                                <video id="salesQrVideo" class="w-full" style="display: block; height: min(27rem, 52vh); object-fit: cover; background: #0f172a;" autoplay muted playsinline></video>
                                <div class="pointer-events-none absolute inset-0">
                                    <div class="absolute left-[23%] top-[16%] h-11 w-11 rounded-tl-2xl border-l-[5px] border-t-[5px] border-[#f4c117]"></div>
                                    <div class="absolute right-[23%] top-[16%] h-11 w-11 rounded-tr-2xl border-r-[5px] border-t-[5px] border-[#f4c117]"></div>
                                    <div class="absolute bottom-[16%] left-[23%] h-11 w-11 rounded-bl-2xl border-b-[5px] border-l-[5px] border-[#f4c117]"></div>
                                    <div class="absolute bottom-[16%] right-[23%] h-11 w-11 rounded-br-2xl border-b-[5px] border-r-[5px] border-[#f4c117]"></div>
                                </div>
                            </div>

                            <div id="salesQrStatus" class="rounded-[1.5rem] border border-slate-200 bg-white px-6 py-4 text-center shadow-sm">
                                Preparing scanner...
                            </div>

                            <div class="flex flex-col gap-3">
                                <button type="button" id="salesQrRetry" class="hidden w-full rounded-[1.25rem] border border-blue-200 bg-blue-50 px-4 py-4 text-base font-semibold text-blue-700 transition hover:bg-blue-100">
                                    Try Again
                                </button>
                                <button type="button" data-sales-qr-close class="w-full rounded-[1.25rem] bg-slate-900 px-4 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-slate-800">
                                    Close Scanner
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);

        this.modal = document.getElementById('salesQrScannerModal');
        this.isModalCreated = true;

        this.modal.querySelectorAll('[data-sales-qr-close]').forEach((button) => {
            button.addEventListener('click', () => this.closeModal().catch(() => {}));
        });

        const retryButton = document.getElementById('salesQrRetry');
        if (retryButton) {
            retryButton.addEventListener('click', () => {
                retryButton.classList.add('hidden');
                this.startScanner().catch((error) => {
                    this.showErrorState(this.getCameraErrorMessage(error));
                });
            });
        }

        document.addEventListener('keydown', this.handleEscape);
    }

    clearRestartTimeout() {
        if (this.restartTimeout) {
            clearTimeout(this.restartTimeout);
            this.restartTimeout = null;
        }
    }

    updateStatus(html) {
        const statusElement = document.getElementById('salesQrStatus');
        if (statusElement) {
            statusElement.innerHTML = html;
        }
    }

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
    }

    showReadyState() {
        this.updateStatus(`
            <div class="text-center">
                <p class="text-[1.35rem] font-semibold text-emerald-600">Camera active</p>
                <p class="mt-1 text-base text-slate-500">Point your camera at a fish box QR code.</p>
            </div>
        `);
    }

    showSuccessState(message) {
        this.updateStatus(`
            <div class="text-center">
                <p class="text-lg font-semibold text-emerald-600">${message}</p>
                <p class="mt-1 text-sm text-slate-500">Ready for the next fish box scan.</p>
            </div>
        `);
    }

    showErrorState(message) {
        this.updateStatus(`
            <div class="text-center">
                <p class="text-lg font-semibold text-red-600">${message}</p>
                <p class="mt-1 text-sm text-slate-500">Allow camera access and try again.</p>
            </div>
        `);

        const retryButton = document.getElementById('salesQrRetry');
        if (retryButton) {
            retryButton.classList.remove('hidden');
        }
    }

    getCameraErrorMessage(error) {
        const errorName = error?.name || '';
        const errorMessage = error?.message?.toLowerCase() || '';

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
    }

    async startScanner() {
        if (!this.modal || this.modal.classList.contains('hidden')) {
            return;
        }

        if (typeof window.QrScanner !== 'function') {
            throw new Error('Legacy QR scanner library is not available.');
        }

        const videoElement = document.getElementById('salesQrVideo');
        if (!videoElement) {
            throw new Error('QR video element is missing.');
        }

        const retryButton = document.getElementById('salesQrRetry');
        if (retryButton) {
            retryButton.classList.add('hidden');
        }

        this.showLoadingState('Starting camera...');
        await this.stopScanner();

        this.scanner = new window.QrScanner(
            videoElement,
            (result) => {
                const qrCode = typeof result === 'string' ? result : (result?.data || '');
                this.handleScanResult(qrCode);
            },
            {
                preferredCamera: 'environment',
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 10,
            }
        );

        await this.scanner.start();
        this.showReadyState();
    }

    async stopScanner() {
        if (this.scanner) {
            try {
                await this.scanner.stop();
            } catch (error) {
                console.warn('Unable to stop sales QR scanner cleanly.', error);
            }

            try {
                this.scanner.destroy();
            } catch (error) {
                console.warn('Unable to destroy sales QR scanner cleanly.', error);
            }

            this.scanner = null;
        }

        const videoElement = document.getElementById('salesQrVideo');
        if (videoElement?.srcObject) {
            videoElement.srcObject.getTracks().forEach((track) => track.stop());
            videoElement.srcObject = null;
        }
    }

    async handleScanResult(qrCode) {
        if (!qrCode || this.isProcessing) {
            return;
        }

        this.isProcessing = true;
        this.clearRestartTimeout();
        await this.stopScanner();

        this.showLoadingState(`Processing ${qrCode}...`);

        try {
            const fishBox = await this.getFishBoxByQRCode(qrCode);
            const fishBoxData = fishBox.data || fishBox;

            if (this.isFishBoxAlreadySelected(fishBoxData.id)) {
                const message = 'This fish box is already in the current transaction.';
                if (window.toastr) {
                    window.toastr.warning(message);
                }
                this.restartScannerAfterSuccess(message);
                return;
            }

            this.handleSalesQRScanSuccess(fishBoxData);
            this.restartScannerAfterSuccess('Fish box added.');
        } catch (error) {
            this.showErrorState(error?.message || 'Error processing QR code. Please try again.');
        } finally {
            this.isProcessing = false;
        }
    }

    restartScannerAfterSuccess(message) {
        this.showSuccessState(message);
        this.clearRestartTimeout();

        this.restartTimeout = setTimeout(() => {
            this.restartTimeout = null;

            if (!this.modal || this.modal.classList.contains('hidden')) {
                return;
            }

            this.startScanner().catch((error) => {
                this.showErrorState(this.getCameraErrorMessage(error));
            });
        }, 700);
    }

    async getFishBoxByQRCode(qrCode) {
        const response = await fetch(this.getLookupUrl(qrCode), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const payload = await response.json().catch(() => null);

        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || 'Fish box not found or not available for sale.');
        }

        return payload.data || payload;
    }

    getLookupUrl(qrCode) {
        if (this.lookupUrlTemplate) {
            return this.lookupUrlTemplate.replace('__QR_CODE__', encodeURIComponent(qrCode));
        }

        return new URL(`sales/fish-boxes/${encodeURIComponent(qrCode)}`, window.location.href).toString();
    }

    notifyScannerUnavailable() {
        const message = 'QR scanner could not be loaded. Please refresh and try again.';

        this.notify(message, 'error');
    }

    handleSalesQRScanSuccess(fishBox) {
        const fishBoxData = fishBox.data || fishBox;

        if (typeof this.onScanSuccess === 'function') {
            this.onScanSuccess(fishBoxData);
        } else {
            this.addFishBoxToSalesDetails(fishBoxData);
        }

        const fishTypeName = fishBoxData.fish_type?.name || fishBoxData.fish_type_name || fishBoxData.fish_type || 'Unknown';
        const boxNumber = fishBoxData.broker_box_number || fishBoxData.id;
        const boxName = fishBoxData.name || `Fish Box #${boxNumber}`;

        if (window.toastr) {
            window.toastr.success(`${boxName} (${fishTypeName}) added! Fish type auto-selected, quantity set to 1.`);
        }
    }

    getActiveSalesFormRoot() {
        const fullPageRoot = document.querySelector('[data-sales-form-root]');
        if (fullPageRoot) {
            return fullPageRoot;
        }

        const modalRoots = Array.from(document.querySelectorAll('[data-app-modal-root]'));

        return modalRoots.reverse().find((modalRoot) => modalRoot.offsetParent !== null)
            || modalRoots.at(-1)
            || document;
    }

    isFishBoxAlreadySelected(fishBoxId) {
        if (!fishBoxId) {
            return false;
        }

        const salesFormRoot = this.getActiveSalesFormRoot();
        return Array.from(salesFormRoot.querySelectorAll('.fish-box-hidden-input, input[type="hidden"][name*="[box_id]"]'))
            .some((input) => String(input.value) === String(fishBoxId));
    }

    getNextRowIndex(container) {
        const indexes = Array.from(container.querySelectorAll('.sales-detail-row'))
            .map((row) => Number.parseInt(row.dataset.index, 10))
            .filter(Number.isFinite);

        return indexes.length > 0 ? Math.max(...indexes) + 1 : 0;
    }

    getScannedUnitPrice(fishBox) {
        const price = fishBox.unit_price
            ?? fishBox.price
            ?? fishBox.suggested_price
            ?? fishBox.latest_price
            ?? null;
        const numericPrice = Number(price);

        return Number.isFinite(numericPrice) && numericPrice > 0 ? numericPrice : null;
    }

    ensureFishTypeOption(fishTypeSelect, fishTypeId, fishTypeName, suggestedPrice = null) {
        if (!fishTypeSelect || !fishTypeId) {
            return null;
        }

        const normalizedFishTypeId = String(fishTypeId);
        let option = Array.from(fishTypeSelect.options)
            .find((candidate) => candidate.value === normalizedFishTypeId);

        if (!option) {
            option = new Option(fishTypeName || `Fish #${normalizedFishTypeId}`, normalizedFishTypeId);
            fishTypeSelect.appendChild(option);
        } else if (fishTypeName && (!option.textContent || option.textContent.trim() === 'Select Fish')) {
            option.textContent = fishTypeName;
        }

        if (suggestedPrice !== null) {
            option.dataset.suggestedPrice = String(suggestedPrice);
        }

        return option;
    }

    applyScannedUnitPrice(targetRow, suggestedPrice) {
        if (suggestedPrice === null) {
            return;
        }

        const unitPriceInput = targetRow.querySelector('.unit-price-input');
        if (!unitPriceInput) {
            return;
        }

        const currentValue = Number(String(unitPriceInput.value || '').replace(/[₱,\s]/g, ''));
        if (unitPriceInput.value !== '' && Number.isFinite(currentValue) && currentValue > 0) {
            return;
        }

        unitPriceInput.value = suggestedPrice.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        unitPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
        unitPriceInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    addFishBoxToSalesDetails(fishBox) {
        const salesFormRoot = this.getActiveSalesFormRoot();
        const container = salesFormRoot.querySelector('#sales-details-container');
        if (!container) {
            return;
        }

        const fishTypeId = fishBox.fish_type_id || fishBox.fish_type?.id || null;
        const fishTypeName = fishBox.fish_type?.name || fishBox.fish_type_name || fishBox.fish_type || '';
        const scannedUnitPrice = this.getScannedUnitPrice(fishBox);

        const existingRows = container.querySelectorAll('.sales-detail-row');
        let targetRow = null;
        let rowIndex = null;

        for (let index = 0; index < existingRows.length; index++) {
            const row = existingRows[index];
            const fishTypeSelect = row.querySelector('.fish-type-select');

            if (fishTypeSelect && !fishTypeSelect.value) {
                targetRow = row;
                rowIndex = row.dataset.index;
                break;
            }
        }

        if (!targetRow) {
            const template = document.getElementById('sales-detail-row-template');
            if (!template) {
                this.notify('Sales row template is missing. Please refresh and try again.', 'error');
                return;
            }

            rowIndex = this.getNextRowIndex(container);
            const newRow = template.content.cloneNode(true).querySelector('.sales-detail-row');
            newRow.dataset.index = rowIndex;

            newRow.querySelectorAll('input, select').forEach((input) => {
                if (input.name) {
                    input.name = input.name.replace('[INDEX]', `[${rowIndex}]`);
                }
            });

            container.appendChild(newRow);
            targetRow = container.querySelector(`.sales-detail-row[data-index="${rowIndex}"]`);

            if (typeof window.bindSalesDetailRow === 'function') {
                window.bindSalesDetailRow(targetRow);
            }
        }

        if (!targetRow) {
            this.notify('Could not prepare the sales row. Please refresh and try again.', 'error');
            return;
        }

        targetRow.dataset.scanned = 'true';

        const fishTypeSelect = targetRow.querySelector('.fish-type-select');
        if (fishTypeSelect && fishTypeId) {
            this.ensureFishTypeOption(fishTypeSelect, fishTypeId, fishTypeName, scannedUnitPrice);
            fishTypeSelect.value = String(fishTypeId);

            const existingHiddenTypeInput = fishTypeSelect.parentNode.querySelector('.fish-type-hidden-input');
            if (existingHiddenTypeInput) {
                existingHiddenTypeInput.remove();
            }

            const hiddenTypeInput = document.createElement('input');
            hiddenTypeInput.type = 'hidden';
            hiddenTypeInput.name = fishTypeSelect.name;
            hiddenTypeInput.value = String(fishTypeId);
            hiddenTypeInput.className = 'fish-type-hidden-input';
            fishTypeSelect.parentNode.appendChild(hiddenTypeInput);

            fishTypeSelect.disabled = true;
            fishTypeSelect.classList.add('bg-gray-100', 'cursor-not-allowed');

            if (typeof window.refreshSalesSuggestedPriceForRow === 'function') {
                window.refreshSalesSuggestedPriceForRow(targetRow, {
                    force: true,
                    overwriteZero: true,
                    showMissingPriceWarning: true,
                });
            }

            this.applyScannedUnitPrice(targetRow, scannedUnitPrice);
        }

        const quantityInput = targetRow.querySelector('.quantity-input');
        if (quantityInput) {
            quantityInput.value = 1;

            const existingHiddenQtyInput = quantityInput.parentNode.querySelector('.quantity-hidden-input');
            if (existingHiddenQtyInput) {
                existingHiddenQtyInput.remove();
            }

            const hiddenQtyInput = document.createElement('input');
            hiddenQtyInput.type = 'hidden';
            hiddenQtyInput.name = quantityInput.name;
            hiddenQtyInput.value = '1';
            hiddenQtyInput.className = 'quantity-hidden-input';
            quantityInput.parentNode.appendChild(hiddenQtyInput);

            quantityInput.disabled = true;
            quantityInput.classList.add('bg-gray-100', 'cursor-not-allowed');
        }

        const itemInput = targetRow.querySelector('.item-input');
        if (itemInput) {
            itemInput.value = fishTypeName;
        }

        if (fishTypeSelect) {
            fishTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const fishBoxesContainer = targetRow.querySelector('.fish-boxes-container');
        if (fishBoxesContainer) {
            const boxLabel = fishBox.name || `Fish Box #${fishBox.broker_box_number || fishBox.id}`;
            fishBoxesContainer.innerHTML = `
                <div class="fish-box-item mb-2">
                    <div class="w-full rounded-lg border border-green-300 bg-green-50 px-3 py-2 text-sm">
                        <div class="flex items-center text-green-700">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-medium">${boxLabel}</span>
                            <span class="ml-2 text-xs">(Scanned)</span>
                        </div>
                    </div>
                    <input type="hidden" name="sales_details[${rowIndex}][box_id][]" value="${fishBox.id}" class="fish-box-hidden-input">
                </div>
            `;
        }

        if (typeof window.refreshSalesTotals === 'function') {
            window.refreshSalesTotals();
        }
    }

    notify(message, type = 'info') {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
            return;
        }

        if (window.Swal) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 2400,
                timerProgressBar: true,
            });
            return;
        }

        const notification = document.createElement('div');
        notification.textContent = message;
        notification.setAttribute('role', 'status');
        notification.style.cssText = [
            'position:fixed',
            'right:1rem',
            'top:1rem',
            'z-index:9999',
            'max-width:22rem',
            'padding:0.85rem 1rem',
            'border-radius:0.75rem',
            'background:#0f172a',
            'color:#fff',
            'box-shadow:0 16px 36px rgba(15,23,42,0.22)',
            'font:600 0.875rem system-ui,sans-serif',
        ].join(';');
        document.body.appendChild(notification);
        window.setTimeout(() => notification.remove(), 2800);
    }
}

window.SalesQRScanner = SalesQRScanner;
