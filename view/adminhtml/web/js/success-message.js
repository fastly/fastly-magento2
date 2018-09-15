define([
    "jquery"
], function ($) {
    return function showSuccessMessage(msg)
    {
        let msgSuccess = $('.fastly-message-success');
        msgSuccess.html($.mage.__(msg));
        msgSuccess.show();
        msgSuccess.focus();
    }
});