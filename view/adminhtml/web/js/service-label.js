define([
    "jquery"
], function ($) {
    return function setActiveServiceLabel(active_version, next_version, service_name)
    {
        /**
         * Set the Active service label message
         *
         * @type {*|jQuery|HTMLElement}
         */
        let msgWarning = $('.fastly-message-warning');
        msgWarning.text($.mage.__('You are about to clone service **' + service_name + '** active version') + ' ' + active_version + '. '
            + $.mage.__('We\'ll make changes to version ') + ' ' + next_version + '.');
        msgWarning.show();
    }
});