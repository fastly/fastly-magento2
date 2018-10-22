define([
    "jquery"
], function ($) {
    return function showSuccessMessage(msg)
    {
        /**
         * Show warning message
         *
         * @type {*|jQuery|HTMLElement}
         */
        let msgSuccess = $('.fastly-message-warning');
        msgSuccess.html($.mage.__(msg));
        msgSuccess.show();
        msgSuccess.focus();
    }
});