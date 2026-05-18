import QRCodeStyling from 'qr-code-styling';

// QR Code modal and bulk-print functionality
window.QRCodeModal = {
    init() {
        this.bindEvents();
    },

    bindEvents() {
        document.addEventListener('click', async (e) => {
            const qrButton = e.target.closest('.qr-code-btn');
            if (qrButton) {
                e.preventDefault();
                const qrData = qrButton.dataset.qrData;
                const fishBoxName = qrButton.dataset.fishBoxName;
                this.showQRModal(qrData, fishBoxName);
                return;
            }

            const bulkPrintButton = e.target.closest('.bulk-qr-print-btn');
            if (bulkPrintButton) {
                e.preventDefault();
                await this.printBulkQRCodes(bulkPrintButton);
                return;
            }

            if (e.target.classList.contains('qr-modal-overlay') || e.target.closest('.qr-modal-close')) {
                this.hideQRModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideQRModal();
            }
        });
    },

    getQRCodeOptions(data, size = 256, type = 'svg') {
        return {
            width: size,
            height: size,
            type,
            data,
            dotsOptions: {
                color: '#2563eb',
                type: 'rounded',
            },
            backgroundOptions: {
                color: '#ffffff',
            },
            cornersSquareOptions: {
                color: '#1d4ed8',
                type: 'extra-rounded',
            },
            cornersDotOptions: {
                color: '#1e40af',
                type: 'dot',
            },
            qrOptions: {
                errorCorrectionLevel: 'M',
            },
        };
    },

    createQRCode(data, size = 256, type = 'svg') {
        return new QRCodeStyling(this.getQRCodeOptions(data, size, type));
    },

    showQRModal(qrData, fishBoxName) {
        this.hideQRModal();

        const modalHTML = `
            <div id="qr-modal" class="fixed inset-0 z-50 overflow-y-auto qr-modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                        <div class="bg-white px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900" id="modal-title">
                                    QR Code - ${this.escapeHtml(fishBoxName)}
                                </h3>
                                <button class="qr-modal-close text-gray-400 hover:text-gray-600 transition-colors" type="button">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="bg-white px-6 py-6">
                            <div class="flex flex-col items-center">
                                <div id="qr-code-container" class="mb-4"></div>
                                <p class="text-sm text-gray-600 text-center mb-4">
                                    Scan this QR code to view fish box details
                                </p>
                                <div class="flex space-x-3">
                                    <button id="download-png" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                        Download PNG
                                    </button>
                                    <button id="download-svg" type="button" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                        Download SVG
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.generateQRCode(qrData);
    },

    generateQRCode(data) {
        const qrCode = this.createQRCode(data, 256);
        const container = document.getElementById('qr-code-container');
        qrCode.append(container);

        document.getElementById('download-png').addEventListener('click', () => {
            qrCode.download({ name: `qr-code-${Date.now()}`, extension: 'png' });
        });

        document.getElementById('download-svg').addEventListener('click', () => {
            qrCode.download({ name: `qr-code-${Date.now()}`, extension: 'svg' });
        });

        this.currentQRCode = qrCode;
    },

    getBulkPrintBoxes(button) {
        const sourceId = button.dataset.bulkQrSource;
        const sourceElement = sourceId ? document.getElementById(sourceId) : null;

        if (!sourceElement) {
            return [];
        }

        try {
            const parsedBoxes = JSON.parse(sourceElement.textContent || '[]');
            return Array.isArray(parsedBoxes) ? parsedBoxes : [];
        } catch (error) {
            this.notify('Bulk QR data could not be read. Please refresh the page and try again.', 'error');
            return [];
        }
    },

    getBulkQrPrintSize(button) {
        const sizeSourceId = button.dataset.bulkQrSizeSource;
        const sizeSource = sizeSourceId ? document.getElementById(sizeSourceId) : null;
        const requestedSize = Number.parseInt(sizeSource?.value || '', 10);
        const allowedSizes = [150, 180, 220];

        return allowedSizes.includes(requestedSize) ? requestedSize : 180;
    },

    getBulkQrSizeLabel(size) {
        const labels = {
            150: 'Small',
            180: 'Medium',
            220: 'Large',
        };

        return labels[size] || 'Medium';
    },

    getBulkQrPrintDimension(size) {
        const dimensions = {
            150: 220,
            180: 320,
            220: 560,
        };

        return dimensions[size] || 320;
    },

    async renderBulkQrMarkup(qrData, size = 180) {
        const tempContainer = document.createElement('div');
        const qrCode = this.createQRCode(qrData, size, 'canvas');

        qrCode.append(tempContainer);
        const canvas = await this.waitForBulkQrCanvas(tempContainer);

        if (!canvas) {
            return tempContainer.innerHTML;
        }

        return `<img src="${canvas.toDataURL('image/png')}" width="${size}" height="${size}" alt="Fish box QR code">`;
    },

    waitForBulkQrCanvas(container) {
        return new Promise((resolve) => {
            let attempts = 0;
            const findCanvas = () => {
                const canvas = container.querySelector('canvas');

                if (canvas && this.isCanvasReadyForPrint(canvas)) {
                    resolve(canvas);
                    return;
                }

                attempts += 1;

                if (attempts >= 90) {
                    resolve(canvas || null);
                    return;
                }

                window.requestAnimationFrame(findCanvas);
            };

            findCanvas();
        });
    },

    isCanvasReadyForPrint(canvas) {
        if (!canvas || canvas.width === 0 || canvas.height === 0) {
            return false;
        }

        try {
            const context = canvas.getContext('2d', { willReadFrequently: true });

            if (!context) {
                return true;
            }

            const sampleSize = Math.min(canvas.width, canvas.height, 80);
            const offsetX = Math.max(0, Math.floor((canvas.width - sampleSize) / 2));
            const offsetY = Math.max(0, Math.floor((canvas.height - sampleSize) / 2));
            const pixels = context.getImageData(offsetX, offsetY, sampleSize, sampleSize).data;

            for (let index = 0; index < pixels.length; index += 4) {
                const red = pixels[index];
                const green = pixels[index + 1];
                const blue = pixels[index + 2];
                const alpha = pixels[index + 3];

                if (alpha > 0 && (red < 245 || green < 245 || blue < 245)) {
                    return true;
                }
            }
        } catch (error) {
            return true;
        }

        return false;
    },

    async printBulkQRCodes(button) {
        if (button.disabled) {
            return;
        }

        const fishBoxes = this.getBulkPrintBoxes(button);

        if (fishBoxes.length === 0) {
            this.notify('No fish boxes match the current filters for bulk QR printing.', 'info');
            return;
        }

        const originalContent = button.innerHTML;
        const qrSize = this.getBulkQrPrintSize(button);
        const printQrSize = this.getBulkQrPrintDimension(qrSize);
        const printWindow = this.openEdgePrintWindow();
        button.disabled = true;
        button.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span>Preparing QR...</span>
        `;

        try {
            const qrCards = await Promise.all(
                fishBoxes.map(async (fishBox) => ({
                    ...fishBox,
                    qrMarkup: await this.renderBulkQrMarkup(fishBox.qr_code, printQrSize),
                }))
            );
            await this.printDocument(
                this.buildBulkPrintDocument(qrCards, button.dataset.filterSummary || '', qrSize, printQrSize),
                printWindow
            );
        } catch (error) {
            if (printWindow && !printWindow.closed) {
                printWindow.close();
            }
            this.notify('Bulk QR print could not be prepared. Please try again.', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = originalContent;
        }
    },

    isMicrosoftEdge() {
        return /\bEdg\//.test(window.navigator.userAgent);
    },

    openEdgePrintWindow() {
        if (!this.isMicrosoftEdge()) {
            return null;
        }

        const printWindow = window.open('', '_blank');

        if (!printWindow) {
            return null;
        }

        printWindow.document.open();
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="utf-8">
                    <title>Preparing QR Print</title>
                    <style>
                        body {
                            margin: 0;
                            min-height: 100vh;
                            display: grid;
                            place-items: center;
                            font-family: Arial, sans-serif;
                            color: #0f172a;
                            background: #ffffff;
                        }
                    </style>
                </head>
                <body>
                    <p>Preparing QR print...</p>
                </body>
            </html>
        `);
        printWindow.document.close();

        return printWindow;
    },

    waitForPrintDocument(iframeDocument) {
        const waitForReadyState = new Promise((resolve) => {
            if (iframeDocument.readyState === 'complete') {
                resolve();
                return;
            }

            iframeDocument.addEventListener('readystatechange', () => {
                if (iframeDocument.readyState === 'complete') {
                    resolve();
                }
            }, { once: true });
        });

        const waitForFonts = iframeDocument.fonts?.ready?.catch(() => null) || Promise.resolve();

        return Promise.race([
            Promise.all([waitForReadyState, waitForFonts]),
            new Promise((resolve) => window.setTimeout(resolve, 1200)),
        ]);
    },

    async printDocument(printMarkup, printWindow = null) {
        if (printWindow && !printWindow.closed) {
            await this.printWindowDocument(printWindow, printMarkup);
            return;
        }

        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '1px';
        iframe.style.height = '1px';
        iframe.style.border = '0';
        iframe.style.opacity = '0';
        iframe.setAttribute('aria-hidden', 'true');

        document.body.appendChild(iframe);

        const iframeWindow = iframe.contentWindow;
        const iframeDocument = iframeWindow?.document;

        if (!iframeWindow || !iframeDocument) {
            iframe.remove();
            throw new Error('Print frame could not be created.');
        }

        let cleanupTimer = null;
        const cleanup = () => {
            if (cleanupTimer) {
                window.clearTimeout(cleanupTimer);
            }

            iframe.remove();
        };

        iframeDocument.open();
        iframeDocument.write(printMarkup);
        iframeDocument.close();

        await this.waitForPrintDocument(iframeDocument);

        iframeWindow.addEventListener('afterprint', cleanup, { once: true });
        cleanupTimer = window.setTimeout(cleanup, 60000);

        iframeWindow.focus();
        iframeWindow.print();
    },

    async printWindowDocument(printWindow, printMarkup) {
        const printDocument = printWindow.document;

        printDocument.open();
        printDocument.write(printMarkup);
        printDocument.close();

        await this.waitForPrintDocument(printDocument);

        printWindow.focus();
        window.setTimeout(() => {
            printWindow.print();
        }, 250);
    },

    buildBulkPrintDocument(fishBoxes, filterSummary, layoutSize = 180, qrSize = 320) {
        const cardsPerPage = layoutSize <= 150 ? 4 : layoutSize >= 220 ? 1 : 2;
        const gridColumns = cardsPerPage === 1 ? 1 : 2;
        const cardMinHeight = cardsPerPage === 1 ? '250mm' : '125mm';
        const pageChunks = [];

        for (let index = 0; index < fishBoxes.length; index += cardsPerPage) {
            pageChunks.push(fishBoxes.slice(index, index + cardsPerPage));
        }

        const renderCard = (fishBox) => `
            <article class="qr-card">
                <div class="qr-card__code">
                    ${fishBox.qrMarkup}
                </div>
                <div class="qr-card__meta">
                    <h2>${this.escapeHtml(fishBox.name)}</h2>
                    <p>${this.escapeHtml(fishBox.fish_name || 'Unassigned')}</p>
                    <span class="status">${this.escapeHtml(fishBox.status)}</span>
                    <div class="qr-value">${this.escapeHtml(fishBox.qr_code)}</div>
                </div>
            </article>
        `;

        const pagesMarkup = pageChunks.map((chunk) => `
            <main class="sheet">
                <section class="qr-grid">
                    ${chunk.map(renderCard).join('')}
                </section>
            </main>
        `).join('');

        return `
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="utf-8">
                    <title>Broker Fish Box QR Codes</title>
                    <style>
                        * {
                            box-sizing: border-box;
                        }

                        body {
                            margin: 0;
                            font-family: Arial, sans-serif;
                            color: #0f172a;
                            background: #ffffff;
                        }

                        .sheet {
                            min-height: 273mm;
                            padding: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            break-after: page;
                            page-break-after: always;
                        }

                        .sheet:last-child {
                            break-after: auto;
                            page-break-after: auto;
                        }

                        .qr-grid {
                            width: 100%;
                            display: grid;
                            grid-template-columns: repeat(${gridColumns}, minmax(0, 1fr));
                            gap: 18px;
                        }

                        .qr-card {
                            border: 1px solid #cbd5e1;
                            border-radius: 18px;
                            padding: 22px;
                            min-height: ${cardMinHeight};
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            gap: 18px;
                            page-break-inside: avoid;
                            break-inside: avoid;
                        }

                        .qr-card__code {
                            width: ${qrSize}px;
                            height: ${qrSize}px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        .qr-card__code svg,
                        .qr-card__code canvas,
                        .qr-card__code img {
                            width: ${qrSize}px;
                            height: ${qrSize}px;
                            display: block;
                        }

                        .qr-card__meta {
                            width: 100%;
                            text-align: center;
                        }

                        .qr-card__meta h2 {
                            margin: 0;
                            font-size: 18px;
                            line-height: 1.2;
                        }

                        .qr-card__meta p {
                            margin: 6px 0 10px;
                            color: #475569;
                            font-size: 13px;
                        }

                        .status {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 999px;
                            background: #dbeafe;
                            color: #1d4ed8;
                            font-size: 11px;
                            font-weight: 700;
                            letter-spacing: 0.08em;
                            padding: 5px 10px;
                            text-transform: uppercase;
                        }

                        .qr-value {
                            margin-top: 12px;
                            color: #64748b;
                            font-family: "Courier New", monospace;
                            font-size: 11px;
                            word-break: break-all;
                        }

                        @page {
                            size: A4 portrait;
                            margin: 12mm;
                        }

                        @media print {
                            .sheet {
                                padding: 0;
                            }
                        }
                    </style>
                </head>
                <body>
                    ${pagesMarkup}
                </body>
            </html>
        `;
    },

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

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
    },

    hideQRModal() {
        const modal = document.getElementById('qr-modal');
        if (modal) {
            modal.remove();
        }

        this.currentQRCode = null;
    },
};

document.addEventListener('DOMContentLoaded', () => {
    window.QRCodeModal.init();
});
