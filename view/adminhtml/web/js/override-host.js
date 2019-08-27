define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, showSuccessMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let active_version = serviceStatus.active_version;
        let errorOverrideHostBtnMsg = $("#fastly-error-override-host-button-msg");
        let successOverrideHostBtnMsg = $("#fastly-success-override-host-button-msg");

        /**
         * modal for configuring override host
         * @type {{actionOk: actionOk, title: *, content: (function(): string)}}
         */
        let overrideHostOptions = {
            title: jQuery.mage.__('Override Host'),
            content: function () {
                return document.getElementById('fastly-override-host-template').textContent;
            },
            actionOk: function () {
                overrideHost(active_version);
            }
        };

        /**
         * After submitting the input field for override host
         * @param activeVersion
         */
        function overrideHost(activeVersion)
        {
            resetAllMessages();

            let host_name = $("#host_name").val();
            let active = $("#fastly_activate_vcl").is(':checked') ? true : false;

            $.ajax({
                type: 'GET',
                url: config.overrideHost,
                showLoader: true,
                data: {
                    'active_version': activeVersion,
                    'host_name': host_name,
                    'active': active
                },
                success: function (response) {
                    modal.modal('closeModal');
                    if (response.status !== false) {
                        let msg = 'Successfully configured default host "' + response.override_host + '"';
                        if (response.activated !== false) {
                            active_version = response.version;
                            msg += ', and activated version #' + response.version;
                            successOverrideHostBtnMsg.text($.mage.__(msg)).show();
                            return;
                        }
                        successOverrideHostBtnMsg.text($.mage.__(msg)).show();
                        return;
                    }
                    return errorOverrideHostBtnMsg.text($.mage.__(response.msg)).show();
                }
            });
        }

        /**
         * after click on Override Host's Configure button
         */
        $("#override-host-container-button").on('click', function () {
            resetAllMessages();
            $.ajax({
                type: 'GET',
                url: config.serviceInfoUrl,
                showLoader: true,
                data: {'active_version': active_version},
                success: function (response) {
                    overlay(overrideHostOptions);
                    setServiceLabel(response.active_version, response.next_version, response.service.name);
                }
            });
        });

        $("#fastly_override_host_button").on('click', function () {
            console.log("dadada");
        });
    }
});