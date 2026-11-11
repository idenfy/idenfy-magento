define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/storage',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/get-payment-information',
    'mage/translate'
], function ($, Component, ko, storage, messageList, urlBuilder, fullScreenLoader, getPaymentInformation, $t) {
    'use strict';

    /**
     * Query parameter Idenfy redirects back with (see GetAuthToken::RETURN_QUERY_PARAM)
     */
    var RETURN_QUERY_PARAM = 'idenfy_return';

    return Component.extend({

        defaults: {
            template: 'Idenfy_CustomerVerification/verify-customer',
            // How often and how long to re-check the status after returning from Idenfy,
            // as the webhook with the result can arrive a few seconds after the redirect
            returnPollInterval: 3000,
            returnPollAttempts: 10
        },

        /**
         * Initialize the component
         */
        initialize: function () {
            this._super();

            this.shouldRender = ko.observable(false);
            this.isCheckingStatus = ko.observable(false);

            var idenfyConfig = window.checkoutConfig ? window.checkoutConfig.idenfy : null;

            // The module is disabled or the API credentials are missing: nothing to do
            if (!idenfyConfig || !idenfyConfig.isEnabled) {
                return this;
            }

            this.shouldVerify(this.isReturningFromIdenfy() ? this.returnPollAttempts : 0);

            return this;
        },

        /**
         * @returns {Boolean}
         */
        isReturningFromIdenfy: function () {
            return new RegExp('[?&]' + RETURN_QUERY_PARAM + '=').test(window.location.search);
        },

        /**
         * Check if the customer should still verify
         *
         * @param {Number} remainingAttempts - re-checks left while waiting for the webhook
         */
        shouldVerify: function (remainingAttempts) {
            var self = this,
                serviceUrl = urlBuilder.createUrl('/idenfy/needs-verification', {}),
                isFirstCheck = !this.isCheckingStatus();

            if (isFirstCheck) {
                fullScreenLoader.startLoader();
            }

            storage.post(
                serviceUrl
            ).fail(
                function () {
                    self.isCheckingStatus(false);
                    self.shouldRender(false);
                }
            ).done(
                function (needsVerification) {
                    if (needsVerification && remainingAttempts > 0) {
                        self.isCheckingStatus(true);
                        setTimeout(function () {
                            self.shouldVerify(remainingAttempts - 1);
                        }, self.returnPollInterval);

                        return;
                    }

                    if (!needsVerification && self.isCheckingStatus()) {
                        // Verified while on the page: reload the payment methods that were disabled
                        getPaymentInformation();
                    }

                    self.isCheckingStatus(false);
                    self.shouldRender(!!needsVerification);
                }
            ).always(
                function () {
                    if (isFirstCheck) {
                        fullScreenLoader.stopLoader();
                    }
                }
            );
        },

        /**
         * Trigger a redirect to the Idenfy platform to perform customer verification
         */
        idenfyRedirect: function () {
            var serviceUrl = urlBuilder.createUrl('/idenfy/get-redirect-url', {});

            fullScreenLoader.startLoader();

            storage.post(
                serviceUrl
            ).fail(
                function (response) {
                    var message = response && response.responseJSON && response.responseJSON.message
                        ? response.responseJSON.message
                        : $t('Something went wrong with your request. Please try again later.');

                    messageList.addErrorMessage({message: message});
                    fullScreenLoader.stopLoader();
                }
            ).done(
                function (redirectUrl) {
                    // Keep the loader running while the browser navigates away
                    window.location.replace(redirectUrl);
                }
            );
        }
    });
});
