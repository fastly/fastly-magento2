define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    "Magento_Ui/js/modal/confirm",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, showSuccessMessage, confirm) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let active_version = serviceStatus.active_version;

        let versionBtnErrorMsg = $("#fastly-error-versions-button-msg");

        /**
         * Trigger ACL container list
         */
        listVersions(active_version, false).done(function (response) {
            console.log(response);
            $('.loading-versions').hide();
            if (response.status !== false) {
                    if (response.versions.length > 0) {
                        versions = response.versions;
                        processAcls(response.versions);
                    } else {
                        $('.no-versions').show();
                    }
            }
        }).fail(function () {
            return versionBtnErrorMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
        });

        /**
         * Queries Fastly API to retrieve the list of Fastly versions
         *
         * @param active_version
         * @param loaderVisibility
         * @returns array
         */
        function listVersions(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "POST",
                url: config.versionHistory,
                showLoader: loaderVisibility,
                data: {'active_version': active_version},
                beforeSend: function () {
                    $('.loading-versions').show();
                }
            });
        }
    }
});