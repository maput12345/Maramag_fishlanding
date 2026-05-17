/**
 * Print broker sales report using the currently filtered range.
 */
window.printBrokerSales = async function(brokerId, brokerName, stallName) {
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

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

    const formatCurrency = (amount) => `₱${Number(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;

    const getInclusiveDayCount = (from, to) => {
        if (!from && !to) {
            return 1;
        }

        const parseDate = (value) => {
            if (!value) return null;
            const parts = value.split('-').map(Number);
            if (parts.length !== 3 || parts.some(Number.isNaN)) return null;

            return new Date(parts[0], parts[1] - 1, parts[2]);
        };

        const startDate = parseDate(from || to);
        const endDate = parseDate(to || from);

        if (!startDate || !endDate) {
            return 1;
        }

        const millisecondsPerDay = 24 * 60 * 60 * 1000;
        const diff = Math.round((endDate - startDate) / millisecondsPerDay);

        return Math.max(diff + 1, 1);
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
    let soldFishBoxesCount = Number(brokerCard.dataset.receiptFishboxCount || 0);
    const leeoCommissionPerBox = Number(brokerCard.dataset.receiptLeeoCommissionPerBox || 15);
    const dailyBrokerFee = 100;
    const watermarkLogoUrl = brokerCard.dataset.receiptWatermarkLogoUrl || '';

    const receiptDataUrl = brokerCard.dataset.receiptDataUrl;

    if (receiptDataUrl) {
        try {
            const requestUrl = new URL(receiptDataUrl, window.location.origin);
            if (dateFrom) {
                requestUrl.searchParams.set('date_from', dateFrom);
            }
            if (dateTo) {
                requestUrl.searchParams.set('date_to', dateTo);
            }

            const freshResponse = await fetch(requestUrl.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (freshResponse.ok) {
                const freshData = await freshResponse.json();
                soldFishBoxesCount = Number(freshData.fish_box_count || soldFishBoxesCount);
                dateFrom = freshData.receipt_date_from || dateFrom;
                dateTo = freshData.receipt_date_to || dateTo;
            }
        } catch {
        }
    }

    const billingDays = getInclusiveDayCount(dateFrom, dateTo);
    const commissionPayableAmount = soldFishBoxesCount * leeoCommissionPerBox;
    const dailyFeeAmount = billingDays * dailyBrokerFee;
    const totalPayableAmount = commissionPayableAmount + dailyFeeAmount;

    const summaryHTML = `
        <div class="receipt-section">
            <h2 class="section-title">Broker Payable Summary</h2>
            <div class="summary-list">
                <div class="summary-row">
                    <span>Sold Fish Boxes:</span>
                    <span>${soldFishBoxesCount}</span>
                </div>
                <div class="summary-row">
                    <span>LEEO Commission:</span>
                    <span>${formatCurrency(commissionPayableAmount)}</span>
                </div>
                <div class="summary-note">${soldFishBoxesCount} box(es) x ${formatCurrency(leeoCommissionPerBox)}</div>
                <div class="summary-row">
                    <span>Daily Fee:</span>
                    <span>${formatCurrency(dailyFeeAmount)}</span>
                </div>
                <div class="summary-note">${billingDays} day(s) x ${formatCurrency(dailyBrokerFee)}</div>
                <div class="summary-row summary-total">
                    <span>Total Payable to LEEO:</span>
                    <span>${formatCurrency(totalPayableAmount)}</span>
                </div>
            </div>
        </div>
    `;

    // Build stall info
    const stallInfo = stallName ? `<p>${escapeHtml(stallName)}</p>` : '';
    const watermarkHTML = watermarkLogoUrl
        ? `
            <div class="receipt-watermark" aria-hidden="true">
                <img src="${escapeHtml(watermarkLogoUrl)}" alt="">
            </div>
        `
        : '';

    // Create print content
    const printContent = `
        <html>
            <head>
                <title>Broker Receipt - ${escapeHtml(brokerName)}</title>
                <style>
                    * {
                        box-sizing: border-box;
                    }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        margin: 0;
                        padding: 20px;
                        background: #ffffff;
                        color: #111827;
                        position: relative;
                    }
                    .print-sheet {
                        position: relative;
                        min-height: calc(100vh - 40px);
                        max-width: 720px;
                        margin: 0 auto;
                    }
                    .print-shell {
                        position: relative;
                        z-index: 1;
                    }
                    .receipt-watermark {
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        width: min(540px, 78vw);
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
                        opacity: 0.08;
                        filter: grayscale(100%);
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 18px;
                        border-bottom: 1px solid #e5e7eb;
                        padding-bottom: 20px;
                    }
                    .header h1 {
                        margin: 0 0 8px;
                        font-size: 24px;
                        line-height: 1.2;
                        color: #111827;
                    }
                    .header p {
                        margin: 4px 0;
                        font-size: 12px;
                        color: #6b7280;
                    }
                    .receipt-info,
                    .receipt-section {
                        margin-bottom: 18px;
                        border-top: 1px solid #e5e7eb;
                        padding-top: 16px;
                    }
                    .receipt-info {
                        border-top: 0;
                        padding-top: 0;
                    }
                    .section-title {
                        margin: 0 0 12px;
                        font-size: 14px;
                        font-weight: 700;
                        color: #111827;
                    }
                    .info-row,
                    .summary-row {
                        display: flex;
                        justify-content: space-between;
                        gap: 24px;
                        margin-bottom: 8px;
                        font-size: 13px;
                    }
                    .info-row span:first-child,
                    .summary-row span:first-child {
                        color: #6b7280;
                    }
                    .info-row span:last-child,
                    .summary-row span:last-child {
                        color: #111827;
                        font-weight: 600;
                        text-align: right;
                    }
                    .summary-note {
                        margin: -4px 0 10px;
                        color: #6b7280;
                        font-size: 12px;
                        text-align: right;
                    }
                    .summary-total {
                        border-top: 1px solid #e5e7eb;
                        margin-top: 10px;
                        padding-top: 10px;
                    }
                    .summary-total span {
                        font-weight: 700;
                    }
                    .summary-total span:last-child {
                        color: #059669;
                    }
                    .footer {
                        margin-top: 20px;
                        text-align: center;
                        font-size: 12px;
                        color: #6b7280;
                        border-top: 1px solid #e5e7eb;
                        padding-top: 20px;
                    }
                    .footer p {
                        margin: 4px 0;
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
                    <div class="print-shell">
                        <div class="header">
                            <h1>${escapeHtml(brokerName)}</h1>
                            ${stallInfo}
                            <p>Broker Receipt</p>
                        </div>
                        <div class="receipt-info">
                            <div class="info-row">
                                <span>Period:</span>
                                <span>${escapeHtml(selectedPeriodLabel)}</span>
                            </div>
                            <div class="info-row">
                                <span>Generated:</span>
                                <span>${escapeHtml(new Date().toLocaleString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }))}</span>
                            </div>
                        </div>
                        ${summaryHTML}
                        <div class="footer">
                            <p>POS System - Broker Receipt</p>
                            <p>Thank you for your service.</p>
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

        const watermarkImage = iframe.contentWindow.document.querySelector('.receipt-watermark img');
        if (watermarkImage && !watermarkImage.complete) {
            const completeAndPrint = () => setTimeout(triggerPrint, 250);
            watermarkImage.addEventListener('load', completeAndPrint, { once: true });
            watermarkImage.addEventListener('error', completeAndPrint, { once: true });
            return;
        }

        setTimeout(triggerPrint, 250);
    };
};
