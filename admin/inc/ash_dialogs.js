/**
 * Kalmoy POS — SweetAlert2 dialogs (replaces native alert/confirm and bootstrap confirm modal).
 */
(function (window) {
    'use strict';

    function swalReady(cb) {
        if (typeof Swal !== 'undefined') {
            cb();
            return;
        }
        setTimeout(function () { swalReady(cb); }, 30);
    }

    window.ashAlert = function (message, icon, title, callback) {
        icon = icon || 'info';
        if (!title) {
            if (icon === 'error') title = 'Error';
            else if (icon === 'success') title = 'Success';
            else if (icon === 'warning') title = 'Warning';
            else title = 'Notice';
        }
        swalReady(function () {
            Swal.fire({
                icon: icon,
                title: title,
                text: String(message),
                confirmButtonText: 'OK'
            }).then(function () {
                if (typeof callback === 'function') callback();
            });
        });
    };

    window.ashConfirm = function (message, onConfirm, options) {
        options = options || {};
        swalReady(function () {
            Swal.fire({
                icon: options.icon || 'warning',
                title: options.title || 'Confirmation',
                text: String(message),
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Yes',
                cancelButtonText: options.cancelText || 'Cancel',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed && typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
        });
    };

    window.ashAccessDenied = function (redirectUrl) {
        ashAlert('You do not have permission to access this area.', 'error', 'Access Denied', function () {
            if (redirectUrl) location.replace(redirectUrl);
        });
    };

    window.ashDebtProfileNotFound = function (createUrl, closeUrl) {
        swalReady(function () {
            Swal.fire({
                icon: 'warning',
                title: 'Debt Profile Not Found',
                text: 'The selected customer does not have a debt profile.',
                showCancelButton: true,
                confirmButtonText: 'Create Debt Profile',
                cancelButtonText: 'Close',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed && createUrl) {
                    location.replace(createUrl);
                } else if (closeUrl) {
                    location.replace(closeUrl);
                }
            });
        });
    };

    window.ashSwalRun = function (options) {
        options = options || {};
        if (options.custom === 'debt_profile_not_found') {
            ashDebtProfileNotFound(options.createUrl || '', options.closeUrl || '');
            return;
        }
        swalReady(function () {
            var opts = {
                icon: options.icon || 'info',
                title: options.title || 'Notice',
                text: options.text || '',
                confirmButtonText: options.confirmButtonText || 'OK'
            };
            if (options.showCancelButton) {
                opts.showCancelButton = true;
                opts.cancelButtonText = options.cancelButtonText || 'Cancel';
                opts.confirmButtonText = options.confirmButtonText || 'Yes';
            }
            Swal.fire(opts).then(function (result) {
                if (options.redirect && (!options.showCancelButton || result.isConfirmed)) {
                    location.replace(options.redirect);
                }
            });
        });
    };

    window._conf = function ($msg, $func, $params) {
        $params = $params || [];
        ashConfirm($msg, function () {
            try {
                (new Function($func + '(' + $params.join(',') + ')'))();
            } catch (e) {
                console.error(e);
            }
        }, { icon: 'warning', title: 'Confirmation' });
    };

    window.alert = function (message) {
        window.ashAlert(String(message), 'info', 'Notice');
    };

})(window);
