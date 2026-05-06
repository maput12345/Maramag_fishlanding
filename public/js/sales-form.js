/******/ (() => { // webpackBootstrap
/*!************************************!*\
  !*** ./resources/js/sales-form.js ***!
  \************************************/
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
/**
 * Sales Form Functionality
 * Handles dynamic sales detail rows, fish box selection, and calculations
 */

// Sales form configuration (initialized from server)
var SALES_CONFIG = {};

/**
 * Initialize sales form
 * @param {Object} config - Configuration object with fishBoxes, fishTypes, fishPrices, and detailIndex
 */
function initializeSalesForm(config) {
  var scope = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : document;
  var normalizeFishPrices = function normalizeFishPrices(fishPrices) {
    if (!fishPrices || _typeof(fishPrices) !== 'object') {
      return {};
    }
    return Object.entries(fishPrices).reduce(function (carry, _ref) {
      var _ref2 = _slicedToArray(_ref, 2),
        key = _ref2[0],
        value = _ref2[1];
      carry[String(key)] = value;
      return carry;
    }, {});
  };
  SALES_CONFIG = _objectSpread(_objectSpread({}, config), {}, {
    fishPrices: normalizeFishPrices(config.fishPrices || {})
  });
  var salesFormMode = SALES_CONFIG.mode || 'create';
  var root = scope && typeof scope.querySelector === 'function' ? scope : document;
  var container = root.querySelector('#sales-details-container');
  var addBtn = root.querySelector('#add-sales-detail-btn');
  var totalAmountDisplay = root.querySelector('#total-amount-display');
  var totalAmountInput = root.querySelector('#total_amount');
  var initialPaidAmountInput = root.querySelector('#initial_paid_amount');
  var initialPaymentMaxAmount = root.querySelector('#initial-payment-max-amount');
  var initialPaymentError = root.querySelector('#initial-payment-error');
  if (!container || !addBtn || !totalAmountDisplay) return;
  if (container.dataset.salesFormInitialized === 'true') return;
  container.dataset.salesFormInitialized = 'true';
  root.querySelectorAll('.sales-detail-row').forEach(function (row) {
    var fishTypeSelect = row.querySelector('.fish-type-select');
    row.dataset.activeFishTypeId = (fishTypeSelect === null || fishTypeSelect === void 0 ? void 0 : fishTypeSelect.value) || '';
  });
  var hydrateSuggestedPriceOptions = function hydrateSuggestedPriceOptions() {
    var scope = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : container;
    scope.querySelectorAll('.fish-type-select option[value]').forEach(function (option) {
      var _SALES_CONFIG$fishPri;
      var fishTypeId = option.value ? String(option.value) : '';
      if (!fishTypeId) {
        return;
      }
      var suggestedPrice = (_SALES_CONFIG$fishPri = SALES_CONFIG.fishPrices) === null || _SALES_CONFIG$fishPri === void 0 ? void 0 : _SALES_CONFIG$fishPri[fishTypeId];
      if (suggestedPrice !== undefined && suggestedPrice !== null && suggestedPrice !== '') {
        option.dataset.suggestedPrice = String(suggestedPrice);
      }
    });
  };

  // Get selected fish boxes (excluding a specific row)
  var getSelectedFishBoxes = function getSelectedFishBoxes() {
    var excludeRowIndex = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
    return Array.from(root.querySelectorAll('.sales-detail-row')).filter(function (row) {
      return row.dataset.index !== excludeRowIndex;
    }).flatMap(function (row) {
      return Array.from(row.querySelectorAll('.fish-box-hidden-input')).map(function (input) {
        return input.value;
      }).filter(Boolean);
    });
  };

  // Get available fish boxes for a fish name
  var getAvailableFishBoxesForType = function getAvailableFishBoxesForType(fishTypeId) {
    var excludeRowIndex = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
    var selectedBoxes = getSelectedFishBoxes(excludeRowIndex);
    return SALES_CONFIG.fishBoxes.filter(function (box) {
      var _box$fish_type;
      var boxFishTypeId = box.fish_type_id || ((_box$fish_type = box.fish_type) === null || _box$fish_type === void 0 ? void 0 : _box$fish_type.id);
      return boxFishTypeId == fishTypeId && !selectedBoxes.includes(box.id.toString());
    });
  };
  var getFishTypeName = function getFishTypeName(fishTypeId) {
    var fishType = SALES_CONFIG.fishTypes.find(function (ft) {
      return String(ft.id) === String(fishTypeId);
    });
    return (fishType === null || fishType === void 0 ? void 0 : fishType.name) || 'this fish';
  };
  var renderAutoAssignFishBox = function renderAutoAssignFishBox(container, rowIndex) {
    var availableCount = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
    var availableLabel = availableCount === null ? '' : "<div class=\"mt-1 text-xs text-slate-500\">Available: ".concat(availableCount, " ").concat(availableCount === 1 ? 'box' : 'boxes', "</div>");
    container.innerHTML = "\n            <div class=\"fish-box-item\">\n                <select class=\"fish-box-select h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500\" disabled>\n                    <option value=\"\">Auto-assign available box</option>\n                </select>\n                ".concat(availableLabel, "\n                <input type=\"hidden\" name=\"sales_details[").concat(rowIndex, "][box_id][]\" class=\"fish-box-hidden-input\">\n            </div>\n        ");
  };
  var getSuggestedPrice = function getSuggestedPrice(row, fishTypeId) {
    var _SALES_CONFIG$fishPri2, _row$querySelector, _selectedOption$datas;
    if (!fishTypeId) {
      return null;
    }
    var normalizedFishTypeId = String(fishTypeId);
    var suggestedPrice = (_SALES_CONFIG$fishPri2 = SALES_CONFIG.fishPrices) === null || _SALES_CONFIG$fishPri2 === void 0 ? void 0 : _SALES_CONFIG$fishPri2[normalizedFishTypeId];
    if (suggestedPrice !== undefined && suggestedPrice !== null && suggestedPrice !== '') {
      var parsedPrice = Number(suggestedPrice);
      if (Number.isFinite(parsedPrice)) {
        return parsedPrice;
      }
    }
    var selectedOption = row === null || row === void 0 || (_row$querySelector = row.querySelector('.fish-type-select')) === null || _row$querySelector === void 0 || (_row$querySelector = _row$querySelector.selectedOptions) === null || _row$querySelector === void 0 ? void 0 : _row$querySelector[0];
    var optionSuggestedPrice = selectedOption === null || selectedOption === void 0 || (_selectedOption$datas = selectedOption.dataset) === null || _selectedOption$datas === void 0 ? void 0 : _selectedOption$datas.suggestedPrice;
    if (optionSuggestedPrice === undefined || optionSuggestedPrice === null || optionSuggestedPrice === '') {
      return null;
    }
    var parsedOptionSuggestedPrice = Number(optionSuggestedPrice);
    return Number.isFinite(parsedOptionSuggestedPrice) ? parsedOptionSuggestedPrice : null;
  };
  var bindRowEvents = function bindRowEvents(row) {
    if (!row || row.dataset.salesRowBound === 'true') {
      return;
    }
    row.dataset.salesRowBound = 'true';
    var fishTypeSelect = row.querySelector('.fish-type-select');
    var unitPriceInput = row.querySelector('.unit-price-input');
    var quantityInput = row.querySelector('.quantity-input');
    if (fishTypeSelect) {
      fishTypeSelect.addEventListener('change', function () {
        handleFishTypeChange(fishTypeSelect);
        applySuggestedPriceToRow(row, {
          force: true,
          overwriteZero: true,
          clearOnMissing: true,
          showMissingPriceWarning: true
        });
      });
    }
    if (unitPriceInput) {
      var onUnitPriceChange = function onUnitPriceChange() {
        calculateSubTotal(unitPriceInput);
        updateTotalAmount();
      };
      unitPriceInput.addEventListener('input', onUnitPriceChange);
      unitPriceInput.addEventListener('change', onUnitPriceChange);
    }
    if (quantityInput) {
      quantityInput.addEventListener('input', function () {
        handleQuantityChange(quantityInput);
      });
      quantityInput.addEventListener('change', function () {
        calculateSubTotal(quantityInput);
        updateTotalAmount();
      });
    }
  };
  var protectAmountInput = function protectAmountInput(input) {
    if (!input || input.dataset.amountInputProtected === 'true') {
      return;
    }
    input.dataset.amountInputProtected = 'true';

    // Prevent accidental 0.01 step changes while scrolling the modal.
    input.addEventListener('wheel', function (event) {
      if (document.activeElement !== input) {
        return;
      }
      event.preventDefault();
      input.blur();
    }, {
      passive: false
    });
    input.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
        event.preventDefault();
      }
    });
  };
  var applySuggestedPriceToRow = function applySuggestedPriceToRow(row) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var _options$force = options.force,
      force = _options$force === void 0 ? false : _options$force,
      _options$overwriteZer = options.overwriteZero,
      overwriteZero = _options$overwriteZer === void 0 ? false : _options$overwriteZer,
      _options$clearOnMissi = options.clearOnMissing,
      clearOnMissing = _options$clearOnMissi === void 0 ? false : _options$clearOnMissi,
      _options$showMissingP = options.showMissingPriceWarning,
      showMissingPriceWarning = _options$showMissingP === void 0 ? false : _options$showMissingP;
    var fishTypeSelect = row.querySelector('.fish-type-select');
    var unitPriceInput = row.querySelector('.unit-price-input');
    if (!fishTypeSelect || !unitPriceInput) {
      return;
    }
    var fishTypeId = fishTypeSelect.value;
    if (!fishTypeId) {
      if (clearOnMissing) {
        unitPriceInput.value = '';
        calculateSubTotal(unitPriceInput);
        updateTotalAmount();
      }
      row.dataset.missingPriceWarningShownFor = '';
      return;
    }
    var suggestedPrice = getSuggestedPrice(row, fishTypeId);
    var currentUnitPrice = parseFloat(unitPriceInput.value);
    var hasCurrentUnitPrice = unitPriceInput.value !== '' && !Number.isNaN(currentUnitPrice);
    var shouldPopulate = force || !hasCurrentUnitPrice || overwriteZero && currentUnitPrice === 0;
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
        toastr.info("No automatic selling price is set for ".concat(getFishTypeName(fishTypeId), " yet. You can encode it in Inventory > Fish Prices or type a manual amount here."));
      }
    }
  };

  // Update all rows fish box availability
  var updateAllRowsFishBoxAvailability = function updateAllRowsFishBoxAvailability() {
    root.querySelectorAll('.sales-detail-row').forEach(function (row) {
      var fishTypeSelect = row.querySelector('.fish-type-select');
      if (fishTypeSelect && fishTypeSelect.value) handleFishTypeChange(fishTypeSelect, true);
    });
  };

  // Add new sales detail
  addBtn.addEventListener('click', function () {
    var template = document.getElementById('sales-detail-row-template');
    if (!template) return;
    var newRow = template.content.cloneNode(true).querySelector('.sales-detail-row');
    newRow.dataset.index = SALES_CONFIG.detailIndex;
    newRow.innerHTML = newRow.innerHTML.replaceAll('INDEX', SALES_CONFIG.detailIndex);
    container.appendChild(newRow);
    hydrateSuggestedPriceOptions(newRow);
    bindRowEvents(newRow);
    SALES_CONFIG.detailIndex++;
    updateTotalAmount();
  });

  // Remove sales detail
  container.addEventListener('click', function (e) {
    if (e.target.closest('.remove-detail-btn') && container.children.length > 1) {
      e.target.closest('.sales-detail-row').remove();
      updateTotalAmount();
      updateAllRowsFishBoxAvailability();
    }
  });
  if (initialPaidAmountInput) {
    protectAmountInput(initialPaidAmountInput);
    initialPaidAmountInput.addEventListener('input', function () {
      validateInitialPayment();
    });
  }
  function handleQuantityChange(quantityInput) {
    var _availableBoxes$lengt;
    var row = quantityInput.closest('.sales-detail-row');
    var quantity = parseInt(quantityInput.value) || 1;
    var fishBoxesContainer = row.querySelector('.fish-boxes-container');
    var fishTypeSelect = row.querySelector('.fish-type-select');
    if (fishTypeSelect.value) {
      var _availableBoxes = getAvailableFishBoxesForType(fishTypeSelect.value, row.dataset.index);
      if (quantity > _availableBoxes.length) {
        quantityInput.value = _availableBoxes.length;
        quantity = _availableBoxes.length; // Update the quantity variable
        toastr.warning("Maximum quantity for this fish is ".concat(_availableBoxes.length, " (available fish boxes)"));
      }
    }
    var availableBoxes = fishTypeSelect.value ? getAvailableFishBoxesForType(fishTypeSelect.value, row.dataset.index) : null;
    renderAutoAssignFishBox(fishBoxesContainer, row.dataset.index, (_availableBoxes$lengt = availableBoxes === null || availableBoxes === void 0 ? void 0 : availableBoxes.length) !== null && _availableBoxes$lengt !== void 0 ? _availableBoxes$lengt : null);
    if (fishTypeSelect.value) handleFishTypeChange(fishTypeSelect);
    updateAllRowsFishBoxAvailability();

    // Calculate subtotal and update total
    calculateSubTotal(quantityInput);
    updateTotalAmount();
  }
  function handleFishTypeChange(fishTypeSelect) {
    var skipUpdate = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    var row = fishTypeSelect.closest('.sales-detail-row');
    var fishTypeId = fishTypeSelect.value;
    var fishBoxesContainer = row.querySelector('.fish-boxes-container');
    var itemInput = row.querySelector('.item-input');
    var previousFishTypeId = row.dataset.activeFishTypeId || '';
    var fishTypeChanged = previousFishTypeId !== String(fishTypeId);
    hydrateSuggestedPriceOptions(row);
    if (fishTypeId) {
      var availableBoxes = getAvailableFishBoxesForType(fishTypeId, row.dataset.index);
      if (availableBoxes.length > 0) {
        var quantityInput = row.querySelector('.quantity-input');
        if (quantityInput) quantityInput.setAttribute('max', availableBoxes.length);
        renderAutoAssignFishBox(fishBoxesContainer, row.dataset.index, availableBoxes.length);
        var fishType = SALES_CONFIG.fishTypes.find(function (ft) {
          return ft.id == fishTypeId;
        });
        if (fishType && itemInput) itemInput.value = fishType.name;
        applySuggestedPriceToRow(row, {
          force: fishTypeChanged,
          overwriteZero: true,
          clearOnMissing: fishTypeChanged,
          showMissingPriceWarning: true
        });
        row.dataset.activeFishTypeId = String(fishTypeId);

        // Only update other rows if not called from updateAllRowsFishBoxAvailability
        if (!skipUpdate) {
          updateAllRowsFishBoxAvailability();
        }
      } else {
        fishBoxesContainer.querySelectorAll('.fish-box-select').forEach(function (select) {
          select.innerHTML = '<option value="">No boxes available</option>';
        });
        toastr.error('No fish boxes available for the selected fish.');
        fishTypeSelect.value = '';
        if (itemInput) itemInput.value = '';
        applySuggestedPriceToRow(row, {
          force: true,
          clearOnMissing: true
        });
        row.dataset.activeFishTypeId = '';
      }
    } else {
      renderAutoAssignFishBox(fishBoxesContainer, row.dataset.index);
      applySuggestedPriceToRow(row, {
        clearOnMissing: true
      });
      row.dataset.activeFishTypeId = '';
    }
  }
  function calculateSubTotal(input) {
    var row = input.closest('.sales-detail-row');
    var unitPriceInput = row.querySelector('.unit-price-input');
    var quantityInput = row.querySelector('.quantity-input');
    var subTotalInput = row.querySelector('.sub-total-input');
    if (!unitPriceInput || !quantityInput || !subTotalInput) return;
    var unitPrice = parseFloat(unitPriceInput.value) || 0;
    var quantity = parseInt(quantityInput.value) || 0;
    subTotalInput.value = (unitPrice * quantity).toFixed(2);
  }
  function updateTotalAmount() {
    var total = Array.from(root.querySelectorAll('.sub-total-input')).reduce(function (sum, input) {
      return sum + (parseFloat(input.value) || 0);
    }, 0);
    totalAmountDisplay.textContent = "PHP ".concat(total.toFixed(2));
    if (totalAmountInput) totalAmountInput.value = total.toFixed(2);
    validateInitialPayment();
  }
  function setInitialPaymentError() {
    var message = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    if (!initialPaymentError || !initialPaidAmountInput) return;
    initialPaymentError.textContent = message;
    initialPaymentError.classList.toggle('hidden', !message);
    initialPaidAmountInput.setCustomValidity(message);
  }
  function validateInitialPayment() {
    if (!initialPaidAmountInput) {
      return;
    }
    var maxPaymentAmount = parseFloat((totalAmountInput === null || totalAmountInput === void 0 ? void 0 : totalAmountInput.value) || 0) || 0;
    var currentAmount = parseFloat(initialPaidAmountInput.value);
    initialPaidAmountInput.max = maxPaymentAmount.toFixed(2);
    if (initialPaymentMaxAmount) {
      initialPaymentMaxAmount.textContent = maxPaymentAmount.toFixed(2);
    }
    if (Number.isNaN(currentAmount)) {
      setInitialPaymentError('');
      return;
    }
    if (currentAmount > maxPaymentAmount) {
      setInitialPaymentError("Payment amount cannot exceed the remaining balance of PHP ".concat(maxPaymentAmount.toFixed(2)));
      return;
    }
    if (currentAmount <= 0) {
      setInitialPaymentError('Payment amount must be greater than 0');
      return;
    }
    setInitialPaymentError('');
  }

  // Initialize total amount
  root.querySelectorAll('.sales-detail-row').forEach(function (row) {
    hydrateSuggestedPriceOptions(row);
    bindRowEvents(row);
    applySuggestedPriceToRow(row, {
      overwriteZero: salesFormMode === 'create'
    });
  });
  window.refreshSalesSuggestedPriceForRow = function (row) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
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
/******/ })()
;