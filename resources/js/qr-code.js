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

    getQRCodeOptions(data, size = 256) {
        return {
            width: size,
            height: size,
            type: 'svg',
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

    createQRCode(data, size = 256) {
        return new QRCodeStyling(this.getQRCodeOptions(data, size));
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
            console.error('Unable to parse bulk QR data.', error);
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

    async renderBulkQrMarkup(qrData, size = 180) {
        const tempContainer = document.createElement('div');
        const qrCode = this.createQRCode(qrData, size);

        qrCode.append(tempContainer);
        await new Promise((resolve) => {
            window.requestAnimationFrame(() => resolve());
        });

        return tempContainer.innerHTML;
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
                    qrMarkup: await this.renderBulkQrMarkup(fishBox.qr_code, qrSize),
                }))
            );
            this.printDocument(this.buildBulkPrintDocument(qrCards, button.dataset.filterSummary || '', qrSize));
        } catch (error) {
            console.error('Bulk QR print failed.', error);
            this.notify('Bulk QR print could not be prepared. Please try again.', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = originalContent;
        }
    },

    printDocument(printMarkup) {
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.setAttribute('aria-hidden', 'true');

        document.body.appendChild(iframe);

        const iframeWindow = iframe.contentWindow;
        const iframeDocument = iframeWindow?.document;

        if (!iframeWindow || !iframeDocument) {
            iframe.remove();
            throw new Error('Print frame could not be created.');
        }

        iframe.onload = () => {
            window.setTimeout(() => {
                iframeWindow.focus();
                iframeWindow.print();

                window.setTimeout(() => {
                    iframe.remove();
                }, 1000);
            }, 250);
        };

        iframeDocument.open();
        iframeDocument.write(printMarkup);
        iframeDocument.close();
    },

    buildBulkPrintDocument(fishBoxes, filterSummary, qrSize = 180) {
        const generatedAt = new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date());
        const gridColumns = qrSize <= 150 ? 4 : qrSize >= 220 ? 2 : 3;
        const cardMinHeight = qrSize + 110;

        const filterLine = filterSummary
            ? `<p class="subtitle">${this.escapeHtml(filterSummary)}</p>`
            : `<p class="subtitle">All fish boxes matching the current filters are included in this printout.</p>`;

        const cardsMarkup = fishBoxes.map((fishBox) => `
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
                            padding: 28px 30px 36px;
                        }

                        .sheet-header {
                            border-bottom: 2px solid #0f172a;
                            margin-bottom: 24px;
                            padding-bottom: 14px;
                        }

                        .sheet-header h1 {
                            margin: 0 0 8px;
                            font-size: 24px;
                        }

                        .sheet-header p {
                            margin: 4px 0;
                            color: #475569;
                            font-size: 13px;
                        }

                        .summary-row {
                            display: flex;
                            justify-content: space-between;
                            align-items: baseline;
                            gap: 16px;
                            margin-top: 10px;
                        }

                        .summary-row strong {
                            font-size: 14px;
                        }

                        .qr-grid {
                            display: grid;
                            grid-template-columns: repeat(${gridColumns}, minmax(0, 1fr));
                            gap: 16px;
                        }

                        .qr-card {
                            border: 1px solid #cbd5e1;
                            border-radius: 18px;
                            padding: 18px;
                            min-height: ${cardMinHeight}px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: space-between;
                            page-break-inside: avoid;
                        }

                        .qr-card__code {
                            width: ${qrSize}px;
                            height: ${qrSize}px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        .qr-card__code svg {
                            width: ${qrSize}px;
                            height: ${qrSize}px;
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
                    <main class="sheet">
                        <header class="sheet-header">
                            <h1>Fish Box QR Codes</h1>
                            ${filterLine}
                            <div class="summary-row">
                                <strong>Total Boxes: ${fishBoxes.length}</strong>
                                <span>QR Size: ${this.escapeHtml(this.getBulkQrSizeLabel(qrSize))} (${qrSize}px) | Generated: ${this.escapeHtml(generatedAt)}</span>
                            </div>
                        </header>

                        <section class="qr-grid">
                            ${cardsMarkup}
                        </section>
                    </main>

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

        window.alert(message);
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
