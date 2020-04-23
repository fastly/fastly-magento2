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
        msgWarning.text($.mage.__('You are about to clone service **%1** active version %2.').replace('%1', service_name).replace('%2', active_version) + ' '
            + $.mage.__('We\'ll make changes to version %1.').replace('%1', next_version));
        msgWarning.show();
    }
});
