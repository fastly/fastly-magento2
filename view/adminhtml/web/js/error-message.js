define([
    "jquery"
], function ($) {
    return function showErrorMessage(msg) {
        var msgError = $('.fastly-message-error');
        msgError.html($.mage.__(msg));
        msgError.show();
        msgError.focus();
    }
});