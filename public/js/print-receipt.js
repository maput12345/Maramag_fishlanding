/******/ (() => { // webpackBootstrap
/*!***************************************!*\
  !*** ./resources/js/print-receipt.js ***!
  \***************************************/
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
// Print Receipt functionality
var PrintReceipt = /*#__PURE__*/function () {
  function PrintReceipt() {
    _classCallCheck(this, PrintReceipt);
  }
  return _createClass(PrintReceipt, null, [{
    key: "print",
    value: function print(receiptElementId) {
      var receiptTitle = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'Receipt';
      var receiptContent = document.getElementById(receiptElementId);
      if (!receiptContent) {
        PrintReceipt.notify('Receipt content could not be found.', 'error');
        return;
      }
      var watermarkLogoUrl = receiptContent.dataset.watermarkLogoUrl || '';

      // Create a new window for printing
      var printWindow = window.open('', '_blank', 'width=800,height=600');
      if (!printWindow) {
        PrintReceipt.notify('Please allow popups to print the receipt.', 'warning');
        return;
      }

      // Get the receipt HTML
      var receiptHTML = receiptContent.innerHTML;
      var watermarkHTML = watermarkLogoUrl ? "\n                <div class=\"receipt-watermark\" aria-hidden=\"true\">\n                    <img src=\"".concat(watermarkLogoUrl, "\" alt=\"\">\n                </div>\n            ") : '';

      // Create the complete HTML document for printing
      var printHTML = "\n            <!DOCTYPE html>\n            <html>\n            <head>\n                <title>".concat(receiptTitle, "</title>\n                <style>\n                    body {\n                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;\n                        margin: 0;\n                        padding: 20px;\n                        background: white;\n                        color: #333;\n                        position: relative;\n                    }\n                    .print-sheet {\n                        position: relative;\n                        min-height: calc(100vh - 40px);\n                    }\n                    .print-sheet__content {\n                        position: relative;\n                        z-index: 1;\n                    }\n                    .receipt-watermark {\n                        position: fixed;\n                        top: 50%;\n                        left: 50%;\n                        transform: translate(-50%, -50%);\n                        width: min(620px, 82vw);\n                        display: block;\n                        pointer-events: none;\n                        z-index: 0;\n                        text-align: center;\n                    }\n                    .receipt-watermark img {\n                        display: block;\n                        width: 100%;\n                        max-width: 100%;\n                        margin: 0 auto;\n                        opacity: 0.07;\n                        filter: grayscale(100%);\n                    }\n                    .max-w-md {\n                        max-width: none;\n                        width: 100%;\n                    }\n                    .text-center {\n                        text-align: center;\n                    }\n                    .border-b {\n                        border-bottom: 1px solid #e5e7eb;\n                    }\n                    .border-t {\n                        border-top: 1px solid #e5e7eb;\n                    }\n                    .border-gray-200 {\n                        border-color: #e5e7eb;\n                    }\n                    .pb-4 {\n                        padding-bottom: 1rem;\n                    }\n                    .pt-4 {\n                        padding-top: 1rem;\n                    }\n                    .mb-4 {\n                        margin-bottom: 1rem;\n                    }\n                    .mb-2 {\n                        margin-bottom: 0.5rem;\n                    }\n                    .mb-3 {\n                        margin-bottom: 0.75rem;\n                    }\n                    .text-2xl {\n                        font-size: 1.5rem;\n                    }\n                    .text-sm {\n                        font-size: 0.875rem;\n                    }\n                    .text-xs {\n                        font-size: 0.75rem;\n                    }\n                    .font-bold {\n                        font-weight: 700;\n                    }\n                    .font-semibold {\n                        font-weight: 600;\n                    }\n                    .font-medium {\n                        font-weight: 500;\n                    }\n                    .text-gray-900 {\n                        color: #111827;\n                    }\n                    .text-gray-600 {\n                        color: #4b5563;\n                    }\n                    .text-gray-500 {\n                        color: #6b7280;\n                    }\n                    .text-green-600 {\n                        color: #059669;\n                    }\n                    .text-orange-600 {\n                        color: #ea580c;\n                    }\n                    .flex {\n                        display: flex;\n                    }\n                    .justify-between {\n                        justify-content: space-between;\n                    }\n                    .items-start {\n                        align-items: flex-start;\n                    }\n                    .space-y-2 > * + * {\n                        margin-top: 0.5rem;\n                    }\n                    .space-y-3 > * + * {\n                        margin-top: 0.75rem;\n                    }\n                    .flex-1 {\n                        flex: 1 1 0%;\n                    }\n                    @media print {\n                        body {\n                            margin: 0;\n                            padding: 0;\n                            -webkit-print-color-adjust: exact;\n                            print-color-adjust: exact;\n                        }\n                        @page {\n                            margin: 0.5in;\n                            size: A4;\n                        }\n                    }\n                </style>\n            </head>\n            <body>\n                <div class=\"print-sheet\">\n                    ").concat(watermarkHTML, "\n                    <div class=\"print-sheet__content\">\n                        ").concat(receiptHTML, "\n                    </div>\n                </div>\n            </body>\n            </html>\n        ");

      // Write the HTML to the print window
      printWindow.document.write(printHTML);
      printWindow.document.close();

      // Wait for content to load, then print
      printWindow.onload = function () {
        var triggerPrint = function triggerPrint() {
          printWindow.focus();
          printWindow.print();
          printWindow.close();
        };
        var watermarkImage = printWindow.document.querySelector('.receipt-watermark img');
        if (watermarkImage && !watermarkImage.complete) {
          var completeAndPrint = function completeAndPrint() {
            return setTimeout(triggerPrint, 150);
          };
          watermarkImage.addEventListener('load', completeAndPrint, {
            once: true
          });
          watermarkImage.addEventListener('error', completeAndPrint, {
            once: true
          });
          return;
        }
        setTimeout(triggerPrint, 150);
      };
    }
  }, {
    key: "notify",
    value: function notify(message) {
      var type = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'info';
      if (window.toastr && typeof window.toastr[type] === 'function') {
        window.toastr[type](message);
        return;
      }
      if (window.Swal) {
        window.Swal.fire({
          toast: true,
          position: 'top-end',
          icon: type,
          title: message,
          showConfirmButton: false,
          timer: 2400,
          timerProgressBar: true
        });
        return;
      }
      var notification = document.createElement('div');
      notification.textContent = message;
      notification.setAttribute('role', 'status');
      notification.style.cssText = ['position:fixed', 'right:1rem', 'top:1rem', 'z-index:9999', 'max-width:22rem', 'padding:0.85rem 1rem', 'border-radius:0.75rem', 'background:#0f172a', 'color:#fff', 'box-shadow:0 16px 36px rgba(15,23,42,0.22)', 'font:600 0.875rem system-ui,sans-serif'].join(';');
      document.body.appendChild(notification);
      window.setTimeout(function () {
        return notification.remove();
      }, 2800);
    }
  }]);
}(); // Make it globally available
window.printReceipt = PrintReceipt.print;
/******/ })()
;