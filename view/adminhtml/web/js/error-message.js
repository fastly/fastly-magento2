define([
    "jquery"
], function ($) {
    return function showErrorMessage(msg) {
        let msgError = $('.fastly-message-error');
        msgError.html($.mage.__(msg));
        msgError.show();
        msgError.focus();
    }
});