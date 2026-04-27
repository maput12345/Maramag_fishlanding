/**
 * Print broker sales report using the currently filtered range.
 */
window.printBrokerSales = async function(brokerId, brokerName, stallName) {
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

    const formatCurrency = (amount) => `PHP ${Number(amount || 0).toLocaleString('en-US', {
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
    const leeoCommissionPerBox = Number(brokerCard.dataset.receiptLeeoCommissionPerBox || 5);
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
        } catch (error) {
            console.error('Unable to refresh broker receipt data before printing.', error);
        }
    }

    const billingDays = getInclusiveDayCount(dateFrom, dateTo);
    const commissionPayableAmount = soldFishBoxesCount * leeoCommissionPerBox;
    const dailyFeeAmount = billingDays * dailyBrokerFee;
    const totalPayableAmount = commissionPayableAmount + dailyFeeAmount;

    const summaryHTML = `
        <div style="margin: 24px 0 0;">
            <div style="border: 1px solid #dbe4f0; border-radius: 16px; overflow: hidden; background: #ffffff;">
                <div style="padding: 18px 22px; border-bottom: 1px solid #e5e7eb; background: #f8fafc;">
                    <h2 style="margin: 0; font-size: 20px; color: #0f172a;">Broker Payable Summary</h2>
                </div>
                <div style="padding: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e5e7eb; gap: 16px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">Sold Fish Boxes</div>
                            <div style="font-size: 12px; color: #64748b;">Total boxes sold in the selected period</div>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: #111827;">${soldFishBoxesCount}</div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e5e7eb; gap: 16px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">LEEO Commission</div>
                            <div style="font-size: 12px; color: #64748b;">${soldFishBoxesCount} box(es) x ${formatCurrency(leeoCommissionPerBox)}</div>
                        </div>
                        <div style="font-size: 18px; font-weight: 700; color: #111827;">${formatCurrency(commissionPayableAmount)}</div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e5e7eb; gap: 16px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">Daily Fee</div>
                            <div style="font-size: 12px; color: #64748b;">${billingDays} day(s) x ${formatCurrency(dailyBrokerFee)}</div>
                        </div>
                        <div style="font-size: 18px; font-weight: 700; color: #111827;">${formatCurrency(dailyFeeAmount)}</div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0 4px; gap: 16px;">
                        <div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a;">Total Payable to LEEO</div>
                        </div>
                        <div style="font-size: 26px; font-weight: 800; color: #047857;">${formatCurrency(totalPayableAmount)}</div>
                    </div>
                </div>
            </div>
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
                            <h1>Broker Receipt</h1>
                            <p><strong>Broker:</strong> ${brokerName}</p>
                            ${stallInfo}
                            ${selectedPeriodLabel ? `<p><strong>Period:</strong> ${selectedPeriodLabel}</p>` : ''}
                        </div>
                        ${summaryHTML}
                        <div class="footer">
                            <p>POS System - Broker Receipt</p>
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

