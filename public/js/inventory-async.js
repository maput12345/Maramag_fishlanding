(function () {
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

    async function refreshInventoryTabFromResponse(response) {
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const currentContainer = document.querySelector('[data-inventory-tab-content]');
        const newContainer = doc.querySelector('[data-inventory-tab-content]');

        if (!currentContainer || !newContainer) {
            window.location.href = response.url || window.location.href;
            return;
        }

        currentContainer.innerHTML = newContainer.innerHTML;

        if (response.url) {
            window.history.replaceState({}, '', response.url);
        }

        const flash = findFlashMessage(doc);
        if (flash) {
            showToast(flash.type, flash.message);
        }
    }

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
