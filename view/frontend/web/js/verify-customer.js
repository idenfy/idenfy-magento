define([
    'uiComponent',
    'mage/storage',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/translate'
], function (Component, storage, messageList, fullScreenLoader, $t) {
    'use strict';

    return Component.extend({

        defaults: {
            template: 'Idenfy_CustomerVerification/verify-customer'
        },

        /**
         * Trigger a redirect to the Idenfy platform to preform customer verification
         */
        idenfyRedirect: function () {

            fullScreenLoader.startLoader();

            storage.post(
                'rest/default/V1/idenfy/get-redirect-url'
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
                    console.log('ALWAYS');
                    fullScreenLoader.stopLoader();
                }
            ).then(
                function () {
                    console.log('THEN');
                }
            );
        }
    });
});
