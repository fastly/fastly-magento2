define([
    "jquery"
], function ($) {
    return function resetAllMessages()
    {
        /**
         * Reset all Fastly Messages
         *
         * @type {*|jQuery|HTMLElement}
         */
        let msgWarning = $('.fastly-message-warning');
        let msgError = $('.fastly-message-error');
        let msgSuccess = $('.fastly-message-success');
        let buttonMessages = $('.fastly-button-messages');

        // Modal window warning messages
        msgWarning.text();
        msgWarning.hide();

        // Modal windows error messages
        msgError.text();
        msgError.hide();

        // Modal windows success messages
        msgSuccess.text();
        msgSuccess.hide();

        // Button messages
        buttonMessages.text();
        buttonMessages.hide();
    }
});