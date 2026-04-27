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
    const initialPaymentMaxAmount = root.querySelector('#initial-payment-max-amount');
    const initialPaymentError = root.querySelector('#initial-payment-error');

    if (!container || !addBtn || !totalAmountDisplay) return;
    if (container.dataset.salesFormInitialized === 'true') return;

    container.dataset.salesFormInitialized = 'true';

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
        const quantityInput = row.querySelector('.quantity-input');

        if (fishTypeSelect) {
            fishTypeSelect.addEventListener('change', () => {
                handleFishTypeChange(fishTypeSelect);
            });
        }

        if (unitPriceInput) {
            const onUnitPriceChange = () => {
                calculateSubTotal(unitPriceInput);
                updateTotalAmount();
            };

            unitPriceInput.addEventListener('input', onUnitPriceChange);
            unitPriceInput.addEventListener('change', onUnitPriceChange);
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
        const currentUnitPrice = parseFloat(unitPriceInput.value);
        const hasCurrentUnitPrice = unitPriceInput.value !== '' && !Number.isNaN(currentUnitPrice);
        const shouldPopulate = force || !hasCurrentUnitPrice || (overwriteZero && currentUnitPrice === 0);

        if (suggestedPrice !== null) {
            if (shouldPopulate) {
                unitPriceInput.value = suggestedPrice.toFixed(2);
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
            const fishTypeSelect = row.querySelector('.fish-type-select');
            if (fishTypeSelect && fishTypeSelect.value) handleFishTypeChange(fishTypeSelect, true);
        });
    };

    // Add new sales detail
    addBtn.addEventListener('click', () => {
        const template = document.getElementById('sales-detail-row-template');
        if (!template) return;

        const newRow = template.content.cloneNode(true).querySelector('.sales-detail-row');
        newRow.dataset.index = SALES_CONFIG.detailIndex;
        newRow.innerHTML = newRow.innerHTML.replaceAll('INDEX', SALES_CONFIG.detailIndex);
        container.appendChild(newRow);
        hydrateSuggestedPriceOptions(newRow);
        bindRowEvents(newRow);
        SALES_CONFIG.detailIndex++;
        updateTotalAmount();
    });

    // Remove sales detail
    container.addEventListener('click', (e) => {
        if (e.target.closest('.remove-detail-btn') && container.children.length > 1) {
            e.target.closest('.sales-detail-row').remove();
            updateTotalAmount();
            updateAllRowsFishBoxAvailability();
        }
    });

    if (initialPaidAmountInput) {
        protectAmountInput(initialPaidAmountInput);

        initialPaidAmountInput.addEventListener('input', () => {
            validateInitialPayment();
        });
    }

    function handleQuantityChange(quantityInput) {
        const row = quantityInput.closest('.sales-detail-row');
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

        fishBoxesContainer.innerHTML = '';
        for (let i = 0; i < quantity; i++) {
            const fishBoxItem = document.createElement('div');
            fishBoxItem.className = 'fish-box-item';
            fishBoxItem.innerHTML = `
                <select class="fish-box-select h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500" disabled>
                    <option value="">Auto-select</option>
                </select>
                <input type="hidden" name="sales_details[${row.dataset.index}][box_id][]" class="fish-box-hidden-input">
            `;
            fishBoxesContainer.appendChild(fishBoxItem);
        }

        if (fishTypeSelect.value) handleFishTypeChange(fishTypeSelect);
        updateAllRowsFishBoxAvailability();

        // Calculate subtotal and update total
        calculateSubTotal(quantityInput);
        updateTotalAmount();
    }

    function handleFishTypeChange(fishTypeSelect, skipUpdate = false) {
        const row = fishTypeSelect.closest('.sales-detail-row');
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

                fishBoxesContainer.querySelectorAll('.fish-box-item').forEach((item, index) => {
                    const fishBoxSelect = item.querySelector('.fish-box-select');
                    const fishBoxHiddenInput = item.querySelector('.fish-box-hidden-input');

                    if (availableBoxes[index]) {
                        const selectedBox = availableBoxes[index];
                        fishBoxHiddenInput.value = selectedBox.id;
                        fishBoxSelect.innerHTML = `<option value="${selectedBox.id}" selected>${selectedBox.name}</option>`;
                    } else {
                        fishBoxSelect.innerHTML = '<option value="">No more boxes available</option>';
                    }
                });

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
                row.dataset.activeFishTypeId = '';
            }
        } else {
            fishBoxesContainer.querySelectorAll('.fish-box-item').forEach(item => {
                item.querySelector('.fish-box-select').innerHTML = '<option value="">Auto-select</option>';
                item.querySelector('.fish-box-hidden-input').value = '';
            });
            applySuggestedPriceToRow(row, {
                clearOnMissing: true,
            });
            row.dataset.activeFishTypeId = '';
        }
    }

    function calculateSubTotal(input) {
        const row = input.closest('.sales-detail-row');
        const unitPriceInput = row.querySelector('.unit-price-input');
        const quantityInput = row.querySelector('.quantity-input');
        const subTotalInput = row.querySelector('.sub-total-input');

        if (!unitPriceInput || !quantityInput || !subTotalInput) return;

        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        subTotalInput.value = (unitPrice * quantity).toFixed(2);
    }

    function updateTotalAmount() {
        const total = Array.from(root.querySelectorAll('.sub-total-input'))
            .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);

        totalAmountDisplay.textContent = `PHP ${total.toFixed(2)}`;
        if (totalAmountInput) totalAmountInput.value = total.toFixed(2);
        validateInitialPayment();
    }

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

        const maxPaymentAmount = parseFloat(totalAmountInput?.value || 0) || 0;
        const currentAmount = parseFloat(initialPaidAmountInput.value);

        initialPaidAmountInput.max = maxPaymentAmount.toFixed(2);

        if (initialPaymentMaxAmount) {
            initialPaymentMaxAmount.textContent = maxPaymentAmount.toFixed(2);
        }

        if (Number.isNaN(currentAmount)) {
            setInitialPaymentError('');
            return;
        }

        if (currentAmount > maxPaymentAmount) {
            setInitialPaymentError(`Payment amount cannot exceed the remaining balance of PHP ${maxPaymentAmount.toFixed(2)}`);
            return;
        }

        if (currentAmount <= 0) {
            setInitialPaymentError('Payment amount must be greater than 0');
            return;
        }

        setInitialPaymentError('');
    }

    // Initialize total amount
    root.querySelectorAll('.sales-detail-row').forEach((row) => {
        hydrateSuggestedPriceOptions(row);
        bindRowEvents(row);
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

    window.protectSalesAmountInput = protectAmountInput;

    updateTotalAmount();
}

// Make function available globally for browser use
window.initializeSalesForm = initializeSalesForm;

