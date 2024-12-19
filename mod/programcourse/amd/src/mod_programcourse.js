/**
 * Mod Program course JS.
 */
define([
    'jquery',
    'selectpicker',
], function ($) {
    return {
        /**
         * Mod Program course JS init.
         *
         * @return void
         */
        init: function () {
            $('#id_courseid').removeClass('custom-select').selectpicker({liveSearch: true});
        }
    };
});
