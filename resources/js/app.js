/**
 * First we will load all of this project's JavaScript dependencies which
 * includes libraries needed for the POS system functionality.
 */

require('./bootstrap');

// Third-party libraries via npm
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
import Swal from 'sweetalert2';
import Alpine from 'alpinejs';

// Make SweetAlert and Toastr available globally
window.Swal = Swal;
window.toastr = toastr;

window.Alpine = Alpine;
Alpine.start();

const moneyTextPattern = /^₱\s*-?[\d,]+(?:\.\d{1,2})?$/;
let moneyAlignmentTimer = null;

function alignMoneyValues(root = document) {
    const elements = root.querySelectorAll
        ? root.querySelectorAll('td, th, span, p, div, strong, output')
        : [];

    elements.forEach((element) => {
        if (element.children.length > 0) {
            return;
        }

        if (moneyTextPattern.test(element.textContent.trim())) {
            element.dataset.money = 'true';
        }
    });
}

function scheduleMoneyAlignment() {
    clearTimeout(moneyAlignmentTimer);
    moneyAlignmentTimer = setTimeout(() => alignMoneyValues(), 75);
}

window.alignMoneyValues = alignMoneyValues;



/**
 * POS System JavaScript
 * Uses local Alpine.js for reactive components and vanilla JS for custom functionality.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Configure Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3500,
        escapeHtml: true
    };

    // Show flash messages from meta tags
    const getMeta = (name) => {
        const el = document.querySelector(`meta[name="${name}"]`);
        return el && el.getAttribute('content') ? el.getAttribute('content') : '';
    };

    const flashSuccess = getMeta('flash-success');
    const flashError = getMeta('flash-error');
    const flashWarning = getMeta('flash-warning');
    const flashInfo = getMeta('flash-info');

    if (flashSuccess) toastr.success(flashSuccess);
    if (flashError) toastr.error(flashError);
    if (flashWarning) toastr.warning(flashWarning);
    if (flashInfo) toastr.info(flashInfo);

    // Initialize SweetAlert form handlers
    initializeSweetAlert();
    initializeProfileDropdowns();
    alignMoneyValues();

    const observer = new MutationObserver(scheduleMoneyAlignment);
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true
    });

});

function initializeProfileDropdowns() {
    const dropdowns = document.querySelectorAll('[data-profile-menu]');

    if (!dropdowns.length) {
        return;
    }

    const closeDropdown = (dropdown) => {
        const button = dropdown.querySelector('[data-profile-menu-button]');
        const panel = dropdown.querySelector('[data-profile-menu-panel]');
        const icon = dropdown.querySelector('[data-profile-menu-icon]');

        if (!button || !panel) {
            return;
        }

        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        icon?.classList.remove('rotate-180');
    };

    const closeOtherDropdowns = (currentDropdown) => {
        dropdowns.forEach((dropdown) => {
            if (dropdown !== currentDropdown) {
                closeDropdown(dropdown);
            }
        });
    };

    dropdowns.forEach((dropdown) => {
        const button = dropdown.querySelector('[data-profile-menu-button]');
        const panel = dropdown.querySelector('[data-profile-menu-panel]');
        const icon = dropdown.querySelector('[data-profile-menu-icon]');

        if (!button || !panel) {
            return;
        }

        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const isOpen = !panel.hidden;
            closeOtherDropdowns(dropdown);

            panel.hidden = isOpen;
            button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            icon?.classList.toggle('rotate-180', !isOpen);
        });
    });

    document.addEventListener('click', (event) => {
        dropdowns.forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                closeDropdown(dropdown);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            dropdowns.forEach(closeDropdown);
        }
    });
}


/**
 * Initialize SweetAlert form handlers
 */
function initializeSweetAlert() {
    if (document.documentElement.dataset.swalDelegatedBound === 'true') {
        return;
    }

    document.documentElement.dataset.swalDelegatedBound = 'true';

    document.addEventListener('submit', function (e) {
        const form = e.target;

        if (!(form instanceof HTMLFormElement) || !form.matches('form[data-swal]')) {
            return;
        }

        if (form.dataset.swalConfirmed === 'true') {
            delete form.dataset.swalConfirmed;
            return;
        }

        e.preventDefault();

        const action = form.getAttribute('data-swal');
        const recordName = form.getAttribute('data-record-name') || '';
        const customTitle = form.getAttribute('data-swal-title');
        const customText = form.getAttribute('data-swal-text');
        const customConfirmText = form.getAttribute('data-swal-confirm');
        const customCancelText = form.getAttribute('data-swal-cancel');
        const customIcon = form.getAttribute('data-swal-icon');

        let title = 'Are you sure?';
        let text = '';
        let confirmText = 'Yes';
        let icon = 'warning';

        const recordType = getRecordTypeFromForm(form);

        switch (action) {
            case 'delete':
                title = `Delete ${recordType}?`;
                text = `This will permanently delete the ${recordType.toLowerCase()}${recordName ? ` "${recordName}"` : ''} and cannot be undone.`;
                confirmText = `Yes, delete ${recordType.toLowerCase()}`;
                break;
            case 'deactivate':
                title = `Deactivate ${recordType}?`;
                text = `This will deactivate the ${recordType.toLowerCase()}${recordName ? ` "${recordName}"` : ''}.`;
                confirmText = `Yes, deactivate`;
                break;
            case 'activate':
                title = `Activate ${recordType}?`;
                text = `This will activate the ${recordType.toLowerCase()}${recordName ? ` "${recordName}"` : ''}.`;
                confirmText = `Yes, activate`;
                break;
            case 'winner':
                title = `Confirm ${recordName || 'this applicant'} as winner?`;
                text = `Are you sure ${recordName || 'this applicant'} is the winner? This will activate the broker account automatically.`;
                confirmText = `Yes, confirm winner`;
                icon = 'question';
                break;
            case 'mark-missing':
                title = `Mark Fish Box as Missing?`;
                text = `Are you sure you want to mark this fish box${recordName ? ` "${recordName}"` : ''} as missing? This action cannot be undone.`;
                confirmText = `Yes, Mark as Missing`;
                icon = 'warning';
                break;
            case 'return-to-stock':
                title = `Return Fish Boxes to In Stock?`;
                text = `This will change all "Returned" fish boxes back to "In Stock" status. Are you sure?`;
                confirmText = `Yes, Return to Stock`;
                icon = 'question';
                break;
            default:
                title = 'Are you sure?';
                text = 'This action cannot be undone.';
                confirmText = 'Yes, continue';
        }

        title = customTitle || title;
        text = customText || text;
        confirmText = customConfirmText || confirmText;
        icon = customIcon || icon;

        Swal.close();
        setTimeout(() => {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: (action === 'delete' || action === 'mark-missing') ? '#dc2626' : '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: confirmText,
                cancelButtonText: customCancelText || 'Cancel',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.swalConfirmed = 'true';
                    form.requestSubmit();
                }
            });
        }, 10);
    });
}

/**
 * Get record type from form action URL
 */
function getRecordTypeFromForm(form) {
    const action = form.getAttribute('action') || '';

    if (action.includes('sales') && action.includes('payments')) return 'Sale Payment';
    if (action.includes('sales')) return 'Sale';
    if (action.includes('fish-boxes') || action.includes('fish-box')) return 'Fish Box';
    if (action.includes('fish-type')) return 'Fish';
    if (action.includes('users')) return 'User';
    if (action.includes('brokers')) return 'Broker';
    if (action.includes('admins')) return 'Admin';

    return 'Record';
}

