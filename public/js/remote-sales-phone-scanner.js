/******/ (() => { // webpackBootstrap
/*!****************************************************!*\
  !*** ./resources/js/remote-sales-phone-scanner.js ***!
  \****************************************************/
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
(function () {
  var scanner = null;
  var isProcessing = false;
  var restartTimer = null;
  function getConfig() {
    return window.remoteSalesPhoneScannerConfig || {};
  }
  function getCsrfToken() {
    var _document$querySelect;
    return ((_document$querySelect = document.querySelector('meta[name="csrf-token"]')) === null || _document$querySelect === void 0 ? void 0 : _document$querySelect.getAttribute('content')) || '';
  }
  function getAppBasePath() {
    var brokerMarker = '/broker/';
    var markerIndex = window.location.pathname.indexOf(brokerMarker);
    if (markerIndex === -1) {
      return '';
    }
    return window.location.pathname.slice(0, markerIndex);
  }
  function withAppBasePath(url) {
    if (!url || /^(https?:)?\/\//i.test(url) || !url.startsWith('/')) {
      return url;
    }
    var appBasePath = getAppBasePath();
    if (!appBasePath || url.startsWith("".concat(appBasePath, "/"))) {
      return url;
    }
    return "".concat(appBasePath).concat(url);
  }
  function setStatus(message) {
    var tone = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'slate';
    var status = document.querySelector('[data-phone-scanner-status]');
    if (!status) {
      return;
    }
    var toneClass = tone === 'green' ? 'border-green-200 bg-green-50 text-green-700' : tone === 'red' ? 'border-red-200 bg-red-50 text-red-700' : 'border-slate-200 bg-white text-slate-700';
    status.className = "rounded-2xl border px-4 py-3 text-center text-sm font-medium ".concat(toneClass);
    status.textContent = message;
  }
  function startScanner() {
    return _startScanner.apply(this, arguments);
  }
  function _startScanner() {
    _startScanner = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
      var video, _t;
      return _regenerator().w(function (_context) {
        while (1) switch (_context.p = _context.n) {
          case 0:
            if (getConfig().scanUrl) {
              _context.n = 1;
              break;
            }
            setStatus('Phone scanner session is not active.', 'red');
            return _context.a(2);
          case 1:
            if (!(typeof window.QrScanner !== 'function')) {
              _context.n = 2;
              break;
            }
            setStatus('QR scanner could not load. Refresh this page.', 'red');
            return _context.a(2);
          case 2:
            video = document.getElementById('remotePhoneScannerVideo');
            if (video) {
              _context.n = 3;
              break;
            }
            return _context.a(2);
          case 3:
            _context.n = 4;
            return stopScanner();
          case 4:
            setStatus('Starting camera...');
            scanner = new window.QrScanner(video, function (result) {
              var qrCode = typeof result === 'string' ? result : (result === null || result === void 0 ? void 0 : result.data) || '';
              handleScan(qrCode);
            }, {
              preferredCamera: 'environment',
              highlightScanRegion: true,
              highlightCodeOutline: true,
              maxScansPerSecond: 8
            });
            _context.p = 5;
            _context.n = 6;
            return scanner.start();
          case 6:
            setStatus('Camera active. Scan a fish box QR code.');
            _context.n = 8;
            break;
          case 7:
            _context.p = 7;
            _t = _context.v;
            setStatus('Unable to start camera. Allow camera access and refresh.', 'red');
          case 8:
            return _context.a(2);
        }
      }, _callee, null, [[5, 7]]);
    }));
    return _startScanner.apply(this, arguments);
  }
  function stopScanner() {
    return _stopScanner.apply(this, arguments);
  }
  function _stopScanner() {
    _stopScanner = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
      var _t2;
      return _regenerator().w(function (_context2) {
        while (1) switch (_context2.p = _context2.n) {
          case 0:
            clearTimeout(restartTimer);
            restartTimer = null;
            if (!scanner) {
              _context2.n = 5;
              break;
            }
            _context2.p = 1;
            _context2.n = 2;
            return scanner.stop();
          case 2:
            scanner.destroy();
            _context2.n = 4;
            break;
          case 3:
            _context2.p = 3;
            _t2 = _context2.v;
            console.warn('Unable to stop remote phone scanner cleanly.', _t2);
          case 4:
            scanner = null;
          case 5:
            return _context2.a(2);
        }
      }, _callee2, null, [[1, 3]]);
    }));
    return _stopScanner.apply(this, arguments);
  }
  function handleScan(_x) {
    return _handleScan.apply(this, arguments);
  }
  function _handleScan() {
    _handleScan = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(qrCode) {
      var response, payload, _t3;
      return _regenerator().w(function (_context3) {
        while (1) switch (_context3.p = _context3.n) {
          case 0:
            if (!(!qrCode || isProcessing)) {
              _context3.n = 1;
              break;
            }
            return _context3.a(2);
          case 1:
            isProcessing = true;
            setStatus('Sending fish box to laptop...');
            _context3.p = 2;
            _context3.n = 3;
            return fetch(withAppBasePath(getConfig().scanUrl), {
              method: 'POST',
              credentials: 'same-origin',
              headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: JSON.stringify({
                qr_code: qrCode
              })
            });
          case 3:
            response = _context3.v;
            _context3.n = 4;
            return response.json()["catch"](function () {
              return {};
            });
          case 4:
            payload = _context3.v;
            if (response.ok) {
              _context3.n = 5;
              break;
            }
            throw new Error(payload.message || 'Scan failed.');
          case 5:
            setStatus(payload.message || 'Fish box sent to laptop.', 'green');
            _context3.n = 7;
            break;
          case 6:
            _context3.p = 6;
            _t3 = _context3.v;
            setStatus(_t3.message || 'Unable to send scan.', 'red');
          case 7:
            _context3.p = 7;
            restartTimer = setTimeout(function () {
              isProcessing = false;
              setStatus('Ready for next fish box QR code.');
            }, 900);
            return _context3.f(7);
          case 8:
            return _context3.a(2);
        }
      }, _callee3, null, [[2, 6, 7, 8]]);
    }));
    return _handleScan.apply(this, arguments);
  }
  document.addEventListener('DOMContentLoaded', function () {
    var _document$querySelect2;
    (_document$querySelect2 = document.querySelector('[data-phone-scanner-start]')) === null || _document$querySelect2 === void 0 || _document$querySelect2.addEventListener('click', startScanner);
    startScanner();
  });
})();
/******/ })()
;