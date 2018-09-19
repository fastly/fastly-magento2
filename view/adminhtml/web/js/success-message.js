define([
    "jquery"
], function ($) {
    return function showSuccessMessage(msg)
    {
        /**
         * Show success message
         *
         * @type {*|jQuery|HTMLElement}
         */
        let msgSuccess = $('.fastly-message-success');
        msgSuccess.html($.mage.__(msg));
        msgSuccess.show();
        msgSuccess.focus();
    }
});