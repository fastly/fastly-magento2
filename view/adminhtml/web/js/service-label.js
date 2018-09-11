define([
    "jquery"
], function ($) {

    function showSuccessMessage(msg) {
        let msgSuccess = $('.fastly-message-success');
        msgSuccess.html($.mage.__(msg));
        msgSuccess.show();
        msgSuccess.focus();
    }

    return function setActiveServiceLabel(active_version, next_version, service_name) {
        let msgWarning = $('.fastly-message-warning');
        msgWarning.text($.mage.__('You are about to clone service **' + service_name + '** active version') + ' ' + active_version + '. '
            + $.mage.__('We\'ll make changes to version ') + ' ' + next_version + '.');
        msgWarning.show();
    }
});