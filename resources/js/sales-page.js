/**
 * Sales page modal and async form workflow
 * Keeps modal navigation and form submissions inside the current page.
 */

(function () {
    const MODAL_QUERY_KEYS = ['modal', 'edit', 'show', 'sale', 'print'];

    function getSalesRoot() {
        return document.querySelector('[data-sales-page]');
    }

    function isSalesPageActive() {
        return Boolean(getSalesRoot());
    }

    function toAbsoluteUrl(url) {
        return new URL(url, window.location.origin);
    }

    function getCleanSalesUrl(url = window.location.href) {
        const parsedUrl = toAbsoluteUrl(url);
        MODAL_QUERY_KEYS.forEach((key) => parsedUrl.searchParams.delete(key));
        return `${parsedUrl.pathname}${parsedUrl.search}${parsedUrl.hash}`;
    }

    function getSalesBaseUrl() {
        return getSalesRoot()?.dataset.salesBaseUrl || getCleanSalesUrl();
    }

    function parseHtml(html) {
        return new DOMParser().parseFromString(html, 'text/html');
    }

    function clearTeleportedModals() {
        document.querySelectorAll('[data-app-modal-root]').forEach((modal) => modal.remove());
        document.documentElement.classList.remove('modal-scroll-lock');
        document.body.classList.remove('modal-scroll-lock');
    }

    function pushHistory(url) {
        window.history.pushState({ salesPage: true }, '', url);
    }

    function replaceHistory(url) {
        window.history.replaceState({ salesPage: true }, '', url);
    }

    function reinitializeSalesFragment(fragment) {
        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(fragment);
        }

        if (typeof window.initializeBrokerSalesPage === 'function') {
            window.initializeBrokerSalesPage(fragment);
        }
    }

    async function refreshSalesFragment(url, historyMode = 'replace') {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Unable to refresh sales view (${response.status}).`);
        }

        const html = await response.text();
        const parsedDocument = parseHtml(html);
        const incomingFragment = parsedDocument.querySelector('#sales-page-fragment');
        const currentFragment = document.querySelector('#sales-page-fragment');

        if (!incomingFragment || !currentFragment) {
            throw new Error('Sales page fragment could not be resolved.');
        }

        clearTeleportedModals();
        currentFragment.replaceWith(incomingFragment);

        if (historyMode === 'push') {
            pushHistory(url);
        } else if (historyMode === 'replace') {
            replaceHistory(url);
        }

        reinitializeSalesFragment(incomingFragment);
    }

    function escapeSelectorValue(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return value.replace(/["\\]/g, '\\$&');
    }

    function dotKeyToBracketName(key) {
        return key.split('.').reduce((result, segment, index) => {
            if (index === 0) {
                return segment;
            }

            return `${result}[${segment}]`;
        }, '');
    }

    function clearFormErrors(form) {
        form.querySelectorAll('.sales-form-error').forEach((errorNode) => errorNode.remove());
        form.querySelectorAll('.sales-form-error-field').forEach((field) => {
            field.classList.remove('sales-form-error-field', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        });
    }

    function ensureErrorSummary(form) {
        let summary = form.querySelector('[data-sales-form-errors]');

        if (!summary) {
            summary = document.createElement('div');
            summary.setAttribute('data-sales-form-errors', 'true');
            summary.className = 'sales-form-error mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
            form.prepend(summary);
        }

        return summary;
    }

    function renderFormErrors(form, errors) {
        const collectedMessages = [];

        Object.entries(errors || {}).forEach(([key, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;

            if (!message) {
                return;
            }

            collectedMessages.push(message);

            const fieldName = dotKeyToBracketName(key);
            const selector = `[name="${escapeSelectorValue(fieldName)}"]`;
            const fallbackSelector = `[name="${escapeSelectorValue(key)}"]`;
            const field = form.querySelector(selector) || form.querySelector(fallbackSelector);

            if (!field) {
                return;
            }

            field.classList.add('sales-form-error-field', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500');

            const messageNode = document.createElement('p');
            messageNode.className = 'sales-form-error mt-1 text-sm text-red-600';
            messageNode.textContent = message;

            field.insertAdjacentElement('afterend', messageNode);
        });

        if (collectedMessages.length > 0) {
            const summary = ensureErrorSummary(form);
            summary.textContent = collectedMessages[0];
        }
    }

    function setFormSubmitting(form, isSubmitting) {
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((submitControl) => {
            submitControl.disabled = isSubmitting;
        });
    }

    async function parseJsonResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return response.json();
        }

        return {
            message: response.ok ? 'Saved successfully.' : 'Request failed.',
        };
    }

    async function handleAsyncFormSubmit(form) {
        clearFormErrors(form);
        setFormSubmitting(form, true);

        try {
            const response = await fetch(form.action, {
                method: (form.getAttribute('method') || 'POST').toUpperCase(),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: new FormData(form),
            });

            const payload = await parseJsonResponse(response);

            if (response.status === 422) {
                renderFormErrors(form, payload.errors || {});
                if (window.toastr) {
                    window.toastr.error(payload.message || 'Please review the highlighted fields.');
                }
                return;
            }

            if (!response.ok) {
                throw new Error(payload.message || 'Unable to save the sales record.');
            }

            if (window.toastr) {
                window.toastr.success(payload.message || 'Sales data saved successfully.');
            }

            await refreshSalesFragment(getSalesBaseUrl(), 'replace');
        } catch (error) {
            if (window.toastr) {
                window.toastr.error(error.message || 'Something went wrong while saving the form.');
            }
        } finally {
            setFormSubmitting(form, false);
        }
    }

    function closeCurrentModal(closeUrl) {
        const modalRoot = document.querySelector('[data-app-modal-root]');
        const closeButton = modalRoot?.querySelector('[data-app-modal-close]');

        if (closeButton) {
            closeButton.click();
            return;
        }

        clearTeleportedModals();
        replaceHistory(closeUrl || getSalesBaseUrl());
    }

    document.addEventListener('click', (event) => {
        if (!isSalesPageActive()) {
            return;
        }

        const modalLink = event.target.closest('[data-sales-modal-link]');
        if (modalLink) {
            event.preventDefault();
            refreshSalesFragment(modalLink.href, 'push').catch((error) => {
                if (window.toastr) {
                    window.toastr.error(error.message || 'Unable to open the requested form.');
                }
            });
            return;
        }

        const modalCloseTrigger = event.target.closest('[data-sales-modal-close]');
        if (modalCloseTrigger) {
            event.preventDefault();
            closeCurrentModal(modalCloseTrigger.dataset.closeUrl || getSalesBaseUrl());
        }
    });

    document.addEventListener('submit', (event) => {
        if (!isSalesPageActive()) {
            return;
        }

        const asyncForm = event.target.closest('form[data-sales-async-form]');
        if (!asyncForm) {
            return;
        }

        event.preventDefault();
        handleAsyncFormSubmit(asyncForm);
    });

    window.addEventListener('popstate', () => {
        if (!isSalesPageActive()) {
            return;
        }

        refreshSalesFragment(window.location.href, 'silent').catch(() => {
            // Keep popstate failures quiet to avoid trapping navigation.
        });
    });
})();
