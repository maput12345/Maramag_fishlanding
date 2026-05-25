/**
 * Sales Form Functionality
 * Handles dynamic sales detail rows, fish box selection, and calculations
 */

// Sales form configuration (initialized from server)
let SALES_CONFIG = {};

/**
 * Initialize sales form
 * @param {Object} config - Configuration object with fishBoxes, fishTypes, fishPrices, and detailIndex
 */
function initializeSalesForm(config, scope = document) {
    const normalizeFishPrices = (fishPrices) => {
        if (!fishPrices || typeof fishPrices !== 'object') {
            return {};
        }

        return Object.entries(fishPrices).reduce((carry, [key, value]) => {
            carry[String(key)] = value;
            return carry;
        }, {});
    };

    SALES_CONFIG = {
        ...config,
        fishPrices: normalizeFishPrices(config.fishPrices || {}),
    };
    const salesFormMode = SALES_CONFIG.mode || 'create';
    const root = scope && typeof scope.querySelector === 'function'
        ? scope
        : document;

    const container = root.querySelector('#sales-details-container');
    const addBtn = root.querySelector('#add-sales-detail-btn');
    const totalAmountDisplay = root.querySelector('#total-amount-display');
    const totalAmountInput = root.querySelector('#total_amount');
    const initialPaidAmountInput = root.querySelector('#initial_paid_amount');
    const initialPaymentMethodSelect = root.querySelector('#initial_payment_method');
    const initialPaymentMaxAmount = root.querySelector('#initial-payment-max-amount');
    const initialPaymentError = root.querySelector('#initial-payment-error');
    const salesForm = root.querySelector('form[data-sales-async-form]');

    if (!container || !addBtn || !totalAmountDisplay) return;
    if (container.dataset.salesFormInitialized === 'true') return;

    container.dataset.salesFormInitialized = 'true';

    const parseMoney = (value) => {
        const normalizedValue = String(value ?? '').replace(/[₱,\s]/g, '');
        const parsedValue = parseFloat(normalizedValue);

        return Number.isFinite(parsedValue) ? parsedValue : 0;
    };

    const formatMoney = (value) => {
        const numericValue = Number(value) || 0;

        return numericValue.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    const normalizeMoneyValue = (value) => parseMoney(value).toFixed(2);
    const roundMoney = (value) => Math.round((Number(value) || 0) * 100) / 100;
    const formatPercent = (value) => {
        const percent = clampDiscountPercent(value);

        return `${Number.isInteger(percent) ? percent.toFixed(0) : percent.toFixed(2)}%`;
    };

    const formatMoneyInput = (input) => {
        if (!input || input.value === '') {
            return;
        }

        input.value = formatMoney(parseMoney(input.value));
    };

    const clampDiscountPercent = (value) => Math.min(100, Math.max(0, parseMoney(value)));

    const normalizeSalesMoneyFields = () => {
        root.querySelectorAll('.unit-price-input, .discount-input, .sub-total-input, #initial_paid_amount').forEach((input) => {
            if (input && input.value !== '') {
                input.value = normalizeMoneyValue(input.value);
            }
        });

        root.querySelectorAll('.discount-value-input').forEach((input) => {
            if (!input || input.value === '') {
                return;
            }

            const row = input.closest('.sales-detail-row');
            const mode = row?.querySelector('.discount-mode-select')?.value || 'percent';
            input.value = mode === 'percent'
                ? clampDiscountPercent(input.value).toFixed(2)
                : normalizeMoneyValue(input.value);
        });

        root.querySelectorAll('.discount-percent-input').forEach((input) => {
            if (input && input.value !== '') {
                input.value = clampDiscountPercent(input.value).toFixed(2);
            }
        });
    };

    if (salesForm && salesForm.dataset.salesMoneyNormalizerBound !== 'true') {
        salesForm.dataset.salesMoneyNormalizerBound = 'true';
        salesForm.addEventListener('submit', normalizeSalesMoneyFields, { capture: true });
    }

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    root.querySelectorAll('[data-regular-buyer-picker]').forEach((picker) => {
        if (!salesForm || picker.dataset.regularBuyerBound === 'true') {
            return;
        }

        const dataElement = picker.querySelector('[data-regular-buyers-json]');
        const buyerIdInput = picker.querySelector('[data-regular-buyer-id]');
        const searchInput = picker.querySelector('[data-regular-buyer-search]');
        const results = picker.querySelector('[data-regular-buyer-results]');
        let regularBuyers = [];

        try {
            regularBuyers = JSON.parse(dataElement?.textContent || '[]');
        } catch (error) {
            regularBuyers = [];
        }

        regularBuyers = regularBuyers.map((buyer) => ({
            ...buyer,
            search: [
                buyer.name,
                buyer.first_name,
                buyer.middle_name,
                buyer.last_name,
                buyer.contact,
            ].filter(Boolean).join(' ').toLowerCase(),
        }));

        const hideResults = () => {
            results?.classList.add('hidden');
        };

        const fillBuyerFields = (buyer) => {
            const fields = {
                buyer_first_name: buyer.first_name || '',
                buyer_middle_name: buyer.middle_name || '',
                buyer_last_name: buyer.last_name || '',
                buyer_contact: buyer.contact || '',
            };

            Object.entries(fields).forEach(([id, value]) => {
                const field = salesForm.querySelector(`#${id}`);

                if (field) {
                    field.value = value;
                }
            });

            if (searchInput) {
                searchInput.value = `${buyer.name || ''}${buyer.contact ? ` - ${buyer.contact}` : ''}`.trim();
                searchInput.dataset.selectedLabel = searchInput.value;
            }

            if (buyerIdInput) {
                buyerIdInput.value = buyer.id || '';
            }

            hideResults();
        };

        const renderResults = () => {
            if (!searchInput || !results) {
                return;
            }

            const query = searchInput.value.trim().toLowerCase();

            if (query === '') {
                hideResults();
                return;
            }

            const matches = regularBuyers
                .filter((buyer) => buyer.search.includes(query))
                .slice(0, 10);

            if (matches.length === 0) {
                results.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No regular customer found. Continue typing below for a new or walk-in customer.</div>';
                results.classList.remove('hidden');
                return;
            }

            results.innerHTML = matches.map((buyer, index) => `
                <button type="button"
                        class="block w-full px-4 py-3 text-left text-sm transition-colors hover:bg-blue-50"
                        data-regular-buyer-option="${index}">
                    <span class="block font-semibold text-slate-900">${escapeHtml(buyer.name || 'Unnamed customer')}</span>
                    <span class="block text-xs text-slate-500">${escapeHtml(buyer.contact || 'No contact number')}</span>
                </button>
            `).join('');
            results.classList.remove('hidden');

            results.querySelectorAll('[data-regular-buyer-option]').forEach((button) => {
                button.addEventListener('click', () => {
                    fillBuyerFields(matches[Number(button.dataset.regularBuyerOption)]);
                });
            });
        };

        searchInput?.addEventListener('input', () => {
            if (buyerIdInput && searchInput.value !== (searchInput.dataset.selectedLabel || '')) {
                buyerIdInput.value = '';
                searchInput.dataset.selectedLabel = '';
            }

            renderResults();
        });
        searchInput?.addEventListener('focus', renderResults);

        document.addEventListener('click', (event) => {
            if (!picker.contains(event.target)) {
                hideResults();
            }
        });

        picker.dataset.regularBuyerBound = 'true';
    });

    root.querySelectorAll('.sales-detail-row').forEach((row) => {
        const fishTypeSelect = row.querySelector('.fish-type-select');
        row.dataset.activeFishTypeId = fishTypeSelect?.value || '';
    });

    const hydrateSuggestedPriceOptions = (scope = container) => {
        scope.querySelectorAll('.fish-type-select option[value]').forEach((option) => {
            const fishTypeId = option.value ? String(option.value) : '';

            if (!fishTypeId) {
                return;
            }

            const suggestedPrice = SALES_CONFIG.fishPrices?.[fishTypeId];

            if (suggestedPrice !== undefined && suggestedPrice !== null && suggestedPrice !== '') {
                option.dataset.suggestedPrice = String(suggestedPrice);
            }
        });
    };

    // Get selected fish boxes (excluding a specific row)
    const getSelectedFishBoxes = (excludeRowIndex = null) => {
        return Array.from(root.querySelectorAll('.sales-detail-row'))
            .filter(row => row.dataset.index !== excludeRowIndex)
            .flatMap(row => Array.from(row.querySelectorAll('.fish-box-hidden-input'))
                .map(input => input.value)
                .filter(Boolean));
    };

        // Get available fish boxes for a fish name
    const getAvailableFishBoxesForType = (fishTypeId, excludeRowIndex = null) => {
        const selectedBoxes = getSelectedFishBoxes(excludeRowIndex);
        return SALES_CONFIG.fishBoxes.filter(box => {
            const boxFishTypeId = box.fish_type_id || box.fish_type?.id;
            return boxFishTypeId == fishTypeId && !selectedBoxes.includes(box.id.toString());
        });
    };

    const getFishTypeName = (fishTypeId) => {
        const fishType = SALES_CONFIG.fishTypes.find(ft => String(ft.id) === String(fishTypeId));
        return fishType?.name || 'this fish';
    };

    const renderAutoAssignFishBox = (container, rowIndex, availableCount = null) => {
        const availableLabel = availableCount === null
            ? ''
            : `<div class="mt-1 text-xs text-slate-500">Available: ${availableCount} ${availableCount === 1 ? 'box' : 'boxes'}</div>`;

        container.innerHTML = `
            <div class="fish-box-item">
                <select class="fish-box-select h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500" disabled>
                    <option value="">Auto-assign available box</option>
                </select>
                ${availableLabel}
                <input type="hidden" name="sales_details[${rowIndex}][box_id][]" class="fish-box-hidden-input">
            </div>
        `;
    };

    const getSuggestedPrice = (row, fishTypeId) => {
        if (!fishTypeId) {
            return null;
        }

        const normalizedFishTypeId = String(fishTypeId);
        const suggestedPrice = SALES_CONFIG.fishPrices?.[normalizedFishTypeId];

        if (suggestedPrice !== undefined && suggestedPrice !== null && suggestedPrice !== '') {
            const parsedPrice = Number(suggestedPrice);
            if (Number.isFinite(parsedPrice)) {
                return parsedPrice;
            }
        }

        const selectedOption = row
            ?.querySelector('.fish-type-select')
            ?.selectedOptions?.[0];
        const optionSuggestedPrice = selectedOption?.dataset?.suggestedPrice;

        if (optionSuggestedPrice === undefined || optionSuggestedPrice === null || optionSuggestedPrice === '') {
            return null;
        }

        const parsedOptionSuggestedPrice = Number(optionSuggestedPrice);

        return Number.isFinite(parsedOptionSuggestedPrice) ? parsedOptionSuggestedPrice : null;
    };

    const bindRowEvents = (row) => {
        if (!row || row.dataset.salesRowBound === 'true') {
            return;
        }

        row.dataset.salesRowBound = 'true';

        const fishTypeSelect = row.querySelector('.fish-type-select');
        const unitPriceInput = row.querySelector('.unit-price-input');
        const discountModeSelect = row.querySelector('.discount-mode-select');
        const discountValueInput = row.querySelector('.discount-value-input');
        const quantityInput = row.querySelector('.quantity-input');

        if (fishTypeSelect) {
            fishTypeSelect.addEventListener('change', () => {
                handleFishTypeChange(fishTypeSelect);
                applySuggestedPriceToRow(row, {
                    force: true,
                    overwriteZero: true,
                    clearOnMissing: true,
                    showMissingPriceWarning: true,
                });
            });
        }

        if (unitPriceInput) {
            const onUnitPriceChange = () => {
                calculateSubTotal(unitPriceInput);
                updateTotalAmount();
            };

            unitPriceInput.addEventListener('input', onUnitPriceChange);
            unitPriceInput.addEventListener('change', () => {
                formatMoneyInput(unitPriceInput);
                onUnitPriceChange();
            });
            unitPriceInput.addEventListener('blur', () => {
                formatMoneyInput(unitPriceInput);
            });
        }

        if (discountModeSelect) {
            discountModeSelect.addEventListener('change', () => {
                const previousMode = row.dataset.discountMode || 'percent';
                const nextMode = discountModeSelect.value || 'percent';

                if (previousMode !== nextMode) {
                    discountModeSelect.value = previousMode;
                    calculateSubTotal(discountModeSelect);
                    discountModeSelect.value = nextMode;
                    syncDiscountValueInput(row);
                }

                updateDiscountInputState(row);
                calculateSubTotal(discountModeSelect);
                updateTotalAmount();
            });
        }

        if (discountValueInput) {
            const onDiscountValueChange = () => {
                calculateSubTotal(discountValueInput);
                updateTotalAmount();
            };

            discountValueInput.addEventListener('input', onDiscountValueChange);
            discountValueInput.addEventListener('change', () => {
                const mode = row.querySelector('.discount-mode-select')?.value || 'percent';

                if (mode === 'percent' && discountValueInput.value !== '') {
                    discountValueInput.value = formatPercent(discountValueInput.value);
                } else {
                    formatMoneyInput(discountValueInput);
                }
                onDiscountValueChange();
            });
            discountValueInput.addEventListener('blur', () => {
                const mode = row.querySelector('.discount-mode-select')?.value || 'percent';

                if (mode === 'percent' && discountValueInput.value !== '') {
                    discountValueInput.value = formatPercent(discountValueInput.value);
                } else {
                    formatMoneyInput(discountValueInput);
                }
            });
        }

        if (quantityInput) {
            quantityInput.addEventListener('input', () => {
                handleQuantityChange(quantityInput);
            });

            quantityInput.addEventListener('change', () => {
                calculateSubTotal(quantityInput);
                updateTotalAmount();
            });
        }
    };

    const protectAmountInput = (input) => {
        if (!input || input.dataset.amountInputProtected === 'true') {
            return;
        }

        input.dataset.amountInputProtected = 'true';

        // Prevent accidental 0.01 step changes while scrolling the modal.
        input.addEventListener('wheel', (event) => {
            if (document.activeElement !== input) {
                return;
            }

            event.preventDefault();
            input.blur();
        }, { passive: false });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                event.preventDefault();
            }
        });
    };

    const updateDiscountInputState = (row) => {
        const mode = row.querySelector('.discount-mode-select')?.value || 'percent';
        const discountValueInput = row.querySelector('.discount-value-input');
        const discountValueLabel = row.querySelector('.discount-value-label');

        if (discountValueLabel) {
            discountValueLabel.textContent = mode === 'amount' ? 'Discount Amount' : 'Discount %';
        }

        if (discountValueInput) {
            discountValueInput.placeholder = mode === 'amount' ? '0.00' : '0%';
        }

        row.dataset.discountMode = mode;
    };

    const syncDiscountValueInput = (row) => {
        const mode = row.querySelector('.discount-mode-select')?.value || 'percent';
        const discountValueInput = row.querySelector('.discount-value-input');

        if (!discountValueInput) {
            return;
        }

        if (mode === 'amount') {
            const discountAmount = parseMoney(row.querySelector('.discount-input')?.value || 0);
            discountValueInput.value = discountAmount > 0 ? formatMoney(discountAmount) : '';
            return;
        }

        const discountPercent = parseMoney(row.querySelector('.discount-percent-input')?.value || 0);
        discountValueInput.value = discountPercent > 0 ? formatPercent(discountPercent) : '';
    };

    const applySuggestedPriceToRow = (row, options = {}) => {
        const {
            force = false,
            overwriteZero = false,
            clearOnMissing = false,
            showMissingPriceWarning = false,
        } = options;

        const fishTypeSelect = row.querySelector('.fish-type-select');
        const unitPriceInput = row.querySelector('.unit-price-input');

        if (!fishTypeSelect || !unitPriceInput) {
            return;
        }

        const fishTypeId = fishTypeSelect.value;

        if (!fishTypeId) {
            if (clearOnMissing) {
                unitPriceInput.value = '';
                calculateSubTotal(unitPriceInput);
                updateTotalAmount();
            }

            row.dataset.missingPriceWarningShownFor = '';
            return;
        }

        const suggestedPrice = getSuggestedPrice(row, fishTypeId);
        const currentUnitPrice = parseMoney(unitPriceInput.value);
        const hasCurrentUnitPrice = unitPriceInput.value !== '';
        const shouldPopulate = force || !hasCurrentUnitPrice || (overwriteZero && currentUnitPrice === 0);

        if (suggestedPrice !== null) {
            if (shouldPopulate) {
                unitPriceInput.value = formatMoney(suggestedPrice);
            }

            calculateSubTotal(unitPriceInput);
            updateTotalAmount();
            row.dataset.missingPriceWarningShownFor = '';
            return;
        }

        if (clearOnMissing && shouldPopulate) {
            unitPriceInput.value = '';
            calculateSubTotal(unitPriceInput);
            updateTotalAmount();
        }

        if (showMissingPriceWarning && row.dataset.missingPriceWarningShownFor !== String(fishTypeId)) {
            row.dataset.missingPriceWarningShownFor = String(fishTypeId);

            if (window.toastr) {
                toastr.info(
                    `No automatic selling price is set for ${getFishTypeName(fishTypeId)} yet. You can encode it in Inventory > Fish Prices or type a manual amount here.`
                );
            }
        }
    };

    // Update all rows fish box availability
    const updateAllRowsFishBoxAvailability = () => {
        root.querySelectorAll('.sales-detail-row').forEach(row => {
            if (row.dataset.scanned === 'true') {
                return;
            }

            const fishTypeSelect = row.querySelector('.fish-type-select');
            if (fishTypeSelect && fishTypeSelect.value) handleFishTypeChange(fishTypeSelect, true);
        });
    };

    const createSalesDetailRow = () => {
        const template = document.getElementById('sales-detail-row-template');
        if (!template) return null;

        const newRow = template.content.cloneNode(true).querySelector('.sales-detail-row');
        newRow.dataset.index = SALES_CONFIG.detailIndex;
        newRow.innerHTML = newRow.innerHTML.replaceAll('INDEX', SALES_CONFIG.detailIndex);
        container.appendChild(newRow);
        hydrateSuggestedPriceOptions(newRow);
        bindRowEvents(newRow);
        updateDiscountInputState(newRow);
        SALES_CONFIG.detailIndex++;
        updateTotalAmount();
        return newRow;
    };

    const clearSalesDetailRow = (row) => {
        if (!row) {
            return;
        }

        row.dataset.scanned = 'false';
        row.dataset.activeFishTypeId = '';
        row.dataset.discountMode = 'percent';
        delete row.dataset.missingPriceWarningShownFor;

        row.querySelectorAll('.fish-type-hidden-input, .quantity-hidden-input').forEach((input) => input.remove());

        const fishTypeSelect = row.querySelector('.fish-type-select');
        const quantityInput = row.querySelector('.quantity-input');
        const unitPriceInput = row.querySelector('.unit-price-input');
        const discountModeSelect = row.querySelector('.discount-mode-select');
        const discountValueInput = row.querySelector('.discount-value-input');
        const discountPercentInput = row.querySelector('.discount-percent-input');
        const discountInput = row.querySelector('.discount-input');
        const subTotalInput = row.querySelector('.sub-total-input');
        const itemInput = row.querySelector('.item-input');
        const itemDescriptionInput = row.querySelector('.item-description-input');
        const fishBoxesContainer = row.querySelector('.fish-boxes-container');

        if (fishTypeSelect) {
            fishTypeSelect.disabled = false;
            fishTypeSelect.value = '';
            fishTypeSelect.classList.remove('bg-gray-100', 'cursor-not-allowed');
        }

        if (quantityInput) {
            quantityInput.disabled = false;
            quantityInput.value = '1';
            quantityInput.removeAttribute('max');
            quantityInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        }

        if (unitPriceInput) {
            unitPriceInput.value = '';
        }

        if (discountModeSelect) {
            discountModeSelect.value = 'percent';
        }

        if (discountValueInput) {
            discountValueInput.value = '';
        }

        if (discountPercentInput) {
            discountPercentInput.value = '';
        }

        if (discountInput) {
            discountInput.value = '';
        }

        if (subTotalInput) {
            subTotalInput.value = '';
        }

        if (itemInput) {
            itemInput.value = '';
        }

        if (itemDescriptionInput) {
            itemDescriptionInput.value = '';
        }

        if (fishBoxesContainer) {
            renderAutoAssignFishBox(fishBoxesContainer, row.dataset.index);
        }

        updateDiscountInputState(row);
        updateTotalAmount();
        updateAllRowsFishBoxAvailability();
    };

    // Add new sales detail
    addBtn.addEventListener('click', () => {
        createSalesDetailRow();
    });

    // Remove sales detail
    container.addEventListener('click', (e) => {
        if (!e.target.closest('.remove-detail-btn')) {
            return;
        }

        const row = e.target.closest('.sales-detail-row');

        if (container.children.length > 1) {
            row?.remove();
            updateTotalAmount();
            updateAllRowsFishBoxAvailability();
            return;
        }

        clearSalesDetailRow(row);
    });

    if (initialPaidAmountInput) {
        protectAmountInput(initialPaidAmountInput);

        initialPaidAmountInput.addEventListener('input', () => {
            validateInitialPayment();
        });
        initialPaidAmountInput.addEventListener('change', () => {
            formatMoneyInput(initialPaidAmountInput);
            validateInitialPayment();
        });
        initialPaidAmountInput.addEventListener('blur', () => {
            formatMoneyInput(initialPaidAmountInput);
        });
    }

    function handleQuantityChange(quantityInput) {
        const row = quantityInput.closest('.sales-detail-row');
        if (row.dataset.scanned === 'true') {
            calculateSubTotal(quantityInput);
            updateTotalAmount();
            return;
        }

        let quantity = parseInt(quantityInput.value) || 1;
        const fishBoxesContainer = row.querySelector('.fish-boxes-container');
        const fishTypeSelect = row.querySelector('.fish-type-select');

        if (fishTypeSelect.value) {
            const availableBoxes = getAvailableFishBoxesForType(fishTypeSelect.value, row.dataset.index);
            if (quantity > availableBoxes.length) {
                quantityInput.value = availableBoxes.length;
                quantity = availableBoxes.length; // Update the quantity variable
                toastr.warning(`Maximum quantity for this fish is ${availableBoxes.length} (available fish boxes)`);
            }
        }

        const availableBoxes = fishTypeSelect.value
            ? getAvailableFishBoxesForType(fishTypeSelect.value, row.dataset.index)
            : null;
        renderAutoAssignFishBox(fishBoxesContainer, row.dataset.index, availableBoxes?.length ?? null);

        if (fishTypeSelect.value) handleFishTypeChange(fishTypeSelect);
        updateAllRowsFishBoxAvailability();

        // Calculate subtotal and update total
        calculateSubTotal(quantityInput);
        updateTotalAmount();
    }

    function handleFishTypeChange(fishTypeSelect, skipUpdate = false) {
        const row = fishTypeSelect.closest('.sales-detail-row');
        if (row.dataset.scanned === 'true' && row.querySelector('.fish-box-hidden-input')?.value) {
            applySuggestedPriceToRow(row, {
                overwriteZero: true,
                showMissingPriceWarning: true,
            });
            return;
        }

        const fishTypeId = fishTypeSelect.value;
        const fishBoxesContainer = row.querySelector('.fish-boxes-container');
        const itemInput = row.querySelector('.item-input');
        const previousFishTypeId = row.dataset.activeFishTypeId || '';
        const fishTypeChanged = previousFishTypeId !== String(fishTypeId);

        hydrateSuggestedPriceOptions(row);

        if (fishTypeId) {
            const availableBoxes = getAvailableFishBoxesForType(fishTypeId, row.dataset.index);

            if (availableBoxes.length > 0) {
                const quantityInput = row.querySelector('.quantity-input');
                if (quantityInput) quantityInput.setAttribute('max', availableBoxes.length);

                renderAutoAssignFishBox(fishBoxesContainer, row.dataset.index, availableBoxes.length);

                const fishType = SALES_CONFIG.fishTypes.find(ft => ft.id == fishTypeId);
                if (fishType && itemInput) itemInput.value = fishType.name;
                applySuggestedPriceToRow(row, {
                    force: fishTypeChanged,
                    overwriteZero: true,
                    clearOnMissing: fishTypeChanged,
                    showMissingPriceWarning: true,
                });

                row.dataset.activeFishTypeId = String(fishTypeId);

                // Only update other rows if not called from updateAllRowsFishBoxAvailability
                if (!skipUpdate) {
                    updateAllRowsFishBoxAvailability();
                }
            } else {
                fishBoxesContainer.querySelectorAll('.fish-box-select').forEach(select => {
                    select.innerHTML = '<option value="">No boxes available</option>';
                });
                toastr.error('No fish boxes available for the selected fish.');
                fishTypeSelect.value = '';
                if (itemInput) itemInput.value = '';
                applySuggestedPriceToRow(row, {
                    force: true,
                    clearOnMissing: true,
                });
                row.dataset.activeFishTypeId = '';
            }
        } else {
            renderAutoAssignFishBox(fishBoxesContainer, row.dataset.index);
            applySuggestedPriceToRow(row, {
                clearOnMissing: true,
            });
            row.dataset.activeFishTypeId = '';
        }
    }

    function calculateSubTotal(input) {
        const row = input.closest('.sales-detail-row');
        const unitPriceInput = row.querySelector('.unit-price-input');
        const discountModeSelect = row.querySelector('.discount-mode-select');
        const discountValueInput = row.querySelector('.discount-value-input');
        const discountPercentInput = row.querySelector('.discount-percent-input');
        const discountInput = row.querySelector('.discount-input');
        const quantityInput = row.querySelector('.quantity-input');
        const subTotalInput = row.querySelector('.sub-total-input');

        if (!unitPriceInput || !quantityInput || !subTotalInput) return;

        const unitPrice = parseMoney(unitPriceInput.value);
        const discountMode = discountModeSelect?.value || 'percent';
        const rawDiscountValue = discountValueInput ? parseMoney(discountValueInput.value) : 0;
        let discountPercent = discountMode === 'percent' ? clampDiscountPercent(rawDiscountValue) : 0;
        let discount = 0;
        const quantity = parseInt(quantityInput.value) || 0;

        if (discountMode === 'amount') {
            discount = rawDiscountValue;

            if (discount > unitPrice) {
                discount = unitPrice;
                if (discountValueInput) {
                    discountValueInput.value = formatMoney(discount);
                }
            }

            discountPercent = unitPrice > 0 ? roundMoney((discount / unitPrice) * 100) : 0;
        } else {
            discount = roundMoney(unitPrice * (discountPercent / 100));
        }

        if (discount > unitPrice) {
            discount = unitPrice;
        }

        if (discountInput) {
            discountInput.value = discount.toFixed(2);
        }

        if (discountPercentInput) {
            discountPercentInput.value = discountPercent > 0 ? discountPercent.toFixed(2) : '';
        }

        subTotalInput.value = formatMoney(Math.max(0, unitPrice - discount) * quantity);
    }

    function updateTotalAmount() {
        const total = Array.from(root.querySelectorAll('.sub-total-input'))
            .reduce((sum, input) => sum + parseMoney(input.value), 0);

        totalAmountDisplay.textContent = `₱${formatMoney(total)}`;
        if (totalAmountInput) totalAmountInput.value = total.toFixed(2);
        validateInitialPayment();
    }

    window.refreshSalesTotals = () => {
        root.querySelectorAll('.sales-detail-row').forEach((row) => {
            const unitPriceInput = row.querySelector('.unit-price-input');
            if (unitPriceInput) {
                calculateSubTotal(unitPriceInput);
            }
        });

        updateTotalAmount();
    };

    function setInitialPaymentError(message = '') {
        if (!initialPaymentError || !initialPaidAmountInput) return;

        initialPaymentError.textContent = message;
        initialPaymentError.classList.toggle('hidden', !message);
        initialPaidAmountInput.setCustomValidity(message);
    }

    function validateInitialPayment() {
        if (!initialPaidAmountInput) {
            return;
        }

        const maxPaymentAmount = parseMoney(totalAmountInput?.value || 0);
        const currentAmount = parseMoney(initialPaidAmountInput.value);
        const hasCurrentAmount = initialPaidAmountInput.value !== '';

        initialPaidAmountInput.max = maxPaymentAmount.toFixed(2);
        if (initialPaymentMethodSelect) {
            initialPaymentMethodSelect.required = hasCurrentAmount;
            initialPaymentMethodSelect.setCustomValidity(
                hasCurrentAmount && !initialPaymentMethodSelect.value
                    ? 'Please select the payment method.'
                    : ''
            );
        }

        if (initialPaymentMaxAmount) {
            initialPaymentMaxAmount.textContent = formatMoney(maxPaymentAmount);
        }

        if (!hasCurrentAmount) {
            setInitialPaymentError('');
            return;
        }

        if (currentAmount > maxPaymentAmount) {
            setInitialPaymentError(`Payment amount cannot exceed the remaining balance of ₱${formatMoney(maxPaymentAmount)}`);
            return;
        }

        if (currentAmount <= 0) {
            setInitialPaymentError('Payment amount must be greater than 0');
            return;
        }

        setInitialPaymentError('');
    }

    initialPaymentMethodSelect?.addEventListener('change', validateInitialPayment);

    // Initialize total amount
    root.querySelectorAll('.sales-detail-row').forEach((row) => {
        hydrateSuggestedPriceOptions(row);
        bindRowEvents(row);
        updateDiscountInputState(row);
        applySuggestedPriceToRow(row, {
            overwriteZero: salesFormMode === 'create',
        });
    });

    window.refreshSalesSuggestedPriceForRow = (row, options = {}) => {
        if (!row) {
            return;
        }

        hydrateSuggestedPriceOptions(row);
        applySuggestedPriceToRow(row, options);
    };

    window.bindSalesDetailRow = (row) => {
        if (!row) {
            return;
        }

        hydrateSuggestedPriceOptions(row);
        bindRowEvents(row);
        updateDiscountInputState(row);
        updateTotalAmount();
    };

    const selectFishTypeForRow = (row, fishTypeId) => {
        if (!row || !fishTypeId) {
            return;
        }

        const fishTypeSelect = row.querySelector('.fish-type-select');
        const quantityInput = row.querySelector('.quantity-input');

        if (!fishTypeSelect) {
            return;
        }

        fishTypeSelect.value = String(fishTypeId);

        if (quantityInput && !quantityInput.value) {
            quantityInput.value = 1;
        }

        fishTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const addFishTypeToSales = (fishTypeId) => {
        let targetRow = Array.from(container.querySelectorAll('.sales-detail-row'))
            .find((row) => {
                const fishTypeSelect = row.querySelector('.fish-type-select');
                const boxInput = row.querySelector('.fish-box-hidden-input');
                return fishTypeSelect && !fishTypeSelect.value && !boxInput?.value;
            });

        if (!targetRow) {
            targetRow = createSalesDetailRow();
        }

        selectFishTypeForRow(targetRow, fishTypeId);
    };

    const initializeFishQuickAdd = () => {
        const quickAdd = root.querySelector('[data-fish-quick-add]');
        const input = quickAdd?.querySelector('[data-fish-quick-add-input]');
        const results = quickAdd?.querySelector('[data-fish-quick-add-results]');
        const clearButton = quickAdd?.querySelector('[data-fish-quick-add-clear]');

        if (!quickAdd || !input || !results || input.dataset.fishQuickAddBound === 'true') {
            return;
        }

        input.dataset.fishQuickAddBound = 'true';

        const hideResults = () => {
            results.classList.add('hidden');
            results.innerHTML = '';
        };

        const renderResults = () => {
            const query = input.value.trim().toLowerCase();

            if (query.length < 1) {
                hideResults();
                return;
            }

            const matches = (SALES_CONFIG.fishTypes || [])
                .filter((fishType) => String(fishType.name || '').toLowerCase().includes(query))
                .slice(0, 8);

            if (matches.length === 0) {
                results.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No fish found.</div>';
                results.classList.remove('hidden');
                return;
            }

            results.innerHTML = matches.map((fishType) => {
                const price = SALES_CONFIG.fishPrices?.[String(fishType.id)];
                const priceLabel = price ? `₱${formatMoney(price)}` : 'No price set';

                return `
                    <button type="button"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm transition hover:bg-blue-50"
                            data-fish-quick-add-option
                            data-fish-type-id="${fishType.id}">
                        <span class="font-semibold text-slate-900">${escapeHtml(fishType.name || 'Unnamed fish')}</span>
                        <span class="text-xs font-semibold text-slate-500">${escapeHtml(priceLabel)}</span>
                    </button>
                `;
            }).join('');
            results.classList.remove('hidden');
        };

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        input.addEventListener('input', renderResults);
        input.addEventListener('focus', renderResults);
        results.addEventListener('click', (event) => {
            const option = event.target.closest('[data-fish-quick-add-option]');
            if (!option) {
                return;
            }

            addFishTypeToSales(option.dataset.fishTypeId);
            input.value = '';
            hideResults();
            input.focus();
        });
        clearButton?.addEventListener('click', () => {
            input.value = '';
            hideResults();
            input.focus();
        });
        document.addEventListener('click', (event) => {
            if (!quickAdd.contains(event.target)) {
                hideResults();
            }
        });
    };

    window.protectSalesAmountInput = protectAmountInput;

    initializeFishQuickAdd();
    updateTotalAmount();
}

// Make function available globally for browser use
window.initializeSalesForm = initializeSalesForm;

