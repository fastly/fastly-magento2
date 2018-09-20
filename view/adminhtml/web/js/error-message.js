define([
    "jquery"
], function ($) {
    return function showErrorMessage(msg)
    {
        /**
         * Show error message
         *
         * @type {*|jQuery|HTMLElement}
         */
        let msgError = $('.fastly-message-error');
        msgError.html($.mage.__(msg));
        msgError.show();
        msgError.focus();
    }
});