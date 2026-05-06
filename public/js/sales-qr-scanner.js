/******/ (() => { // webpackBootstrap
/*!******************************************!*\
  !*** ./resources/js/sales-qr-scanner.js ***!
  \******************************************/
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
var SalesQRScanner = /*#__PURE__*/function () {
  function SalesQRScanner() {
    var _window$salesQrScanne;
    _classCallCheck(this, SalesQRScanner);
    this.scanner = null;
    this.modal = null;
    this.isModalCreated = false;
    this.isProcessing = false;
    this.onScanSuccess = null;
    this.lookupUrlTemplate = ((_window$salesQrScanne = window.salesQrScannerConfig) === null || _window$salesQrScanne === void 0 ? void 0 : _window$salesQrScanne.lookupUrlTemplate) || null;
    this.handleEscape = this.handleEscape.bind(this);
  }
  return _createClass(SalesQRScanner, [{
    key: "setScanSuccessCallback",
    value: function setScanSuccessCallback(callback) {
      this.onScanSuccess = callback;
    }
  }, {
    key: "openModal",
    value: function openModal() {
      var _this = this;
      if (typeof window.QrScanner !== 'function') {
        this.notifyScannerUnavailable();
        return;
      }
      this.createModal();
      if (this.modal) {
        this.modal.classList.remove('hidden');
      }
      this.startScanner()["catch"](function (error) {
        console.error('Unable to start sales QR scanner.', error);
        _this.showErrorState(_this.getCameraErrorMessage(error));
      });
    }
  }, {
    key: "closeModal",
    value: function () {
      var _closeModal = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var _this2 = this;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              this.isProcessing = false;
              document.removeEventListener('keydown', this.handleEscape);
              _context.n = 1;
              return this.stopScanner();
            case 1:
              if (this.modal) {
                this.modal.classList.add('hidden');
                setTimeout(function () {
                  if (_this2.modal && _this2.modal.parentNode) {
                    _this2.modal.parentNode.removeChild(_this2.modal);
                    _this2.modal = null;
                    _this2.isModalCreated = false;
                  }
                }, 200);
              }
            case 2:
              return _context.a(2);
          }
        }, _callee, this);
      }));
      function closeModal() {
        return _closeModal.apply(this, arguments);
      }
      return closeModal;
    }()
  }, {
    key: "handleEscape",
    value: function handleEscape(event) {
      if (event.key === 'Escape') {
        this.closeModal()["catch"](function () {});
      }
    }
  }, {
    key: "createModal",
    value: function createModal() {
      var _this3 = this;
      if (this.isModalCreated) {
        return;
      }
      document.body.insertAdjacentHTML('beforeend', "\n            <div id=\"salesQrScannerModal\" class=\"fixed inset-0 hidden overflow-y-auto\" style=\"z-index: 140;\">\n                <div class=\"flex min-h-screen items-center justify-center px-4 py-6 sm:px-6\">\n                    <button type=\"button\" data-sales-qr-close class=\"fixed inset-0 bg-slate-900/35 backdrop-blur-[2px]\" aria-label=\"Close QR scanner\"></button>\n\n                    <div class=\"relative z-10 w-full overflow-hidden bg-white\" style=\"max-width: 35rem; border-radius: 2rem; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 30px 80px rgba(15, 23, 42, 0.18);\" role=\"dialog\" aria-modal=\"true\" aria-labelledby=\"salesQrScannerTitle\">\n                        <div class=\"px-6 py-6 text-white\" style=\"background: linear-gradient(90deg, #2f66f5 0%, #89adff 58%, #ffffff 100%);\">\n                            <div class=\"flex items-start justify-between gap-4\">\n                                <div>\n                                    <h3 id=\"salesQrScannerTitle\" class=\"text-[2rem] font-semibold leading-none tracking-tight\">QR Code Scanner</h3>\n                                    <p class=\"mt-3 text-base text-white/90\">Scan a fish box QR code to add it to this sale.</p>\n                                </div>\n\n                                <button type=\"button\" data-sales-qr-close class=\"rounded-full p-2 text-white/80 transition hover:bg-white/15 hover:text-white\" aria-label=\"Close QR scanner\">\n                                    <svg class=\"h-5 w-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">\n                                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"></path>\n                                    </svg>\n                                </button>\n                            </div>\n                        </div>\n\n                        <div class=\"space-y-5 bg-white px-6 pb-6 pt-5\">\n                            <div class=\"relative overflow-hidden border border-slate-200 bg-slate-100 shadow-inner\" style=\"border-radius: 1.75rem;\">\n                                <video id=\"salesQrVideo\" class=\"w-full\" style=\"display: block; height: min(27rem, 52vh); object-fit: cover; background: #0f172a;\" autoplay muted playsinline></video>\n                                <div class=\"pointer-events-none absolute inset-0\">\n                                    <div class=\"absolute left-[23%] top-[16%] h-11 w-11 rounded-tl-2xl border-l-[5px] border-t-[5px] border-[#f4c117]\"></div>\n                                    <div class=\"absolute right-[23%] top-[16%] h-11 w-11 rounded-tr-2xl border-r-[5px] border-t-[5px] border-[#f4c117]\"></div>\n                                    <div class=\"absolute bottom-[16%] left-[23%] h-11 w-11 rounded-bl-2xl border-b-[5px] border-l-[5px] border-[#f4c117]\"></div>\n                                    <div class=\"absolute bottom-[16%] right-[23%] h-11 w-11 rounded-br-2xl border-b-[5px] border-r-[5px] border-[#f4c117]\"></div>\n                                </div>\n                            </div>\n\n                            <div id=\"salesQrStatus\" class=\"rounded-[1.5rem] border border-slate-200 bg-white px-6 py-4 text-center shadow-sm\">\n                                Preparing scanner...\n                            </div>\n\n                            <div class=\"flex flex-col gap-3\">\n                                <button type=\"button\" id=\"salesQrRetry\" class=\"hidden w-full rounded-[1.25rem] border border-blue-200 bg-blue-50 px-4 py-4 text-base font-semibold text-blue-700 transition hover:bg-blue-100\">\n                                    Try Again\n                                </button>\n                                <button type=\"button\" data-sales-qr-close class=\"w-full rounded-[1.25rem] bg-slate-900 px-4 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-slate-800\">\n                                    Close Scanner\n                                </button>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n            </div>\n        ");
      this.modal = document.getElementById('salesQrScannerModal');
      this.isModalCreated = true;
      this.modal.querySelectorAll('[data-sales-qr-close]').forEach(function (button) {
        button.addEventListener('click', function () {
          return _this3.closeModal()["catch"](function () {});
        });
      });
      var retryButton = document.getElementById('salesQrRetry');
      if (retryButton) {
        retryButton.addEventListener('click', function () {
          retryButton.classList.add('hidden');
          _this3.startScanner()["catch"](function (error) {
            console.error('Unable to restart sales QR scanner.', error);
            _this3.showErrorState(_this3.getCameraErrorMessage(error));
          });
        });
      }
      document.addEventListener('keydown', this.handleEscape);
    }
  }, {
    key: "updateStatus",
    value: function updateStatus(html) {
      var statusElement = document.getElementById('salesQrStatus');
      if (statusElement) {
        statusElement.innerHTML = html;
      }
    }
  }, {
    key: "showLoadingState",
    value: function showLoadingState(message) {
      this.updateStatus("\n            <div class=\"flex items-center justify-center gap-3 text-left\">\n                <span class=\"h-5 w-5 animate-spin rounded-full border-2 border-blue-100 border-t-blue-600\"></span>\n                <div>\n                    <p class=\"text-base font-semibold text-blue-700\">".concat(message, "</p>\n                    <p class=\"text-sm text-slate-500\">Hold steady while we prepare the scanner.</p>\n                </div>\n            </div>\n        "));
    }
  }, {
    key: "showReadyState",
    value: function showReadyState() {
      this.updateStatus("\n            <div class=\"text-center\">\n                <p class=\"text-[1.35rem] font-semibold text-emerald-600\">Camera active</p>\n                <p class=\"mt-1 text-base text-slate-500\">Point your camera at a fish box QR code.</p>\n            </div>\n        ");
    }
  }, {
    key: "showErrorState",
    value: function showErrorState(message) {
      this.updateStatus("\n            <div class=\"text-center\">\n                <p class=\"text-lg font-semibold text-red-600\">".concat(message, "</p>\n                <p class=\"mt-1 text-sm text-slate-500\">Allow camera access and try again.</p>\n            </div>\n        "));
      var retryButton = document.getElementById('salesQrRetry');
      if (retryButton) {
        retryButton.classList.remove('hidden');
      }
    }
  }, {
    key: "getCameraErrorMessage",
    value: function getCameraErrorMessage(error) {
      var _error$message;
      var errorName = (error === null || error === void 0 ? void 0 : error.name) || '';
      var errorMessage = (error === null || error === void 0 || (_error$message = error.message) === null || _error$message === void 0 ? void 0 : _error$message.toLowerCase()) || '';
      if (errorName === 'NotAllowedError' || errorName === 'PermissionDeniedError') {
        return 'Camera access was denied.';
      }
      if (errorName === 'NotReadableError' || errorMessage.includes('already in use')) {
        return 'Camera is already in use by another app.';
      }
      if (errorName === 'NotFoundError' || errorMessage.includes('camera not found')) {
        return 'No camera was found on this device.';
      }
      if (errorName === 'NotSupportedError' || errorMessage.includes('secure context')) {
        return 'This browser cannot start the camera here.';
      }
      return 'Unable to start the camera.';
    }
  }, {
    key: "startScanner",
    value: function () {
      var _startScanner = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        var _this4 = this;
        var videoElement, retryButton;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              if (!(!this.modal || this.modal.classList.contains('hidden'))) {
                _context2.n = 1;
                break;
              }
              return _context2.a(2);
            case 1:
              if (!(typeof window.QrScanner !== 'function')) {
                _context2.n = 2;
                break;
              }
              throw new Error('Legacy QR scanner library is not available.');
            case 2:
              videoElement = document.getElementById('salesQrVideo');
              if (videoElement) {
                _context2.n = 3;
                break;
              }
              throw new Error('QR video element is missing.');
            case 3:
              retryButton = document.getElementById('salesQrRetry');
              if (retryButton) {
                retryButton.classList.add('hidden');
              }
              this.showLoadingState('Starting camera...');
              _context2.n = 4;
              return this.stopScanner();
            case 4:
              this.scanner = new window.QrScanner(videoElement, function (result) {
                var qrCode = typeof result === 'string' ? result : (result === null || result === void 0 ? void 0 : result.data) || '';
                _this4.handleScanResult(qrCode);
              }, {
                preferredCamera: 'environment',
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 10
              });
              _context2.n = 5;
              return this.scanner.start();
            case 5:
              this.showReadyState();
            case 6:
              return _context2.a(2);
          }
        }, _callee2, this);
      }));
      function startScanner() {
        return _startScanner.apply(this, arguments);
      }
      return startScanner;
    }()
  }, {
    key: "stopScanner",
    value: function () {
      var _stopScanner = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3() {
        var videoElement, _t;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.p = _context3.n) {
            case 0:
              if (!this.scanner) {
                _context3.n = 5;
                break;
              }
              _context3.p = 1;
              _context3.n = 2;
              return this.scanner.stop();
            case 2:
              _context3.n = 4;
              break;
            case 3:
              _context3.p = 3;
              _t = _context3.v;
              console.warn('Unable to stop sales QR scanner cleanly.', _t);
            case 4:
              try {
                this.scanner.destroy();
              } catch (error) {
                console.warn('Unable to destroy sales QR scanner cleanly.', error);
              }
              this.scanner = null;
            case 5:
              videoElement = document.getElementById('salesQrVideo');
              if (videoElement !== null && videoElement !== void 0 && videoElement.srcObject) {
                videoElement.srcObject.getTracks().forEach(function (track) {
                  return track.stop();
                });
                videoElement.srcObject = null;
              }
            case 6:
              return _context3.a(2);
          }
        }, _callee3, this, [[1, 3]]);
      }));
      function stopScanner() {
        return _stopScanner.apply(this, arguments);
      }
      return stopScanner;
    }()
  }, {
    key: "handleScanResult",
    value: function () {
      var _handleScanResult = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4(qrCode) {
        var fishBox, _t2;
        return _regenerator().w(function (_context4) {
          while (1) switch (_context4.p = _context4.n) {
            case 0:
              if (!(!qrCode || this.isProcessing)) {
                _context4.n = 1;
                break;
              }
              return _context4.a(2);
            case 1:
              this.isProcessing = true;
              _context4.n = 2;
              return this.stopScanner();
            case 2:
              this.showLoadingState("Processing ".concat(qrCode, "..."));
              _context4.p = 3;
              _context4.n = 4;
              return this.getFishBoxByQRCode(qrCode);
            case 4:
              fishBox = _context4.v;
              this.handleSalesQRScanSuccess(fishBox);
              _context4.n = 5;
              return this.closeModal();
            case 5:
              _context4.n = 7;
              break;
            case 6:
              _context4.p = 6;
              _t2 = _context4.v;
              console.error('Sales QR processing failed.', _t2);
              this.showErrorState((_t2 === null || _t2 === void 0 ? void 0 : _t2.message) || 'Error processing QR code. Please try again.');
            case 7:
              _context4.p = 7;
              this.isProcessing = false;
              return _context4.f(7);
            case 8:
              return _context4.a(2);
          }
        }, _callee4, this, [[3, 6, 7, 8]]);
      }));
      function handleScanResult(_x) {
        return _handleScanResult.apply(this, arguments);
      }
      return handleScanResult;
    }()
  }, {
    key: "getFishBoxByQRCode",
    value: function () {
      var _getFishBoxByQRCode = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5(qrCode) {
        var response, payload;
        return _regenerator().w(function (_context5) {
          while (1) switch (_context5.n) {
            case 0:
              _context5.n = 1;
              return fetch(this.getLookupUrl(qrCode), {
                method: 'GET',
                headers: {
                  Accept: 'application/json',
                  'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
              });
            case 1:
              response = _context5.v;
              _context5.n = 2;
              return response.json()["catch"](function () {
                return null;
              });
            case 2:
              payload = _context5.v;
              if (!(!response.ok || !(payload !== null && payload !== void 0 && payload.success))) {
                _context5.n = 3;
                break;
              }
              throw new Error((payload === null || payload === void 0 ? void 0 : payload.message) || 'Fish box not found or not available for sale.');
            case 3:
              return _context5.a(2, payload.data || payload);
          }
        }, _callee5, this);
      }));
      function getFishBoxByQRCode(_x2) {
        return _getFishBoxByQRCode.apply(this, arguments);
      }
      return getFishBoxByQRCode;
    }()
  }, {
    key: "getLookupUrl",
    value: function getLookupUrl(qrCode) {
      if (this.lookupUrlTemplate) {
        return this.lookupUrlTemplate.replace('__QR_CODE__', encodeURIComponent(qrCode));
      }
      return new URL("sales/fish-boxes/".concat(encodeURIComponent(qrCode)), window.location.href).toString();
    }
  }, {
    key: "notifyScannerUnavailable",
    value: function notifyScannerUnavailable() {
      var message = 'QR scanner could not be loaded. Please refresh and try again.';
      if (window.toastr) {
        window.toastr.error(message);
        return;
      }
      alert(message);
    }
  }, {
    key: "handleSalesQRScanSuccess",
    value: function handleSalesQRScanSuccess(fishBox) {
      var _fishBoxData$fish_typ;
      var fishBoxData = fishBox.data || fishBox;
      if (typeof this.onScanSuccess === 'function') {
        this.onScanSuccess(fishBoxData);
      } else {
        this.addFishBoxToSalesDetails(fishBoxData);
      }
      var fishTypeName = ((_fishBoxData$fish_typ = fishBoxData.fish_type) === null || _fishBoxData$fish_typ === void 0 ? void 0 : _fishBoxData$fish_typ.name) || fishBoxData.fish_type_name || fishBoxData.fish_type || 'Unknown';
      var boxNumber = fishBoxData.broker_box_number || fishBoxData.id;
      var boxName = fishBoxData.name || "Fish Box #".concat(boxNumber);
      if (window.toastr) {
        window.toastr.success("".concat(boxName, " (").concat(fishTypeName, ") added! Fish type auto-selected, quantity set to 1."));
      }
    }
  }, {
    key: "getActiveSalesFormRoot",
    value: function getActiveSalesFormRoot() {
      var modalRoots = Array.from(document.querySelectorAll('[data-app-modal-root]'));
      return modalRoots.reverse().find(function (modalRoot) {
        return modalRoot.offsetParent !== null;
      }) || modalRoots.at(-1) || document;
    }
  }, {
    key: "addFishBoxToSalesDetails",
    value: function addFishBoxToSalesDetails(fishBox) {
      var _fishBox$fish_type, _fishBox$fish_type2;
      var salesFormRoot = this.getActiveSalesFormRoot();
      var container = salesFormRoot.querySelector('#sales-details-container');
      if (!container) {
        return;
      }
      var fishTypeId = fishBox.fish_type_id || ((_fishBox$fish_type = fishBox.fish_type) === null || _fishBox$fish_type === void 0 ? void 0 : _fishBox$fish_type.id) || null;
      var fishTypeName = ((_fishBox$fish_type2 = fishBox.fish_type) === null || _fishBox$fish_type2 === void 0 ? void 0 : _fishBox$fish_type2.name) || fishBox.fish_type_name || fishBox.fish_type || '';
      var existingRows = container.querySelectorAll('.sales-detail-row');
      var targetRow = null;
      var rowIndex = null;
      for (var index = 0; index < existingRows.length; index++) {
        var row = existingRows[index];
        var _fishTypeSelect = row.querySelector('.fish-type-select');
        if (_fishTypeSelect && !_fishTypeSelect.value) {
          targetRow = row;
          rowIndex = row.dataset.index;
          break;
        }
      }
      if (!targetRow) {
        var template = document.getElementById('sales-detail-row-template');
        if (!template) {
          console.error('Sales detail row template not found.');
          return;
        }
        rowIndex = existingRows.length;
        var newRow = template.content.cloneNode(true).querySelector('.sales-detail-row');
        newRow.dataset.index = rowIndex;
        newRow.querySelectorAll('input, select').forEach(function (input) {
          if (input.name) {
            input.name = input.name.replace('[INDEX]', "[".concat(rowIndex, "]"));
          }
        });
        container.appendChild(newRow);
        targetRow = container.querySelector(".sales-detail-row[data-index=\"".concat(rowIndex, "\"]"));
      }
      if (!targetRow) {
        console.error('Could not find or create target sales row.');
        return;
      }
      targetRow.dataset.scanned = 'true';
      var fishTypeSelect = targetRow.querySelector('.fish-type-select');
      if (fishTypeSelect && fishTypeId) {
        fishTypeSelect.value = String(fishTypeId);
        var existingHiddenTypeInput = fishTypeSelect.parentNode.querySelector('.fish-type-hidden-input');
        if (existingHiddenTypeInput) {
          existingHiddenTypeInput.remove();
        }
        var hiddenTypeInput = document.createElement('input');
        hiddenTypeInput.type = 'hidden';
        hiddenTypeInput.name = fishTypeSelect.name;
        hiddenTypeInput.value = String(fishTypeId);
        hiddenTypeInput.className = 'fish-type-hidden-input';
        fishTypeSelect.parentNode.appendChild(hiddenTypeInput);
        fishTypeSelect.disabled = true;
        fishTypeSelect.classList.add('bg-gray-100', 'cursor-not-allowed');
        if (typeof window.refreshSalesSuggestedPriceForRow === 'function') {
          window.refreshSalesSuggestedPriceForRow(targetRow, {
            force: true,
            overwriteZero: true,
            showMissingPriceWarning: true
          });
        }
      }
      var quantityInput = targetRow.querySelector('.quantity-input');
      if (quantityInput) {
        quantityInput.value = 1;
        var existingHiddenQtyInput = quantityInput.parentNode.querySelector('.quantity-hidden-input');
        if (existingHiddenQtyInput) {
          existingHiddenQtyInput.remove();
        }
        var hiddenQtyInput = document.createElement('input');
        hiddenQtyInput.type = 'hidden';
        hiddenQtyInput.name = quantityInput.name;
        hiddenQtyInput.value = '1';
        hiddenQtyInput.className = 'quantity-hidden-input';
        quantityInput.parentNode.appendChild(hiddenQtyInput);
        quantityInput.disabled = true;
        quantityInput.classList.add('bg-gray-100', 'cursor-not-allowed');
      }
      var itemInput = targetRow.querySelector('.item-input');
      if (itemInput) {
        itemInput.value = fishTypeName;
      }
      if (fishTypeSelect) {
        fishTypeSelect.dispatchEvent(new Event('change', {
          bubbles: true
        }));
      }
      var fishBoxesContainer = targetRow.querySelector('.fish-boxes-container');
      if (fishBoxesContainer) {
        var boxLabel = fishBox.name || "Fish Box #".concat(fishBox.broker_box_number || fishBox.id);
        fishBoxesContainer.innerHTML = "\n                <div class=\"fish-box-item mb-2\">\n                    <div class=\"w-full rounded-lg border border-green-300 bg-green-50 px-3 py-2 text-sm\">\n                        <div class=\"flex items-center text-green-700\">\n                            <svg class=\"mr-2 h-4 w-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">\n                                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\"></path>\n                            </svg>\n                            <span class=\"font-medium\">".concat(boxLabel, "</span>\n                            <span class=\"ml-2 text-xs\">(Scanned)</span>\n                        </div>\n                    </div>\n                    <input type=\"hidden\" name=\"sales_details[").concat(rowIndex, "][box_id][]\" value=\"").concat(fishBox.id, "\">\n                </div>\n            ");
      }
    }
  }]);
}();
window.SalesQRScanner = SalesQRScanner;
/******/ })()
;