/******/ (() => { // webpackBootstrap
/*!************************************!*\
  !*** ./resources/js/sales-page.js ***!
  \************************************/
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
/**
 * Sales page modal and async form workflow
 * Keeps modal navigation and form submissions inside the current page.
 */

(function () {
  var MODAL_QUERY_KEYS = ['modal', 'edit', 'show', 'sale', 'print', 'auto_print'];
  var SALES_UPDATED_STORAGE_KEY = 'broker-sales-updated-token';
  var pendingSalesRecordsRefresh = false;
  window.BrokerSalesTransactionState = window.BrokerSalesTransactionState || {
    isSaving: false,
    lastSavedAt: null
  };
  function getSalesRoot() {
    return document.querySelector('[data-sales-page]');
  }
  function isSalesPageActive() {
    return Boolean(getSalesRoot());
  }
  function isSalesRecordsPageActive() {
    var _getSalesRoot;
    return Boolean((_getSalesRoot = getSalesRoot()) === null || _getSalesRoot === void 0 ? void 0 : _getSalesRoot.hasAttribute('data-sales-records'));
  }
  function toAbsoluteUrl(url) {
    return new URL(url, window.location.origin);
  }
  function getCleanSalesUrl() {
    var url = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : window.location.href;
    var parsedUrl = toAbsoluteUrl(url);
    MODAL_QUERY_KEYS.forEach(function (key) {
      return parsedUrl.searchParams["delete"](key);
    });
    return "".concat(parsedUrl.pathname).concat(parsedUrl.search).concat(parsedUrl.hash);
  }
  function getSalesBaseUrl() {
    var _getSalesRoot2;
    return ((_getSalesRoot2 = getSalesRoot()) === null || _getSalesRoot2 === void 0 ? void 0 : _getSalesRoot2.dataset.salesBaseUrl) || getCleanSalesUrl();
  }
  function parseHtml(html) {
    return new DOMParser().parseFromString(html, 'text/html');
  }
  function clearTeleportedModals() {
    document.querySelectorAll('[data-app-modal-root]').forEach(function (modal) {
      return modal.remove();
    });
    document.documentElement.classList.remove('modal-scroll-lock');
    document.body.classList.remove('modal-scroll-lock');
  }
  function pushHistory(url) {
    window.history.pushState({
      salesPage: true
    }, '', url);
  }
  function replaceHistory(url) {
    window.history.replaceState({
      salesPage: true
    }, '', url);
  }
  function reinitializeSalesFragment(fragment) {
    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
      window.Alpine.initTree(fragment);
    }
    if (typeof window.initializeBrokerSalesPage === 'function') {
      window.initializeBrokerSalesPage(fragment);
    }
  }
  function publishSalesUpdated(token) {
    if (!token) {
      return;
    }
    try {
      localStorage.setItem(SALES_UPDATED_STORAGE_KEY, JSON.stringify({
        token: String(token),
        at: Date.now()
      }));
    } catch (error) {}
  }
  function refreshSalesRecordsPage() {
    if (!isSalesRecordsPageActive()) {
      return;
    }
    refreshSalesFragment(getSalesBaseUrl(), 'silent')["catch"](function () {
      window.location.href = getSalesBaseUrl();
    });
  }
  function refreshSalesRecordsWhenVisible() {
    if (!isSalesRecordsPageActive()) {
      return;
    }
    if (document.hidden) {
      pendingSalesRecordsRefresh = true;
      return;
    }
    pendingSalesRecordsRefresh = false;
    refreshSalesRecordsPage();
  }
  function publishInitialSalesUpdate() {
    var _getSalesRoot3;
    var token = (_getSalesRoot3 = getSalesRoot()) === null || _getSalesRoot3 === void 0 ? void 0 : _getSalesRoot3.dataset.salesUpdatedToken;
    if (token) {
      publishSalesUpdated(token);
    }
  }
  function refreshSalesFragment(_x) {
    return _refreshSalesFragment.apply(this, arguments);
  }
  function _refreshSalesFragment() {
    _refreshSalesFragment = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(url) {
      var historyMode,
        response,
        html,
        parsedDocument,
        incomingFragment,
        currentFragment,
        _args = arguments;
      return _regenerator().w(function (_context) {
        while (1) switch (_context.n) {
          case 0:
            historyMode = _args.length > 1 && _args[1] !== undefined ? _args[1] : 'replace';
            _context.n = 1;
            return fetch(url, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              },
              credentials: 'same-origin'
            });
          case 1:
            response = _context.v;
            if (response.ok) {
              _context.n = 2;
              break;
            }
            throw new Error("Unable to refresh sales view (".concat(response.status, ")."));
          case 2:
            _context.n = 3;
            return response.text();
          case 3:
            html = _context.v;
            parsedDocument = parseHtml(html);
            incomingFragment = parsedDocument.querySelector('#sales-page-fragment');
            currentFragment = document.querySelector('#sales-page-fragment');
            if (!(!incomingFragment || !currentFragment)) {
              _context.n = 4;
              break;
            }
            throw new Error('Sales page fragment could not be resolved.');
          case 4:
            clearTeleportedModals();
            currentFragment.replaceWith(incomingFragment);
            if (historyMode === 'push') {
              pushHistory(url);
            } else if (historyMode === 'replace') {
              replaceHistory(url);
            }
            reinitializeSalesFragment(incomingFragment);
          case 5:
            return _context.a(2);
        }
      }, _callee);
    }));
    return _refreshSalesFragment.apply(this, arguments);
  }
  function escapeSelectorValue(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }
    return value.replace(/["\\]/g, '\\$&');
  }
  function dotKeyToBracketName(key) {
    return key.split('.').reduce(function (result, segment, index) {
      if (index === 0) {
        return segment;
      }
      return "".concat(result, "[").concat(segment, "]");
    }, '');
  }
  function clearFormErrors(form) {
    form.querySelectorAll('.sales-form-error').forEach(function (errorNode) {
      return errorNode.remove();
    });
    form.querySelectorAll('.sales-form-error-field').forEach(function (field) {
      field.classList.remove('sales-form-error-field', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    });
  }
  function ensureErrorSummary(form) {
    var summary = form.querySelector('[data-sales-form-errors]');
    if (!summary) {
      summary = document.createElement('div');
      summary.setAttribute('data-sales-form-errors', 'true');
      summary.className = 'sales-form-error mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
      form.prepend(summary);
    }
    return summary;
  }
  function renderFormErrors(form, errors) {
    var collectedMessages = [];
    Object.entries(errors || {}).forEach(function (_ref) {
      var _ref2 = _slicedToArray(_ref, 2),
        key = _ref2[0],
        messages = _ref2[1];
      var message = Array.isArray(messages) ? messages[0] : messages;
      if (!message) {
        return;
      }
      collectedMessages.push(message);
      var fieldName = dotKeyToBracketName(key);
      var selector = "[name=\"".concat(escapeSelectorValue(fieldName), "\"]");
      var fallbackSelector = "[name=\"".concat(escapeSelectorValue(key), "\"]");
      var field = form.querySelector(selector) || form.querySelector(fallbackSelector);
      if (!field) {
        return;
      }
      field.classList.add('sales-form-error-field', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
      var messageNode = document.createElement('p');
      messageNode.className = 'sales-form-error mt-1 text-sm text-red-600';
      messageNode.textContent = message;
      field.insertAdjacentElement('afterend', messageNode);
    });
    if (collectedMessages.length > 0) {
      var summary = ensureErrorSummary(form);
      summary.textContent = collectedMessages[0];
    }
  }
  function setFormSubmitting(form, isSubmitting) {
    window.BrokerSalesTransactionState.isSaving = isSubmitting;
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (submitControl) {
      submitControl.disabled = isSubmitting;
    });
  }
  function dispatchSalesTransactionEvent(name) {
    var detail = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    window.dispatchEvent(new CustomEvent(name, {
      detail: detail
    }));
  }
  function parseJsonResponse(_x2) {
    return _parseJsonResponse.apply(this, arguments);
  }
  function _parseJsonResponse() {
    _parseJsonResponse = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(response) {
      var contentType;
      return _regenerator().w(function (_context2) {
        while (1) switch (_context2.n) {
          case 0:
            contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
              _context2.n = 1;
              break;
            }
            return _context2.a(2, response.json());
          case 1:
            return _context2.a(2, {
              message: response.ok ? 'Saved successfully.' : 'Request failed.'
            });
        }
      }, _callee2);
    }));
    return _parseJsonResponse.apply(this, arguments);
  }
  function handleAsyncFormSubmit(_x3) {
    return _handleAsyncFormSubmit.apply(this, arguments);
  }
  function _handleAsyncFormSubmit() {
    _handleAsyncFormSubmit = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(form) {
      var response, payload, afterSaveUrl, _t, _t2;
      return _regenerator().w(function (_context3) {
        while (1) switch (_context3.p = _context3.n) {
          case 0:
            clearFormErrors(form);
            setFormSubmitting(form, true);
            dispatchSalesTransactionEvent('broker-sales:save-started', {
              form: form
            });
            _context3.p = 1;
            _context3.n = 2;
            return fetch(form.action, {
              method: (form.getAttribute('method') || 'POST').toUpperCase(),
              headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              credentials: 'same-origin',
              body: new FormData(form)
            });
          case 2:
            response = _context3.v;
            _context3.n = 3;
            return parseJsonResponse(response);
          case 3:
            payload = _context3.v;
            if (!(response.status === 422)) {
              _context3.n = 4;
              break;
            }
            renderFormErrors(form, payload.errors || {});
            if (window.toastr) {
              window.toastr.error(payload.message || 'Please review the highlighted fields.');
            }
            return _context3.a(2);
          case 4:
            if (response.ok) {
              _context3.n = 5;
              break;
            }
            throw new Error(payload.message || 'Unable to save the sales record.');
          case 5:
            if (window.toastr) {
              window.toastr.success(payload.message || 'Sales data saved successfully.');
            }
            afterSaveUrl = payload.redirect_url || form.dataset.salesAfterSaveUrl || getSalesBaseUrl();
            if (!payload.force_redirect) {
              _context3.n = 6;
              break;
            }
            window.location.href = afterSaveUrl;
            return _context3.a(2);
          case 6:
            _context3.p = 6;
            _context3.n = 7;
            return refreshSalesFragment(afterSaveUrl, 'replace');
          case 7:
            window.BrokerSalesTransactionState.lastSavedAt = Date.now();
            dispatchSalesTransactionEvent('broker-sales:save-succeeded', {
              form: form,
              payload: payload,
              afterSaveUrl: afterSaveUrl
            });
            _context3.n = 9;
            break;
          case 8:
            _context3.p = 8;
            _t = _context3.v;
            window.location.href = afterSaveUrl;
          case 9:
            _context3.n = 11;
            break;
          case 10:
            _context3.p = 10;
            _t2 = _context3.v;
            if (window.toastr) {
              window.toastr.error(_t2.message || 'Something went wrong while saving the form.');
            }
            dispatchSalesTransactionEvent('broker-sales:save-failed', {
              form: form,
              error: _t2
            });
          case 11:
            _context3.p = 11;
            setFormSubmitting(form, false);
            dispatchSalesTransactionEvent('broker-sales:save-finished', {
              form: form
            });
            return _context3.f(11);
          case 12:
            return _context3.a(2);
        }
      }, _callee3, null, [[6, 8], [1, 10, 11, 12]]);
    }));
    return _handleAsyncFormSubmit.apply(this, arguments);
  }
  function closeCurrentModal(closeUrl) {
    var modalRoot = document.querySelector('[data-app-modal-root]');
    var closeButton = modalRoot === null || modalRoot === void 0 ? void 0 : modalRoot.querySelector('[data-app-modal-close]');
    if (closeButton) {
      closeButton.click();
      return;
    }
    clearTeleportedModals();
    replaceHistory(closeUrl || getSalesBaseUrl());
  }
  document.addEventListener('click', function (event) {
    if (!isSalesPageActive()) {
      return;
    }
    var modalLink = event.target.closest('[data-sales-modal-link]');
    if (modalLink) {
      event.preventDefault();
      refreshSalesFragment(modalLink.href, 'push')["catch"](function (error) {
        if (window.toastr) {
          window.toastr.error(error.message || 'Unable to open the requested form.');
        }
      });
      return;
    }
    var modalCloseTrigger = event.target.closest('[data-sales-modal-close]');
    if (modalCloseTrigger) {
      event.preventDefault();
      closeCurrentModal(modalCloseTrigger.dataset.closeUrl || getSalesBaseUrl());
    }
  }, true);
  document.addEventListener('submit', function (event) {
    if (!isSalesPageActive()) {
      return;
    }
    var asyncForm = event.target.closest('form[data-sales-async-form]');
    if (!asyncForm) {
      return;
    }
    if (asyncForm.hasAttribute('data-sales-sync-submit')) {
      setFormSubmitting(asyncForm, true);
      dispatchSalesTransactionEvent('broker-sales:save-started', {
        form: asyncForm
      });
      return;
    }
    event.preventDefault();
    handleAsyncFormSubmit(asyncForm);
  });
  window.addEventListener('popstate', function () {
    if (!isSalesPageActive()) {
      return;
    }
    refreshSalesFragment(window.location.href, 'silent')["catch"](function () {
      // Keep popstate failures quiet to avoid trapping navigation.
    });
  });
  window.addEventListener('storage', function (event) {
    if (event.key !== SALES_UPDATED_STORAGE_KEY || !event.newValue) {
      return;
    }
    refreshSalesRecordsWhenVisible();
  });
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && pendingSalesRecordsRefresh) {
      refreshSalesRecordsWhenVisible();
    }
  });
  window.addEventListener('focus', function () {
    if (pendingSalesRecordsRefresh) {
      refreshSalesRecordsWhenVisible();
    }
  });
  publishInitialSalesUpdate();
})();
/******/ })()
;