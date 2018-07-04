define([
    "jquery"
], function ($) {
    return function resetAllMessages() {
        var msgWarning = $('.fastly-message-warning');
        var msgError = $('.fastly-message-error');
        var msgSuccess = $('.fastly-message-success');

        // Modal window warning messages
        msgWarning.text();
        msgWarning.hide();

        // Modal windows error messages
        msgError.text();
        msgError.hide();

        // Modal windows success messages
        msgSuccess.text();
        msgSuccess.hide();
    }
});