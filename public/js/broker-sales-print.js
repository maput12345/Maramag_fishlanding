/**
 * Print broker sales report using the currently filtered range.
 */
window.printBrokerSales = function(brokerId, brokerName, stallName) {
    // Get date range from URL or form inputs
    const urlParams = new URLSearchParams(window.location.search);
    let dateFrom = urlParams.get('date_from');
    let dateTo = urlParams.get('date_to');

    // If not in URL, try to get from the form inputs
    if (!dateFrom) {
        const dateFromInput = document.querySelector('input[name="date_from"]');
        if (dateFromInput) dateFrom = dateFromInput.value;
    }
    if (!dateTo) {
        const dateToInput = document.querySelector('input[name="date_to"]');
        if (dateToInput) dateTo = dateToInput.value;
    }

    const formatDate = (dateStr) => {
        if (!dateStr) return '';
        const parts = dateStr.split('-').map(Number);
        if (parts.length !== 3 || parts.some(Number.isNaN)) return dateStr;

        const date = new Date(parts[0], parts[1] - 1, parts[2]);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    };

    // Find the broker's table in the DOM
    const brokerCard = document.querySelector(`[data-broker-id="${brokerId}"]`);
    if (!brokerCard) {
        toastr.error('Broker data not found');
        return;
    }

    dateFrom = brokerCard.dataset.receiptDateFrom || dateFrom;
    dateTo = brokerCard.dataset.receiptDateTo || dateTo;

    const receiptDate = brokerCard.dataset.receiptDate || dateTo || dateFrom || new Date().toISOString().slice(0, 10);
    const formattedReceiptDate = formatDate(receiptDate);
    const formattedDateFrom = formatDate(dateFrom);
    const formattedDateTo = formatDate(dateTo);
    const hasRange = Boolean(dateFrom && dateTo && dateFrom !== dateTo);
    const selectedPeriodLabel = hasRange
        ? `${formattedDateFrom} to ${formattedDateTo}`
        : formattedDateTo || formattedDateFrom || formattedReceiptDate;
    const salesCount = Number(brokerCard.dataset.receiptSalesCount || 0);
    const soldFishBoxesCount = Number(brokerCard.dataset.receiptFishboxCount || 0);
    const watermarkLogoUrl = brokerCard.dataset.receiptWatermarkLogoUrl || '';

    let receiptSales = [];
    try {
        receiptSales = JSON.parse(brokerCard.dataset.receiptSales || '[]');
    } catch (error) {
        receiptSales = [];
    }

    let missingBoxes = [];
    try {
        missingBoxes = JSON.parse(brokerCard.dataset.brokerMissingBoxesForReceipt || '[]');
    } catch (error) {
        missingBoxes = [];
    }

    const missingBoxesCount = missingBoxes.length;
    const tableHTML = receiptSales.length > 0
        ? `
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; background-color: #f3f4f6;">Date</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; background-color: #f3f4f6;">Buyer</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; background-color: #f3f4f6;">Fish Name</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; background-color: #f3f4f6;">Quantity</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; background-color: #f3f4f6;">Fish Boxes</th>
                    </tr>
                </thead>
                <tbody>
                    ${receiptSales.map((saleRow) => `
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 12px;">${saleRow.date}</td>
                            <td style="border: 1px solid #ddd; padding: 12px;">${saleRow.buyer}</td>
                            <td style="border: 1px solid #ddd; padding: 12px;">${saleRow.fish_name}</td>
                            <td style="border: 1px solid #ddd; padding: 12px;">${saleRow.quantity}</td>
                            <td style="border: 1px solid #ddd; padding: 12px;">${(saleRow.fish_boxes || []).join(', ') || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `
        : `
            <div style="margin-top: 20px; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 18px 20px; background: #f8fafc;">
                <h2 style="margin: 0 0 8px; font-size: 18px; color: #0f172a;">No Sales Recorded</h2>
                <p style="margin: 0; font-size: 13px; color: #475569;">No sales were recorded for this broker in the selected filter${selectedPeriodLabel ? ` (${selectedPeriodLabel})` : ''}.</p>
            </div>
        `;

    const summaryHTML = `
        <div style="display: flex; gap: 12px; margin: 20px 0; flex-wrap: wrap;">
            <div style="flex: 1 1 180px; border: 1px solid #dbeafe; border-radius: 12px; padding: 14px 16px; background: #eff6ff;">
                <div style="font-size: 12px; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.08em;">Sales in Filter</div>
                <div style="margin-top: 6px; font-size: 24px; font-weight: 700; color: #111827;">${salesCount}</div>
            </div>
            <div style="flex: 1 1 180px; border: 1px solid #fed7aa; border-radius: 12px; padding: 14px 16px; background: #fff7ed;">
                <div style="font-size: 12px; color: #c2410c; text-transform: uppercase; letter-spacing: 0.08em;">Sold Fish Boxes</div>
                <div style="margin-top: 6px; font-size: 24px; font-weight: 700; color: #111827;">${soldFishBoxesCount}</div>
            </div>
            <div style="flex: 1 1 180px; border: 1px solid #fecaca; border-radius: 12px; padding: 14px 16px; background: #fef2f2;">
                <div style="font-size: 12px; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.08em;">Missing Boxes</div>
                <div style="margin-top: 6px; font-size: 24px; font-weight: 700; color: #111827;">${missingBoxesCount}</div>
            </div>
        </div>
    `;

    const missingBoxesHTML = missingBoxesCount > 0
        ? `
            <div style="margin-top: 24px; border: 1px solid #fecaca; border-radius: 14px; padding: 18px 20px; background: #fff5f5;">
                <h2 style="margin: 0 0 8px; font-size: 18px; color: #991b1b;">Missing Boxes</h2>
                <p style="margin: 0 0 12px; font-size: 13px; color: #7f1d1d;">
                    These fish boxes were still marked missing as of ${formattedReceiptDate}. They stay on the receipt until a return is logged.
                </p>
                <ul style="margin: 0; padding-left: 18px; color: #1f2937;">
                    ${missingBoxes.map((fishBox) => `<li style="margin-bottom: 6px;"><strong>${fishBox.name}</strong> <span style="color: #6b7280;">(${fishBox.qr_code})</span>${fishBox.reported_at ? ` <span style="color: #991b1b;">- reported ${fishBox.reported_at}</span>` : ''}</li>`).join('')}
                </ul>
            </div>
        `
        : `
            <div style="margin-top: 24px; border: 1px solid #d1fae5; border-radius: 14px; padding: 18px 20px; background: #ecfdf5;">
                <h2 style="margin: 0 0 8px; font-size: 18px; color: #065f46;">Missing Boxes</h2>
                <p style="margin: 0; font-size: 13px; color: #047857;">No fish boxes were still marked missing for this broker on ${formattedReceiptDate}.</p>
            </div>
        `;

    // Build stall info
    const stallInfo = stallName ? `<p><strong>Stall:</strong> ${stallName}</p>` : '';
    const watermarkHTML = watermarkLogoUrl
        ? `
            <div class="print-watermark" aria-hidden="true">
                <img src="${watermarkLogoUrl}" alt="">
            </div>
        `
        : '';

    // Create print content
    const printContent = `
        <html>
            <head>
                <title>Sales Report - ${brokerName}</title>
                <style>
                    @media print {
                        body { margin: 0; }
                        @page { margin: 1cm; }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        padding: 20px;
                        position: relative;
                    }
                    .print-sheet {
                        position: relative;
                        min-height: calc(100vh - 40px);
                    }
                    .print-shell {
                        position: relative;
                        z-index: 1;
                    }
                    .print-watermark {
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        width: min(700px, 84vw);
                        display: block;
                        pointer-events: none;
                        z-index: 0;
                        text-align: center;
                    }
                    .print-watermark img {
                        display: block;
                        width: 100%;
                        max-width: 100%;
                        margin: 0 auto;
                        opacity: 0.06;
                        filter: grayscale(100%);
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 20px;
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 24px;
                    }
                    .header p {
                        margin: 5px 0;
                        color: #666;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 12px;
                        text-align: left;
                    }
                    th {
                        background-color: #f3f4f6;
                    }
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        font-size: 12px;
                        color: #666;
                        border-top: 1px solid #ddd;
                        padding-top: 20px;
                    }
                    @media print {
                        body {
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="print-sheet">
                    ${watermarkHTML}
                    <div class="print-shell">
                        <div class="header">
                            <h1>Sales Report</h1>
                            <p><strong>Broker:</strong> ${brokerName}</p>
                            ${stallInfo}
                            ${selectedPeriodLabel ? `<p><strong>Period:</strong> ${selectedPeriodLabel}</p>` : ''}
                        </div>
                        ${summaryHTML}
                        ${tableHTML}
                        ${missingBoxesHTML}
                        <div class="footer">
                            <p>POS System - Sales Report</p>
                            <p><strong>Generated:</strong> ${new Date().toLocaleString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}</p>
                        </div>
                    </div>
                </div>
            </body>
        </html>
    `;

    // Create iframe for printing
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    document.body.appendChild(iframe);

    const iframeDoc = iframe.contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(printContent);
    iframeDoc.close();

    // Print after content is loaded
    iframe.onload = function() {
        const triggerPrint = () => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();

            // Remove iframe after printing
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 1000);
        };

        const watermarkImage = iframe.contentWindow.document.querySelector('.print-watermark img');
        if (watermarkImage && !watermarkImage.complete) {
            const completeAndPrint = () => setTimeout(triggerPrint, 250);
            watermarkImage.addEventListener('load', completeAndPrint, { once: true });
            watermarkImage.addEventListener('error', completeAndPrint, { once: true });
            return;
        }

        setTimeout(triggerPrint, 250);
    };
};

