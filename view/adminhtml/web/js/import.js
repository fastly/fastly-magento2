define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {
        let successButtonMsg = $("#fastly-success-import-button-msg");
        let errorButtonMsg = $("#fastly-error-import-button-msg");
        let warningButtonMsg = $("#fastly-warning-import-button-msg");

        let importOptions = {
            id: 'fastly-import-options',
            title: jQuery.mage.__('Import ACLs'),
            content: function () {
                return document.getElementById('fastly-export-template').textContent;
            },
            actionOk: function () {
                fastlyExport(active_version);
            }
        };

        $('#fastly_import').on('click', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {
                if (service.status === false) {
                    return errorButtonMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
            });
        });
    }
});