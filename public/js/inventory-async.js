(function () {
    const AUTO_SYNC_INTERVAL_MS = 5000;
    let autoSyncTimer = null;
    let autoSyncInProgress = false;
    let lastAutoSyncAt = 0;

    function getSwal() {
        return window.Swal || null;
    }

    function getToastr() {
        return window.toastr || null;
    }

    async function confirmAction(message) {
        const Swal = getSwal();

        if (!Swal) {
            return window.confirm(message);
        }

        const result = await Swal.fire({
            title: 'Please confirm',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Continue',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0D2B45',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            focusCancel: true,
        });

        return result.isConfirmed;
    }

    function findFlashMessage(doc) {
        const types = ['success', 'error', 'warning', 'info'];

        for (const type of types) {
            const meta = doc.querySelector(`meta[name="flash-${type}"]`);
            const message = meta?.getAttribute('content')?.trim();

            if (message) {
                return { type, message };
            }
        }

        return null;
    }

    function showToast(type, message) {
        if (!message) {
            return;
        }

        const toastr = getToastr();
        if (toastr) {
            const method = typeof toastr[type] === 'function' ? type : 'info';
            toastr[method](message);
            return;
        }

        const Swal = getSwal();
        if (Swal) {
            const icons = {
                success: 'success',
                error: 'error',
                warning: 'warning',
                info: 'info',
            };

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icons[type] || 'info',
                title: message,
                showConfirmButton: false,
                timer: 3200,
                timerProgressBar: true,
            });
            return;
        }

        const colors = {
            success: 'border-green-200 bg-green-50 text-green-800',
            error: 'border-red-200 bg-red-50 text-red-800',
            warning: 'border-yellow-200 bg-yellow-50 text-yellow-800',
            info: 'border-blue-200 bg-blue-50 text-blue-800',
        };

        const toast = document.createElement('div');
        toast.className = `fixed right-4 top-4 z-[80] max-w-sm rounded-xl border px-4 py-3 shadow-lg transition-all duration-300 ${colors[type] || colors.info}`;
        toast.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-1 text-sm font-medium">${message}</div>
                <button type="button" class="text-current/70 hover:text-current" aria-label="Close notification">&times;</button>
            </div>
        `;

        const closeButton = toast.querySelector('button');
        const removeToast = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            window.setTimeout(() => toast.remove(), 220);
        };

        closeButton?.addEventListener('click', removeToast);

        document.body.appendChild(toast);

        window.setTimeout(removeToast, 3200);
    }

    function executeInlineScripts(container) {
        container.querySelectorAll('script').forEach((script) => {
            if (script.type && script.type !== 'text/javascript' && script.type !== 'application/javascript') {
                return;
            }

            const replacement = document.createElement('script');
            Array.from(script.attributes).forEach((attribute) => {
                replacement.setAttribute(attribute.name, attribute.value);
            });
            replacement.textContent = script.textContent;
            script.replaceWith(replacement);
        });
    }

    function setInventoryLoading(isLoading) {
        const container = document.querySelector('[data-inventory-tab-content]');

        if (!container) {
            return;
        }

        container.classList.toggle('opacity-50', isLoading);
        container.classList.toggle('pointer-events-none', isLoading);
        container.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    function isFishBoxesScreen() {
        const currentUrl = new URL(window.location.href);

        return currentUrl.pathname.includes('/broker/inventory')
            && (currentUrl.searchParams.get('tab') || 'fishBoxes') === 'fishBoxes'
            && Boolean(document.querySelector('[data-fish-box-summary], [data-fish-box-card]'));
    }

    function hasOpenInventoryOverlay() {
        return Boolean(
            document.querySelector([
                '.workspace-modal-host [role="dialog"]:not(.hidden)',
                '[data-app-modal-root][role="dialog"]:not([style*="display: none"])',
                '#fish-box-history-modal:not(.hidden)',
                '#fish-type-edit-modal:not(.hidden)',
                '#fish-price-history-modal:not(.hidden)',
                '#fish-price-edit-modal:not(.hidden)',
                '#qrScannerModal:not(.hidden)',
            ].join(', '))
        );
    }

    function isUserEditingField() {
        const activeElement = document.activeElement;

        if (!activeElement) {
            return false;
        }

        return activeElement.matches('input, textarea, select, [contenteditable="true"]');
    }

    function syncInventoryChrome(doc) {
        const currentTabs = document.querySelector('[data-inventory-tabs]');
        const newTabs = doc.querySelector('[data-inventory-tabs]');

        if (currentTabs && newTabs) {
            currentTabs.innerHTML = newTabs.innerHTML;
        }
    }

    function removeDetachedInventoryModals() {
        [
            'fish-box-history-modal',
            'fish-type-edit-modal',
            'fish-price-history-modal',
            'fish-price-edit-modal',
        ].forEach((id) => {
            document.querySelectorAll(`body > #${id}`).forEach((modal) => modal.remove());
        });

        document.documentElement.classList.remove('modal-scroll-lock');
        document.body.classList.remove('modal-scroll-lock');
    }

    async function refreshInventoryTabFromResponse(response, historyMode = 'replace') {
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const currentContainer = document.querySelector('[data-inventory-tab-content]');
        const newContainer = doc.querySelector('[data-inventory-tab-content]');

        if (!currentContainer || !newContainer) {
            window.location.href = response.url || window.location.href;
            return;
        }

        syncInventoryChrome(doc);
        removeDetachedInventoryModals();
        currentContainer.innerHTML = newContainer.innerHTML;
        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(currentContainer);
        }
        executeInlineScripts(currentContainer);

        if (response.url) {
            const method = historyMode === 'push' ? 'pushState' : 'replaceState';
            window.history[method]({}, '', response.url);
        }

        const flash = findFlashMessage(doc);
        if (flash) {
            showToast(flash.type, flash.message);
        }
    }

    async function loadInventoryTab(url, historyMode = 'push', options = {}) {
        const shouldFallbackToLocation = options.fallbackToLocation !== false;

        if (!options.silent) {
            setInventoryLoading(true);
        }

        try {
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                },
            });

            if (!response.ok) {
                throw new Error('Request failed.');
            }

            await refreshInventoryTabFromResponse(response, historyMode);
        } catch (error) {
            if (shouldFallbackToLocation) {
                window.location.href = url;
            }
        } finally {
            if (!options.silent) {
                setInventoryLoading(false);
            }
        }
    }

    async function refreshFishBoxesQuietly() {
        if (
            autoSyncInProgress
            || document.hidden
            || !isFishBoxesScreen()
            || hasOpenInventoryOverlay()
            || isUserEditingField()
        ) {
            return;
        }

        autoSyncInProgress = true;

        try {
            await loadInventoryTab(window.location.href, 'replace', {
                fallbackToLocation: false,
                silent: true,
            });
            lastAutoSyncAt = Date.now();
        } catch (error) {
            // The normal navigation fallback inside loadInventoryTab handles hard failures.
        } finally {
            autoSyncInProgress = false;
        }
    }

    function startFishBoxAutoSync() {
        if (autoSyncTimer) {
            clearInterval(autoSyncTimer);
        }

        autoSyncTimer = window.setInterval(refreshFishBoxesQuietly, AUTO_SYNC_INTERVAL_MS);
    }

    window.InventoryAsync = {
        refreshCurrentTab() {
            return loadInventoryTab(window.location.href, 'replace');
        },
        refreshFromUrl(url, historyMode = 'replace') {
            return loadInventoryTab(url, historyMode);
        },
        refreshFishBoxesQuietly,
    };

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[data-inventory-tab-link]');

        if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        loadInventoryTab(url.toString(), 'push');
    });

    window.addEventListener('popstate', function () {
        const currentUrl = new URL(window.location.href);

        if (!currentUrl.pathname.includes('/broker/inventory')) {
            return;
        }

        loadInventoryTab(currentUrl.toString(), 'replace');
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && Date.now() - lastAutoSyncAt > 1000) {
            refreshFishBoxesQuietly();
        }
    });

    window.addEventListener('focus', function () {
        if (Date.now() - lastAutoSyncAt > 1000) {
            refreshFishBoxesQuietly();
        }
    });

    startFishBoxAutoSync();

    document.addEventListener('submit', async function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches('form[data-inventory-async]')) {
            return;
        }

        event.preventDefault();

        const confirmMessage = form.dataset.confirmMessage || 'Are you sure you want to continue?';
        const isConfirmed = await confirmAction(confirmMessage);

        if (!isConfirmed) {
            return;
        }

        const submitButton = event.submitter || form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                },
            });

            if (!response.ok) {
                throw new Error('Request failed.');
            }

            await refreshInventoryTabFromResponse(response);
        } catch (error) {
            showToast('error', 'The action could not be completed right now. Please try again.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
})();
