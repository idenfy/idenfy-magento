/**
 * Keeps a native colour picker and the hex colour text field next to it in sync
 */
define([
    'jquery'
], function ($) {
    'use strict';

    /**
     * @param {String} value
     * @returns {String|null} the colour as #rrggbb, as required by input[type=color]
     */
    function toLongHex(value) {
        var hex = $.trim(value || '');

        if (/^#[0-9a-f]{3}$/i.test(hex)) {
            hex = '#' + hex.charAt(1) + hex.charAt(1) + hex.charAt(2) + hex.charAt(2) + hex.charAt(3) + hex.charAt(3);
        }

        return /^#[0-9a-f]{6}$/i.test(hex) ? hex.toLowerCase() : null;
    }

    return function (config, element) {
        var $swatch = $(element),
            $input = $(config.target);

        function syncSwatch() {
            var hex = toLongHex($input.val());

            if (hex) {
                $swatch.val(hex);
            }
        }

        syncSwatch();
        $input.on('input change', syncSwatch);
        $swatch.on('input change', function () {
            $input.val($swatch.val().toUpperCase()).trigger('change');
        });
    };
});
