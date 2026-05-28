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
  var initialPaymentMethodSelect = root.querySelector('#initial_payment_method');
  var initialReferenceNumberGroup = root.querySelector('[data-initial-reference-number-group]');
  var initialReferenceNumberInput = root.querySelector('[data-initial-reference-number]');
  var initialPaymentMaxAmount = root.querySelector('#initial-payment-max-amount');
  var initialPaymentError = root.querySelector('#initial-payment-error');
  var salesForm = root.querySelector('form[data-sales-async-form]');
  if (!container || !addBtn || !totalAmountDisplay) return;
  if (container.dataset.salesFormInitialized === 'true') return;
  container.dataset.salesFormInitialized = 'true';
  var parseMoney = function parseMoney(value) {
    var normalizedValue = String(value !== null && value !== void 0 ? value : '').replace(/[₱,\s]/g, '');
    var parsedValue = parseFloat(normalizedValue);
    return Number.isFinite(parsedValue) ? parsedValue : 0;
  };
  var formatMoney = function formatMoney(value) {
    var numericValue = Number(value) || 0;
    return numericValue.toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };
  var normalizeMoneyValue = function normalizeMoneyValue(value) {
    return parseMoney(value).toFixed(2);
  };
  var roundMoney = function roundMoney(value) {
    return Math.round((Number(value) || 0) * 100) / 100;
  };
  var formatPercent = function formatPercent(value) {
    var percent = clampDiscountPercent(value);
    return "".concat(Number.isInteger(percent) ? percent.toFixed(0) : percent.toFixed(2), "%");
  };
  var formatMoneyInput = function formatMoneyInput(input) {
    if (!input || input.value === '') {
      return;
    }
    input.value = formatMoney(parseMoney(input.value));
  };
  var clampDiscountPercent = function clampDiscountPercent(value) {
    return Math.min(100, Math.max(0, parseMoney(value)));
  };
  var normalizeSalesMoneyFields = function normalizeSalesMoneyFields() {
    root.querySelectorAll('.unit-price-input, .discount-input, .sub-total-input, #initial_paid_amount').forEach(function (input) {
      if (input && input.value !== '') {
        input.value = normalizeMoneyValue(input.value);
      }
    });
    root.querySelectorAll('.discount-value-input').forEach(function (input) {
      var _row$querySelector;
      if (!input || input.value === '') {
        return;
      }
      var row = input.closest('.sales-detail-row');
      var mode = (row === null || row === void 0 || (_row$querySelector = row.querySelector('.discount-mode-select')) === null || _row$querySelector === void 0 ? void 0 : _row$querySelector.value) || 'percent';
      input.value = mode === 'percent' ? clampDiscountPercent(input.value).toFixed(2) : normalizeMoneyValue(input.value);
    });
    root.querySelectorAll('.discount-percent-input').forEach(function (input) {
      if (input && input.value !== '') {
        input.value = clampDiscountPercent(input.value).toFixed(2);
      }
    });
  };
  if (salesForm && salesForm.dataset.salesMoneyNormalizerBound !== 'true') {
    salesForm.dataset.salesMoneyNormalizerBound = 'true';
    salesForm.addEventListener('submit', normalizeSalesMoneyFields, {
      capture: true
    });
  }
  var escapeHtml = function escapeHtml(value) {
    return String(value !== null && value !== void 0 ? value : '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  };
  root.querySelectorAll('[data-regular-buyer-picker]').forEach(function (picker) {
    if (!salesForm || picker.dataset.regularBuyerBound === 'true') {
      return;
    }
    var dataElement = picker.querySelector('[data-regular-buyers-json]');
    var buyerIdInput = picker.querySelector('[data-regular-buyer-id]');
    var searchInput = picker.querySelector('[data-regular-buyer-search]');
    var results = picker.querySelector('[data-regular-buyer-results]');
    var regularBuyers = [];
    try {
      regularBuyers = JSON.parse((dataElement === null || dataElement === void 0 ? void 0 : dataElement.textContent) || '[]');
    } catch (error) {
      regularBuyers = [];
    }
    regularBuyers = regularBuyers.map(function (buyer) {
      return Object.assign(Object.assign({}, buyer), {
        search: [buyer.name, buyer.first_name, buyer.middle_name, buyer.last_name, buyer.contact].filter(Boolean).join(' ').toLowerCase()
      });
    });
    var hideResults = function hideResults() {
      results === null || results === void 0 || results.classList.add('hidden');
    };
    var fillBuyerFields = function fillBuyerFields(buyer) {
      var fields = {
        buyer_first_name: buyer.first_name || '',
        buyer_middle_name: buyer.middle_name || '',
        buyer_last_name: buyer.last_name || '',
        buyer_contact: buyer.contact || ''
      };
      Object.entries(fields).forEach(function (_ref) {
        var id = _ref[0],
          value = _ref[1];
        var field = salesForm.querySelector("#".concat(id));
        if (field) {
          field.value = value;
        }
      });
      if (searchInput) {
        searchInput.value = "".concat(buyer.name || "").concat(buyer.contact ? " - ".concat(buyer.contact) : '').trim();
        searchInput.dataset.selectedLabel = searchInput.value;
      }
      if (buyerIdInput) {
        buyerIdInput.value = buyer.id || '';
      }
      hideResults();
    };
    var renderResults = function renderResults() {
      if (!searchInput || !results) {
        return;
      }
      var query = searchInput.value.trim().toLowerCase();
      if (query === '') {
        hideResults();
        return;
      }
      var matches = regularBuyers.filter(function (buyer) {
        return buyer.search.includes(query);
      }).slice(0, 10);
      if (matches.length === 0) {
        results.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No regular customer found. Continue typing below for a new or walk-in customer.</div>';
        results.classList.remove('hidden');
        return;
      }
      results.innerHTML = matches.map(function (buyer, index) {
        return "\n                <button type=\"button\"\n                        class=\"block w-full px-4 py-3 text-left text-sm transition-colors hover:bg-blue-50\"\n                        data-regular-buyer-option=\"".concat(index, "\">\n                    <span class=\"block font-semibold text-slate-900\">").concat(escapeHtml(buyer.name || 'Unnamed customer'), "</span>\n                    <span class=\"block text-xs text-slate-500\">").concat(escapeHtml(buyer.contact || 'No contact number'), "</span>\n                </button>\n            ");
      }).join('');
      results.classList.remove('hidden');
      results.querySelectorAll('[data-regular-buyer-option]').forEach(function (button) {
        button.addEventListener('click', function () {
          fillBuyerFields(matches[Number(button.dataset.regularBuyerOption)]);
        });
      });
    };
    searchInput === null || searchInput === void 0 || searchInput.addEventListener('input', function () {
      if (buyerIdInput && searchInput.value !== (searchInput.dataset.selectedLabel || '')) {
        buyerIdInput.value = '';
        searchInput.dataset.selectedLabel = '';
      }
      renderResults();
    });
    searchInput === null || searchInput === void 0 || searchInput.addEventListener('focus', renderResults);
    document.addEventListener('click', function (event) {
      if (!picker.contains(event.target)) {
        hideResults();
      }
    });
    picker.dataset.regularBuyerBound = 'true';
  });
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
    var _SALES_CONFIG$fishPri2, _row$querySelector2, _selectedOption$datas;
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
    var selectedOption = row === null || row === void 0 || (_row$querySelector2 = row.querySelector('.fish-type-select')) === null || _row$querySelector2 === void 0 || (_row$querySelector2 = _row$querySelector2.selectedOptions) === null || _row$querySelector2 === void 0 ? void 0 : _row$querySelector2[0];
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
    var discountModeSelect = row.querySelector('.discount-mode-select');
    var discountValueInput = row.querySelector('.discount-value-input');
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
      unitPriceInput.addEventListener('change', function () {
        formatMoneyInput(unitPriceInput);
        onUnitPriceChange();
      });
      unitPriceInput.addEventListener('blur', function () {
        formatMoneyInput(unitPriceInput);
      });
    }
    if (discountModeSelect) {
      discountModeSelect.addEventListener('change', function () {
        var previousMode = row.dataset.discountMode || 'percent';
        var nextMode = discountModeSelect.value || 'percent';
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
      var onDiscountValueChange = function onDiscountValueChange() {
        calculateSubTotal(discountValueInput);
        updateTotalAmount();
      };
      discountValueInput.addEventListener('input', onDiscountValueChange);
      discountValueInput.addEventListener('change', function () {
        var _row$querySelector3;
        var mode = ((_row$querySelector3 = row.querySelector('.discount-mode-select')) === null || _row$querySelector3 === void 0 ? void 0 : _row$querySelector3.value) || 'percent';
        if (mode === 'percent' && discountValueInput.value !== '') {
          discountValueInput.value = formatPercent(discountValueInput.value);
        } else {
          formatMoneyInput(discountValueInput);
        }
        onDiscountValueChange();
      });
      discountValueInput.addEventListener('blur', function () {
        var _row$querySelector4;
        var mode = ((_row$querySelector4 = row.querySelector('.discount-mode-select')) === null || _row$querySelector4 === void 0 ? void 0 : _row$querySelector4.value) || 'percent';
        if (mode === 'percent' && discountValueInput.value !== '') {
          discountValueInput.value = formatPercent(discountValueInput.value);
        } else {
          formatMoneyInput(discountValueInput);
        }
      });
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
  var updateDiscountInputState = function updateDiscountInputState(row) {
    var _row$querySelector5;
    var mode = ((_row$querySelector5 = row.querySelector('.discount-mode-select')) === null || _row$querySelector5 === void 0 ? void 0 : _row$querySelector5.value) || 'percent';
    var discountValueInput = row.querySelector('.discount-value-input');
    var discountValueLabel = row.querySelector('.discount-value-label');
    if (discountValueLabel) {
      discountValueLabel.textContent = mode === 'amount' ? 'Discount Amount' : 'Discount %';
    }
    if (discountValueInput) {
      discountValueInput.placeholder = mode === 'amount' ? '0.00' : '0%';
    }
    row.dataset.discountMode = mode;
  };
  var syncDiscountValueInput = function syncDiscountValueInput(row) {
    var _row$querySelector6, _row$querySelector8;
    var mode = ((_row$querySelector6 = row.querySelector('.discount-mode-select')) === null || _row$querySelector6 === void 0 ? void 0 : _row$querySelector6.value) || 'percent';
    var discountValueInput = row.querySelector('.discount-value-input');
    if (!discountValueInput) {
      return;
    }
    if (mode === 'amount') {
      var _row$querySelector7;
      var discountAmount = parseMoney(((_row$querySelector7 = row.querySelector('.discount-input')) === null || _row$querySelector7 === void 0 ? void 0 : _row$querySelector7.value) || 0);
      discountValueInput.value = discountAmount > 0 ? formatMoney(discountAmount) : '';
      return;
    }
    var discountPercent = parseMoney(((_row$querySelector8 = row.querySelector('.discount-percent-input')) === null || _row$querySelector8 === void 0 ? void 0 : _row$querySelector8.value) || 0);
    discountValueInput.value = discountPercent > 0 ? formatPercent(discountPercent) : '';
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
    var currentUnitPrice = parseMoney(unitPriceInput.value);
    var hasCurrentUnitPrice = unitPriceInput.value !== '';
    var shouldPopulate = force || !hasCurrentUnitPrice || overwriteZero && currentUnitPrice === 0;
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
        toastr.info("No automatic selling price is set for ".concat(getFishTypeName(fishTypeId), " yet. You can encode it in Inventory > Fish Prices or type a manual amount here."));
      }
    }
  };

  // Update all rows fish box availability
  var updateAllRowsFishBoxAvailability = function updateAllRowsFishBoxAvailability() {
    root.querySelectorAll('.sales-detail-row').forEach(function (row) {
      if (row.dataset.scanned === 'true') {
        return;
      }
      var fishTypeSelect = row.querySelector('.fish-type-select');
      if (fishTypeSelect && fishTypeSelect.value) handleFishTypeChange(fishTypeSelect, true);
    });
  };
  var createSalesDetailRow = function createSalesDetailRow() {
    var template = document.getElementById('sales-detail-row-template');
    if (!template) return null;
    var newRow = template.content.cloneNode(true).querySelector('.sales-detail-row');
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
  var clearSalesDetailRow = function clearSalesDetailRow(row) {
    if (!row) {
      return;
    }
    row.dataset.scanned = 'false';
    row.dataset.activeFishTypeId = '';
    row.dataset.discountMode = 'percent';
    delete row.dataset.missingPriceWarningShownFor;
    row.querySelectorAll('.fish-type-hidden-input, .quantity-hidden-input').forEach(function (input) {
      return input.remove();
    });
    var fishTypeSelect = row.querySelector('.fish-type-select');
    var quantityInput = row.querySelector('.quantity-input');
    var unitPriceInput = row.querySelector('.unit-price-input');
    var discountModeSelect = row.querySelector('.discount-mode-select');
    var discountValueInput = row.querySelector('.discount-value-input');
    var discountPercentInput = row.querySelector('.discount-percent-input');
    var discountInput = row.querySelector('.discount-input');
    var subTotalInput = row.querySelector('.sub-total-input');
    var itemInput = row.querySelector('.item-input');
    var itemDescriptionInput = row.querySelector('.item-description-input');
    var fishBoxesContainer = row.querySelector('.fish-boxes-container');
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
  addBtn.addEventListener('click', function () {
    createSalesDetailRow();
  });

  // Remove sales detail
  container.addEventListener('click', function (e) {
    if (!e.target.closest('.remove-detail-btn')) {
      return;
    }
    var row = e.target.closest('.sales-detail-row');
    if (container.children.length > 1) {
      row === null || row === void 0 || row.remove();
      updateTotalAmount();
      updateAllRowsFishBoxAvailability();
      return;
    }
    clearSalesDetailRow(row);
  });
  if (initialPaidAmountInput) {
    protectAmountInput(initialPaidAmountInput);
    initialPaidAmountInput.addEventListener('input', function () {
      validateInitialPayment();
    });
    initialPaidAmountInput.addEventListener('change', function () {
      formatMoneyInput(initialPaidAmountInput);
      validateInitialPayment();
    });
    initialPaidAmountInput.addEventListener('blur', function () {
      formatMoneyInput(initialPaidAmountInput);
    });
  }
  function handleQuantityChange(quantityInput) {
    var _availableBoxes$lengt;
    var row = quantityInput.closest('.sales-detail-row');
    if (row.dataset.scanned === 'true') {
      calculateSubTotal(quantityInput);
      updateTotalAmount();
      return;
    }
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
    var _row$querySelector9;
    var skipUpdate = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    var row = fishTypeSelect.closest('.sales-detail-row');
    if (row.dataset.scanned === 'true' && (_row$querySelector9 = row.querySelector('.fish-box-hidden-input')) !== null && _row$querySelector9 !== void 0 && _row$querySelector9.value) {
      applySuggestedPriceToRow(row, {
        overwriteZero: true,
        showMissingPriceWarning: true
      });
      return;
    }
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
    var discountModeSelect = row.querySelector('.discount-mode-select');
    var discountValueInput = row.querySelector('.discount-value-input');
    var discountPercentInput = row.querySelector('.discount-percent-input');
    var discountInput = row.querySelector('.discount-input');
    var quantityInput = row.querySelector('.quantity-input');
    var subTotalInput = row.querySelector('.sub-total-input');
    if (!unitPriceInput || !quantityInput || !subTotalInput) return;
    var unitPrice = parseMoney(unitPriceInput.value);
    var discountMode = (discountModeSelect === null || discountModeSelect === void 0 ? void 0 : discountModeSelect.value) || 'percent';
    var rawDiscountValue = discountValueInput ? parseMoney(discountValueInput.value) : 0;
    var discountPercent = discountMode === 'percent' ? clampDiscountPercent(rawDiscountValue) : 0;
    var discount = 0;
    var quantity = parseInt(quantityInput.value) || 0;
    if (discountMode === 'amount') {
      discount = rawDiscountValue;
      if (discount > unitPrice) {
        discount = unitPrice;
        if (discountValueInput) {
          discountValueInput.value = formatMoney(discount);
        }
      }
      discountPercent = unitPrice > 0 ? roundMoney(discount / unitPrice * 100) : 0;
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
    var total = Array.from(root.querySelectorAll('.sub-total-input')).reduce(function (sum, input) {
      return sum + parseMoney(input.value);
    }, 0);
    totalAmountDisplay.textContent = "\u20B1".concat(formatMoney(total));
    if (totalAmountInput) totalAmountInput.value = total.toFixed(2);
    validateInitialPayment();
  }
  window.refreshSalesTotals = function () {
    root.querySelectorAll('.sales-detail-row').forEach(function (row) {
      var unitPriceInput = row.querySelector('.unit-price-input');
      if (unitPriceInput) {
        calculateSubTotal(unitPriceInput);
      }
    });
    updateTotalAmount();
  };
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
    var maxPaymentAmount = parseMoney((totalAmountInput === null || totalAmountInput === void 0 ? void 0 : totalAmountInput.value) || 0);
    var currentAmount = parseMoney(initialPaidAmountInput.value);
    var hasCurrentAmount = initialPaidAmountInput.value !== '';
    var requiresReferenceNumber = ['GCash', 'Bank Transfer'].includes((initialPaymentMethodSelect === null || initialPaymentMethodSelect === void 0 ? void 0 : initialPaymentMethodSelect.value) || '');
    if (initialReferenceNumberGroup) {
      initialReferenceNumberGroup.classList.toggle('hidden', !requiresReferenceNumber);
    }
    if (initialReferenceNumberInput) {
      initialReferenceNumberInput.required = hasCurrentAmount && requiresReferenceNumber;
      if (!requiresReferenceNumber) {
        initialReferenceNumberInput.value = '';
        initialReferenceNumberInput.setCustomValidity('');
      }
    }
    initialPaidAmountInput.max = maxPaymentAmount.toFixed(2);
    if (initialPaymentMethodSelect) {
      initialPaymentMethodSelect.required = hasCurrentAmount;
      initialPaymentMethodSelect.setCustomValidity(hasCurrentAmount && !initialPaymentMethodSelect.value ? 'Please select the payment method.' : '');
    }
    if (initialPaymentMaxAmount) {
      initialPaymentMaxAmount.textContent = formatMoney(maxPaymentAmount);
    }
    if (!hasCurrentAmount) {
      setInitialPaymentError('');
      return;
    }
    if (currentAmount > maxPaymentAmount) {
      setInitialPaymentError("Payment amount cannot exceed the remaining balance of \u20B1".concat(formatMoney(maxPaymentAmount)));
      return;
    }
    if (currentAmount <= 0) {
      setInitialPaymentError('Payment amount must be greater than 0');
      return;
    }
    setInitialPaymentError('');
  }
  initialPaymentMethodSelect === null || initialPaymentMethodSelect === void 0 || initialPaymentMethodSelect.addEventListener('change', validateInitialPayment);
  validateInitialPayment();

  // Initialize total amount
  root.querySelectorAll('.sales-detail-row').forEach(function (row) {
    hydrateSuggestedPriceOptions(row);
    bindRowEvents(row);
    updateDiscountInputState(row);
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
  window.bindSalesDetailRow = function (row) {
    if (!row) {
      return;
    }
    hydrateSuggestedPriceOptions(row);
    bindRowEvents(row);
    updateDiscountInputState(row);
    updateTotalAmount();
  };
  var selectFishTypeForRow = function selectFishTypeForRow(row, fishTypeId) {
    if (!row || !fishTypeId) {
      return;
    }
    var fishTypeSelect = row.querySelector('.fish-type-select');
    var quantityInput = row.querySelector('.quantity-input');
    if (!fishTypeSelect) {
      return;
    }
    fishTypeSelect.value = String(fishTypeId);
    if (quantityInput && !quantityInput.value) {
      quantityInput.value = 1;
    }
    fishTypeSelect.dispatchEvent(new Event('change', {
      bubbles: true
    }));
    row.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest'
    });
  };
  var addFishTypeToSales = function addFishTypeToSales(fishTypeId) {
    var targetRow = Array.from(container.querySelectorAll('.sales-detail-row')).find(function (row) {
      var fishTypeSelect = row.querySelector('.fish-type-select');
      var boxInput = row.querySelector('.fish-box-hidden-input');
      return fishTypeSelect && !fishTypeSelect.value && !(boxInput !== null && boxInput !== void 0 && boxInput.value);
    });
    if (!targetRow) {
      targetRow = createSalesDetailRow();
    }
    selectFishTypeForRow(targetRow, fishTypeId);
  };
  var initializeFishQuickAdd = function initializeFishQuickAdd() {
    var quickAdd = root.querySelector('[data-fish-quick-add]');
    var input = quickAdd === null || quickAdd === void 0 ? void 0 : quickAdd.querySelector('[data-fish-quick-add-input]');
    var results = quickAdd === null || quickAdd === void 0 ? void 0 : quickAdd.querySelector('[data-fish-quick-add-results]');
    var clearButton = quickAdd === null || quickAdd === void 0 ? void 0 : quickAdd.querySelector('[data-fish-quick-add-clear]');
    if (!quickAdd || !input || !results || input.dataset.fishQuickAddBound === 'true') {
      return;
    }
    input.dataset.fishQuickAddBound = 'true';
    var hideResults = function hideResults() {
      results.classList.add('hidden');
      results.innerHTML = '';
    };
    var renderResults = function renderResults() {
      var query = input.value.trim().toLowerCase();
      if (query.length < 1) {
        hideResults();
        return;
      }
      var matches = (SALES_CONFIG.fishTypes || []).filter(function (fishType) {
        return String(fishType.name || '').toLowerCase().includes(query);
      }).slice(0, 8);
      if (matches.length === 0) {
        results.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No fish found.</div>';
        results.classList.remove('hidden');
        return;
      }
      results.innerHTML = matches.map(function (fishType) {
        var _SALES_CONFIG$fishPri3;
        var price = (_SALES_CONFIG$fishPri3 = SALES_CONFIG.fishPrices) === null || _SALES_CONFIG$fishPri3 === void 0 ? void 0 : _SALES_CONFIG$fishPri3[String(fishType.id)];
        var priceLabel = price ? "\u20B1".concat(formatMoney(price)) : 'No price set';
        return "\n                    <button type=\"button\"\n                            class=\"flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm transition hover:bg-blue-50\"\n                            data-fish-quick-add-option\n                            data-fish-type-id=\"".concat(fishType.id, "\">\n                        <span class=\"font-semibold text-slate-900\">").concat(escapeHtml(fishType.name || 'Unnamed fish'), "</span>\n                        <span class=\"text-xs font-semibold text-slate-500\">").concat(escapeHtml(priceLabel), "</span>\n                    </button>\n                ");
      }).join('');
      results.classList.remove('hidden');
    };
    var escapeHtml = function escapeHtml(value) {
      return String(value !== null && value !== void 0 ? value : '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    };
    input.addEventListener('input', renderResults);
    input.addEventListener('focus', renderResults);
    results.addEventListener('click', function (event) {
      var option = event.target.closest('[data-fish-quick-add-option]');
      if (!option) {
        return;
      }
      addFishTypeToSales(option.dataset.fishTypeId);
      input.value = '';
      hideResults();
      input.focus();
    });
    clearButton === null || clearButton === void 0 || clearButton.addEventListener('click', function () {
      input.value = '';
      hideResults();
      input.focus();
    });
    document.addEventListener('click', function (event) {
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
/******/ })()
;
