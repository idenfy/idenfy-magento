/**
 * Verification state of the checkout. Other components (e.g. of a one-step checkout extension) can use it to read the
 * status or to open the verification modal with open().
 */
define([
    'ko'
], function (ko) {
    'use strict';

    /**
     * Statuses that keep the customer from placing an order (see GetVerificationStatus::BLOCKING_STATUSES)
     */
    var BLOCKING_STATUSES = ['not_started', 'pending', 'reviewing', 'failed'],
        status = ko.observable(null),
        opener = null;

    return {
        /**
         * Verification status as confirmed by the server
         */
        status: status,

        /**
         * @returns {Boolean}
         */
        requiresVerification: function () {
            return BLOCKING_STATUSES.indexOf(status()) !== -1;
        },

        /**
         * @param {Function} callback - opens the verification modal
         */
        setOpener: function (callback) {
            opener = callback;
        },

        /**
         * Open the verification modal
         */
        open: function () {
            if (opener) {
                opener();
            }
        }
    };
});
