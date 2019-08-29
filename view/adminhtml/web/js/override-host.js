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
        let next_version = serviceStatus.next_version;
        let service_name = serviceStatus.service.name;

        /* State Elements */
        let overrideHostStateSpan = $("#override_host_switch_span");
        let overrideHostMsgSpan = $("#fastly_override_host_state_message_span");
        let overrideHost;
        let defaultTtl;
        let overrideHostStatus;

        /* Override Host messages */
        let errorOverrideHostBtnMsg = $("#fastly-error-override-host-button-msg");
        let successOverrideHostBtnMsg = $("#fastly-success-override-host-button-msg");
        let warningOverrideHostBtnMsg = $("#fastly-warning-override-host-button-msg");

        overrideHostStateSpan.find('.processing').show();
        checkOverrideHostStatus(active_version);

        let overrideHostOptionsDisable = {
            title: jQuery.mage.__('Override Host Disable'),
            content: function () {
                return document.getElementById('fastly-disable-override-host-template').textContent;
            },
            actionOk: function () {
                changeOverrideHost(active_version, defaultTtl, overrideHostStatus);
            }
        };

        let overrideHostOptionsEnable = {
            title: jQuery.mage.__('Override Host Enable'),
            content: function () {
                return document.getElementById('fastly-override-host-template').textContent;
            },
            actionOk: function () {
                changeOverrideHost(active_version, defaultTtl, overrideHostStatus);
            }
        };

        /**
         * Checks current default_host and default_ttl of the service.
         * Displays override host status as "enabled" or "disabled".
         * @param activeVersion
         */
        function checkOverrideHostStatus(activeVersion)
        {
            $.ajax({
                type: 'GET',
                url: config.overrideHostStatus,
                data: {'active_version': activeVersion},
                showLoader: true,
                success: function (response) {
                    let overrideHostStateEnabled = $("#override_host_enabled");
                    let overrideHostStateDisabled = $("#override_host_disabled");
                    if (response.status !== false) {
                        overrideHost = response.general_default_host;
                        defaultTtl = response.general_default_ttl;
                        overrideHostStateSpan.find('.processing').hide();
                        overrideHostStatus = response.override_host_status;
                        if (!overrideHostStatus) {
                            overrideHostStateSpan.find('.enabled').hide();
                            overrideHostStateDisabled.show();
                            return;
                        }

                        overrideHostStateSpan.find('.disabled').hide();
                        overrideHostStateEnabled.show();
                        return;
                    }

                    errorOverrideHostBtnMsg.text($.mage.__(response.msg)).show();
                }
            });
        }

        /**
         * After submitting the input field for the override host.
         * @param activeVersion
         * @param status
         * @param defaultTtlValue
         */
        function changeOverrideHost(activeVersion, defaultTtlValue, status)
        {
            let activate = $("#fastly_activate_vcl").is(':checked') ? true : false;
            let overrideHostInput = !status ? $("#host_name").val() : '';

            $.ajax({
                type: 'GET',
                url: config.overrideHostSwitcher,
                showLoader: true,
                data: {
                    'active_version': activeVersion,
                    'override_host': overrideHostInput,
                    'default_ttl': defaultTtlValue,
                    'activate': activate,
                    'status': status
                },
                success: function (response) {
                    resetAllMessages();
                    modal.modal('closeModal');
                    if (response.status !== false) {
                        active_version = response.version;
                        next_version = response.next_version;
                        overrideHost = response.override_host;
                        checkOverrideHostStatus(active_version);
                        return successOverrideHostBtnMsg.text($.mage.__('Successfully updated ' +
                            'the Override Host for the version #' + response.edited_version)).show();
                    }
                    return errorOverrideHostBtnMsg.text($.mage.__(response.msg)).show();
                }
            });
        }

        /**
         * Enable/Disable button pressed
         */
        $("#fastly_override_switcher_button").on('click', function () {
            resetAllMessages();
            if (!overrideHostStatus) {
                overlay(overrideHostOptionsEnable);
                setServiceLabel(active_version, next_version, service_name);
                return;
            }

            overlay(overrideHostOptionsDisable);
            setServiceLabel(active_version, next_version, service_name);
        });
    }
});