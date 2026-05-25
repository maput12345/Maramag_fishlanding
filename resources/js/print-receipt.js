// Print Receipt functionality
class PrintReceipt {
    static print(receiptElementId, receiptTitle = 'Receipt') {
        const receiptContent = document.getElementById(receiptElementId);

        if (!receiptContent) {
            PrintReceipt.notify('Receipt content could not be found.', 'error');
            return;
        }

        const watermarkLogoUrl = receiptContent.dataset.watermarkLogoUrl || '';

        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        if (!printWindow) {
            PrintReceipt.notify('Please allow popups to print the receipt.', 'warning');
            return;
        }

        // Get the receipt HTML
        const receiptHTML = receiptContent.innerHTML;
        const watermarkHTML = watermarkLogoUrl
            ? `
                <div class="receipt-watermark" aria-hidden="true">
                    <img src="${watermarkLogoUrl}" alt="">
                </div>
            `
            : '';

        // Create the complete HTML document for printing
        const printHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${receiptTitle}</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        margin: 0;
                        padding: 20px;
                        background: white;
                        color: #333;
                        position: relative;
                    }
                    .print-sheet {
                        position: relative;
                        min-height: calc(100vh - 40px);
                    }
                    .print-sheet__content {
                        position: relative;
                        z-index: 1;
                    }
                    .receipt-watermark {
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        width: min(620px, 82vw);
                        display: block;
                        pointer-events: none;
                        z-index: 0;
                        text-align: center;
                    }
                    .receipt-watermark img {
                        display: block;
                        width: 100%;
                        max-width: 100%;
                        margin: 0 auto;
                        opacity: 0.07;
                        filter: grayscale(100%);
                    }
                    .max-w-md {
                        max-width: none;
                        width: 100%;
                    }
                    .text-center {
                        text-align: center;
                    }
                    .border-b {
                        border-bottom: 1px solid #e5e7eb;
                    }
                    .border-t {
                        border-top: 1px solid #e5e7eb;
                    }
                    .border-gray-200 {
                        border-color: #e5e7eb;
                    }
                    .pb-4 {
                        padding-bottom: 1rem;
                    }
                    .pt-4 {
                        padding-top: 1rem;
                    }
                    .mb-4 {
                        margin-bottom: 1rem;
                    }
                    .mb-2 {
                        margin-bottom: 0.5rem;
                    }
                    .mb-3 {
                        margin-bottom: 0.75rem;
                    }
                    .text-2xl {
                        font-size: 1.5rem;
                    }
                    .text-sm {
                        font-size: 0.875rem;
                    }
                    .text-xs {
                        font-size: 0.75rem;
                    }
                    .font-bold {
                        font-weight: 700;
                    }
                    .font-semibold {
                        font-weight: 600;
                    }
                    .font-medium {
                        font-weight: 500;
                    }
                    .text-gray-900 {
                        color: #111827;
                    }
                    .text-gray-600 {
                        color: #4b5563;
                    }
                    .text-gray-500 {
                        color: #6b7280;
                    }
                    .text-green-600 {
                        color: #059669;
                    }
                    .text-orange-600 {
                        color: #ea580c;
                    }
                    .flex {
                        display: flex;
                    }
                    .justify-between {
                        justify-content: space-between;
                    }
                    .items-start {
                        align-items: flex-start;
                    }
                    .space-y-2 > * + * {
                        margin-top: 0.5rem;
                    }
                    .space-y-3 > * + * {
                        margin-top: 0.75rem;
                    }
                    .flex-1 {
                        flex: 1 1 0%;
                    }
                    @media print {
                        body {
                            margin: 0;
                            padding: 0;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        @page {
                            margin: 0.5in;
                            size: A4;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="print-sheet">
                    ${watermarkHTML}
                    <div class="print-sheet__content">
                        ${receiptHTML}
                    </div>
                </div>
            </body>
            </html>
        `;

        // Write the HTML to the print window
        printWindow.document.write(printHTML);
        printWindow.document.close();

        PrintReceipt.printWhenReady(printWindow);
    }

    static async printWhenReady(printWindow) {
        const printDocument = printWindow.document;
        const watermarkImage = printDocument.querySelector('.receipt-watermark img');

        await Promise.race([
            new Promise((resolve) => {
                if (!watermarkImage || watermarkImage.complete) {
                    resolve();
                    return;
                }

                watermarkImage.addEventListener('load', resolve, { once: true });
                watermarkImage.addEventListener('error', resolve, { once: true });
            }),
            new Promise((resolve) => window.setTimeout(resolve, 1200)),
        ]);

        await new Promise((resolve) => window.requestAnimationFrame(() => {
            window.requestAnimationFrame(resolve);
        }));

        const closePrintWindow = () => {
            if (!printWindow.closed) {
                printWindow.close();
            }
        };

        printWindow.addEventListener('afterprint', () => {
            window.setTimeout(closePrintWindow, 400);
        }, { once: true });

        window.setTimeout(closePrintWindow, 60000);

        printWindow.focus();
        window.setTimeout(() => {
            printWindow.print();
        }, 250);
    }

    static notify(message, type = 'info') {
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

// Make it globally available
window.printReceipt = PrintReceipt.print;
