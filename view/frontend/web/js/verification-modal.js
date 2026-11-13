/**
 * Verification modal on the checkout
 *
 * Shows the Idenfy verification in an embedded iFrame (or starts the redirect flow), listens for the result the
 * iFrame posts and then waits for the status confirmed by the webhook on the server. What the browser reports is
 * only used to decide what to show: orders are unlocked by the server alone.
 *
 * The modal opens again whenever an unverified customer tries to place the order: through the checkout's
 * additional validators (Place Order and most payment buttons) and when the server rejects an order.
 *
 * @see https://documentation.idenfy.com/kyc/iframe-redirect
 */
define([
    'jquery',
    'mage/translate',
    'Idenfy_CustomerVerification/js/model/verification',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/modal/modal'
], function ($, $t, verification, additionalValidators) {
    'use strict';

    var STATUS_NOT_REQUIRED = 'not_required',
        STATUS_VERIFIED = 'verified',
        STATUS_REVIEWING = 'reviewing',
        STATUS_FAILED = 'failed',

        /**
         * Statuses after which there is nothing left to wait for
         */
        SETTLED_STATUSES = [STATUS_NOT_REQUIRED, STATUS_VERIFIED, STATUS_REVIEWING, STATUS_FAILED];

    /**
     * @param {Object} config - see VerificationModal::getJsConfig()
     * @param {HTMLElement} element
     */
    return function (config, element) {
        var options = $.extend({
                mode: 'iframe',
                autoOpen: true,
                closable: false,
                uiOrigin: 'https://ui.idenfy.com',
                statusUrl: '',
                startUrl: '',
                returnParam: 'idenfy_return',
                orderRejectedMessage: '',
                buttonStyle: {},
                pollInterval: 3000,
                // Webhooks normally arrive within seconds of the result
                pollAttempts: 20,
                shortPollAttempts: 5,
                returnPollAttempts: 10,
                verifiedCloseDelay: 1500
            }, config),
            $root = $(element),
            $frame = $root.find('[data-role="frame"]'),
            $error = $root.find('[data-role="error"]'),
            $notice = $root.find('[data-role="notice"]'),
            $retryMessage = $root.find('[data-role="retry-message"]'),
            iframe = null,
            pollTimer = null,
            resultHint = null,
            isOpen = false,
            isBusy = false;

        /**
         * Apply the custom button style as CSS custom properties (allowed by a strict Content Security Policy)
         */
        function applyButtonStyle() {
            var style = document.documentElement.style;

            $.each(options.buttonStyle || {}, function (name, value) {
                style.setProperty(name, value);
            });
        }

        /**
         * @param {String} message
         */
        function showError(message) {
            $error.text(message).prop('hidden', false);
        }

        /**
         * @param {String} [message]
         */
        function showNotice(message) {
            $notice.text(message || '').prop('hidden', !message);
        }

        /**
         * @param {String} state - intro, frame, checking, verified, reviewing or retry
         */
        function setState(state) {
            $root.find('[data-state]').each(function () {
                this.hidden = this.getAttribute('data-state') !== state;
            });
            $root.attr('data-current-state', state).toggleClass('_frame', state === 'frame');
            $error.text('').prop('hidden', true);
            showNotice('');
        }

        /**
         * @param {String} message
         */
        function showRetry(message) {
            setState('retry');
            $retryMessage.text(message);
        }

        /**
         * @param {Boolean} busy
         */
        function setBusy(busy) {
            isBusy = busy;
            $root.find('[data-action="start"], [data-action="restart"]')
                .prop('disabled', busy)
                .toggleClass('_busy', busy);
        }

        function open() {
            if (!isOpen) {
                isOpen = true;
                $root.modal('openModal');
            }
        }

        function close() {
            if (isOpen) {
                $root.modal('closeModal');
            }
        }

        function stopPolling() {
            clearTimeout(pollTimer);
            pollTimer = null;
        }

        function removeIframe() {
            if (iframe) {
                $(iframe).remove();
                iframe = null;
            }
        }

        /**
         * Call one of the module's REST endpoints (absolute URLs, so not through mage/url)
         *
         * @param {String} url
         * @param {Object} [data]
         * @returns {jQuery.jqXHR}
         */
        function post(url, data) {
            return $.ajax({
                url: url,
                type: 'POST',
                data: data ? JSON.stringify(data) : undefined,
                contentType: 'application/json',
                dataType: 'json',
                global: false
            });
        }

        /**
         * @returns {jQuery.Promise} resolved with the status, or null when it could not be retrieved
         */
        function fetchStatus() {
            var deferred = $.Deferred();

            post(options.statusUrl)
                .done(function (status) {
                    verification.status(status);
                    deferred.resolve(status);
                })
                .fail(function () {
                    deferred.resolve(null);
                });

            return deferred.promise();
        }

        /**
         * Check the status until it is settled or the attempts run out
         *
         * @param {Number} attempts
         * @returns {jQuery.Promise} resolved with the last status
         */
        function pollStatus(attempts) {
            var deferred = $.Deferred();

            stopPolling();

            (function poll(remaining) {
                fetchStatus().done(function (status) {
                    if (!isOpen) {
                        return;
                    }

                    if (SETTLED_STATUSES.indexOf(status) !== -1 || remaining <= 1) {
                        deferred.resolve(status);

                        return;
                    }

                    pollTimer = setTimeout(function () {
                        poll(remaining - 1);
                    }, options.pollInterval);
                });
            })(attempts);

            return deferred.promise();
        }

        /**
         * Show the success state, then close the modal so the customer can place the order
         */
        function completeVerification() {
            stopPolling();
            removeIframe();
            setState('verified');

            setTimeout(close, options.verifiedCloseDelay);
        }

        /**
         * Show the state that belongs to a verification status
         *
         * @param {String|null} status
         */
        function showStatus(status) {
            switch (status) {
                case STATUS_VERIFIED:
                    completeVerification();
                    break;

                case STATUS_NOT_REQUIRED:
                    close();
                    break;

                case STATUS_REVIEWING:
                    setState('reviewing');
                    break;

                case STATUS_FAILED:
                    showRetry($t('We could not verify your identity. Please try again.'));
                    break;

                default:
                    setState('intro');
            }
        }

        /**
         * Show the outcome once polling has finished
         *
         * @param {String|null} status
         */
        function resolveResult(status) {
            if (SETTLED_STATUSES.indexOf(status) !== -1) {
                showStatus(status);

                return;
            }

            // The webhook has not arrived yet: fall back on what the verification UI reported
            switch (resultHint) {
                case 'approved':
                    setState('reviewing');
                    break;

                case 'failed':
                    showRetry($t('We could not verify your identity. Please try again.'));
                    break;

                case 'unverified':
                    showRetry($t('Your verification was not completed. Please try again.'));
                    break;

                default:
                    setState('intro');
                    // Single literal, so the phrase can be collected for translation
                    showNotice($t('We have not received the result of your verification yet. If you did not finish it, you can continue where you left off.')); // eslint-disable-line max-len
            }
        }

        /**
         * @param {Number} attempts
         */
        function waitForResult(attempts) {
            setState('checking');
            pollStatus(attempts).done(resolveResult);
        }

        /**
         * @param {String} url
         */
        function showFrame(url) {
            removeIframe();
            resultHint = null;

            iframe = document.createElement('iframe');
            iframe.className = 'idenfy-verification__iframe';
            // Camera for document and face capture, fullscreen for the liveness check
            iframe.setAttribute('allow', 'camera; fullscreen; clipboard-write');
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('title', $t('Identity verification'));
            iframe.src = url;

            $frame.append(iframe);
            setState('frame');
        }

        /**
         * @param {Object} response
         * @returns {String}
         */
        function getErrorMessage(response) {
            var json = response && response.responseJSON;

            return json && json.message ?
                json.message :
                $t('Something went wrong with your request. Please try again later.');
        }

        /**
         * @returns {String} the current page without the return marker
         */
        function getCurrentUrl() {
            var url = new URL(window.location.href);

            url.searchParams.delete(options.returnParam);

            return url.toString();
        }

        /**
         * @returns {Boolean}
         */
        function isReturningFromRedirect() {
            return new URL(window.location.href).searchParams.has(options.returnParam);
        }

        /**
         * Start or resume the verification
         *
         * @param {Boolean} restart - start a new session instead of resuming the unfinished one
         */
        function start(restart) {
            var payload = {
                restart: !!restart
            };

            if (isBusy) {
                return;
            }

            if (options.mode === 'redirect') {
                payload.returnUrl = getCurrentUrl();
            }

            setBusy(true);

            post(options.startUrl, payload)
                .done(function (url) {
                    if (options.mode === 'redirect') {
                        // Stay busy while the browser navigates away
                        window.location.assign(url);

                        return;
                    }

                    setBusy(false);
                    showFrame(url);
                })
                .fail(function (response) {
                    var message = getErrorMessage(response);

                    // The verification may have moved on meanwhile, e.g. it was completed in another tab
                    fetchStatus().done(function (status) {
                        setBusy(false);

                        if (status === STATUS_VERIFIED || status === STATUS_REVIEWING) {
                            showStatus(status);

                            return;
                        }

                        showError(message);
                    });
                });
        }

        /**
         * Handle the result posted by the verification UI
         *
         * @param {MessageEvent} event
         */
        function onMessage(event) {
            var data = event.data;

            if (!iframe || event.source !== iframe.contentWindow || event.origin !== options.uiOrigin) {
                return;
            }

            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    return;
                }
            }

            if (!data || typeof data !== 'object' || !data.status) {
                return;
            }

            resultHint = String(data.status).toLowerCase();
            removeIframe();
            waitForResult(resultHint === 'approved' ? options.pollAttempts : options.shortPollAttempts);
        }

        /**
         * Open the modal in the state that belongs to the current status
         */
        function openForCurrentStatus() {
            open();
            showStatus(verification.status());
        }

        /**
         * Additional validator of Place Order and the payment buttons that run the checkout's validators
         *
         * @param {Boolean} hideError - silent check, e.g. to enable or disable a wallet button
         * @returns {Boolean}
         */
        function validateBeforePlaceOrder(hideError) {
            if (!verification.requiresVerification()) {
                return true;
            }

            // Keep wallet buttons enabled: the click on them is validated again and opens the modal then
            if (hideError) {
                return true;
            }

            openForCurrentStatus();

            return false;
        }

        /**
         * Reopen the modal when the server rejected an order because the customer is not verified, whichever
         * checkout or payment button submitted it
         *
         * @param {jQuery.Event} event
         * @param {jQuery.jqXHR} xhr
         */
        function onAjaxError(event, xhr) {
            var json = xhr && xhr.responseJSON;

            if (!json && xhr && xhr.responseText) {
                try {
                    json = JSON.parse(xhr.responseText);
                } catch (e) {
                    return;
                }
            }

            if (!json || !options.orderRejectedMessage || json.message !== options.orderRejectedMessage) {
                return;
            }

            fetchStatus().done(function (status) {
                if (verification.requiresVerification()) {
                    open();
                    showStatus(status);
                }
            });
        }

        /**
         * Decide what to show once the status is known
         *
         * @param {String|null} status
         */
        function boot(status) {
            if (!status) {
                return;
            }

            verification.status(status);

            if (isReturningFromRedirect()) {
                window.history.replaceState(window.history.state, '', getCurrentUrl());

                if (verification.requiresVerification()) {
                    open();
                    waitForResult(options.returnPollAttempts);
                }

                return;
            }

            if (options.autoOpen && verification.requiresVerification()) {
                open();
                showStatus(status);
            }
        }

        applyButtonStyle();

        $root.modal({
            type: 'popup',
            title: '',
            modalClass: 'idenfy-verification-modal' + (options.closable ? '' : ' _idenfy-locked'),
            responsive: true,
            innerScroll: true,
            clickableOverlay: options.closable,
            buttons: [],
            focus: '',
            keyEventHandlers: options.closable ? {} : {
                /**
                 * The modal guards the checkout and cannot be dismissed
                 */
                escapeKey: function () {}
            },
            closed: function () {
                isOpen = false;
                stopPolling();
                removeIframe();
            }
        });

        $root.on('click', '[data-action="start"]', function () {
            start(false);
        });
        $root.on('click', '[data-action="restart"]', function () {
            start(true);
        });
        $root.on('click', '[data-action="cancel"]', function () {
            removeIframe();
            setState('intro');
        });
        $root.on('click', '[data-action="check"]', function () {
            waitForResult(1);
        });

        window.addEventListener('message', onMessage, false);

        verification.setOpener(openForCurrentStatus);
        additionalValidators.registerValidator({
            validate: validateBeforePlaceOrder
        });
        $(document).on('ajaxError', onAjaxError);

        if (window.checkoutConfig && window.checkoutConfig.idenfy && window.checkoutConfig.idenfy.status) {
            boot(window.checkoutConfig.idenfy.status);
        } else {
            fetchStatus().done(boot);
        }
    };
});
