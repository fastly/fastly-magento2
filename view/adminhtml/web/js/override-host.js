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

        /* Override Host messages */
        let errorOverrideHostBtnMsg = $("#fastly-error-override-host-button-msg");
        let successOverrideHostBtnMsg = $("#fastly-success-override-host-button-msg");
        let warningOverrideHostBtnMsg = $("#fastly-warning-override-host-button-msg");

        /* Modal window message warning */
        let warningMsgModal = $(".fastly-message-warning");


        overrideHostStateSpan.find('.processing').show();
        checkOverrideHostStatus(active_version);

        let overrideHostOptionsDisable = {
            title: jQuery.mage.__('Override Host Disable'),
            content: function () {
                return document.getElementById('fastly-disable-override-host-template').textContent;
            },
            actionOk: function () {
                changeOverrideHost(active_version, defaultTtl, false);
            }
        };

        let overrideHostOptionsEnable = {
            title: jQuery.mage.__('Override Host Enable'),
            content: function () {
                return document.getElementById('fastly-override-host-template').textContent;
            },
            actionOk: function () {
                changeOverrideHost(active_version, defaultTtl, true);
            }
        };

        function checkOverrideHostStatus(activeVersion)
        {
            $.ajax({
               type: 'GET',
               url: config.overrideHostStatus,
               data: { 'active_version' : activeVersion },
               showLoader: true,
               success: function (response) {
                   let overrideHostStateEnabled = $("#override_host_enabled");
                   let overrideHostStateDisabled = $("#override_host_disabled");
                   let overrideHostStateUnknown = $("#override_host_unknown");

                   if (response.status !== false) {
                       overrideHost = response.general_default_host;
                       defaultTtl = response.general_default_ttl;
                       overrideHostStateSpan.find('.processing').hide();
                        if (response.override_host_switcher !== 'disabled') {
                            console.log(overrideHostStateSpan.find('.disabled').hide());
                            overrideHostStateEnabled.show();
                            return;
                        }
                       console.log(overrideHostStateSpan.find('.enabled').hide());
                       overrideHostStateDisabled.show();
                   }
               }
            });
        }

        /**
         * After submitting the input field for override host
         * @param activeVersion
         * @param enable
         * @param defaultTtlValue
         */
        function changeOverrideHost(activeVersion, defaultTtlValue, enable)
        {
            let activate = $("#fastly_activate_vcl").is(':checked') ? true : false;
            let overrideHostInput = (enable === true) ? $("#host_name").val() : '';

            $.ajax({
                type: 'GET',
                url: config.overrideHostSwitcher,
                showLoader: true,
                data: {
                    'active_version': activeVersion,
                    'override_host': overrideHostInput,
                    'default_ttl' : defaultTtlValue,
                    'activate': activate,
                    'enable': enable
                },
                success: function (response) {
                    resetAllMessages();
                    modal.modal('closeModal');
                    if (response.status !== false) {
                        active_version = response.version;
                        next_version = response.next_version;
                        overrideHost = response.override_host;
                        checkOverrideHostStatus(active_version);
                        return;
                    }
                    return errorOverrideHostBtnMsg.text($.mage.__(response.msg));
                }
            });
        }

        /**
         * after click on Override Host's Configure button
         */
        $("#override_host_container_button").on('click', function () {
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

        $("#fastly_override_switcher_button").on('click', function () {
            resetAllMessages();
            if (overrideHost !== '') {
                overlay(overrideHostOptionsDisable);
                setServiceLabel(active_version, next_version, service_name);
                return;
            }

            overlay(overrideHostOptionsEnable);
            setServiceLabel(active_version, next_version, service_name);
        });
    }
});