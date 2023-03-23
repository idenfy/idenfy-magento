define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/storage',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/translate'
], function ($, Component, ko, storage, messageList, urlBuilder, fullScreenLoader, $t) {
    'use strict';

    return Component.extend({

        defaults: {
            template: 'Idenfy_CustomerVerification/verify-customer',
            shouldRender: ko.observable()
        },

        /**
         * Initialze the component
         */
        initialize: function() {
            this._super();

            this.shouldRender.subscribe(function(shouldRender) {
                if (!shouldRender) {
                    const hideIdenfyContainer = setInterval(function () {
                        if ($('#idenfy-verification').length > 0) {
                            $('#idenfy-verification').hide();
                            clearInterval(hideIdenfyContainer);
                        }
                    }, 200)
                }
            });

            this.shouldVerify();
        },
        /**
         * Check if the customer should still verify
         */
        shouldVerify: function () {
            let self = this,
                status = undefined,
                serviceUrl = urlBuilder.createUrl('/idenfy/needs-verification', {});

            fullScreenLoader.startLoader();
            storage.post(
                serviceUrl
            ).fail(
                function (response) {
                    self.shouldRender(false);
                }
            ).done(
                function (response) {
                    self.shouldRender(response);
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                }
            );
        },

        /**
         * Trigger a redirect to the Idenfy platform to preform customer verification
         */
        idenfyRedirect: function () {

            let serviceUrl = urlBuilder.createUrl('/idenfy/get-redirect-url', {});
            fullScreenLoader.startLoader();

            storage.post(
                serviceUrl
            ).fail(
                function (response) {
                    messageList.addErrorMessage(
                        $t('Something went wrong with your request. Please try again later.')
                    );
                }
            ).done(
                function (response) {
                    window.location.replace(response);
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                }
            );
        }
    });
});
