define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {
        /* Web Application Firewall state elements */
        let wafStateSpan = $('#waf_state_span');
        let wafStateMsgSpan = $('#fastly_waf_state_message_span');
        let wafStateEnabled = wafStateMsgSpan.find('#waf_state_enabled');
        let wafStateDisabled = wafStateMsgSpan.find('#waf_state_disabled');
        let wafStateBlocking = wafStateMsgSpan.find('#waf_state_blocking');
        let wafStateLogging = wafStateMsgSpan.find('#waf_state_logging');

        /* Web Application Firewall button messages */
        let successWafBtnMsg = $('#fastly-success-waf-button-msg');
        let errorWafBtnMsg = $('#fastly-error-waf-button-msg');
        let warningWafBtnMsg = $('#fastly-warning-waf-button-msg');

        /* OWASP rows */
        let owaspRestrictedExtensionsRow = $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_owasp_restricted_extensions');
        let owaspAllowedMethodsRow = $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_owasp_allowed_methods');

        /* OWASP fields */
        let owaspRestrictedExtensionsField = $('#system_full_page_cache_fastly_fastly_web_application_firewall_owasp_restricted_extensions');
        let owaspAllowedMethodsField = $('#system_full_page_cache_fastly_fastly_web_application_firewall_owasp_allowed_methods');

        owaspRestrictedExtensionsField.prop('disabled', true);
        owaspAllowedMethodsField.prop('disabled', true);

        /**
         * Trigger the WAF status call
         */
        getServiceInfo().done(function (response) {
            if (response.status !== false) {
                let service_info = response.service_info;
                let active_version_info = service_info.active_version;
                let wafs = active_version_info.wafs;
                if (wafs !== undefined) {
                    warningWafBtnMsg.hide();
                    $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_waf_bypass').show();
                    $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_update_waf_bypass').show();
                    $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_waf_allow_by_acl').show();
                    requirejs(['wafBypass'], function (wafBypass) {
                        wafBypass(config, serviceStatus, isAlreadyConfigured);
                    });
                    $.each(wafs, function (waf, info) {
                        getWafSettings(info.id, true).done(function (wafResponse) {
                            wafStateSpan.find('.processing').hide();
                            let wafSettings = wafResponse.waf_settings;
                            let wafData = wafSettings.data;
                            let wafAttributes = wafData.attributes;
                            if (wafAttributes.disabled) {
                                wafStateDisabled.show();
                            } else {
                                if (wafAttributes.rule_statuses_block_count > 0) {
                                    wafStateBlocking.show();
                                } else if (wafAttributes.rule_statuses_log_count > 0) {
                                    wafStateLogging.show();
                                } else {
                                    wafStateEnabled.show();
                                }
                            }

                            const includedData = wafResponse.waf_settings.included;
                            if (!includedData) {
                                return;
                            }

                            const latestFirewallVersionIncluded = includedData.find(function (included) {
                                return included.type === "waf_firewall_version" && included.attributes.active;
                            });

                            if (!latestFirewallVersionIncluded) {
                                return;
                            }

                            let firewallRulesData = latestFirewallVersionIncluded.attributes;
                            let firewallAllowedMethods = firewallRulesData.allowed_methods;
                            let firewallRestrictedExtensions = firewallRulesData.restricted_extensions;

                            owaspAllowedMethodsRow.show();
                            owaspAllowedMethodsField.val(firewallAllowedMethods);
                            owaspRestrictedExtensionsRow.show();
                            owaspRestrictedExtensionsField.val(firewallRestrictedExtensions);
                        });
                    });
                } else {
                    wafStateSpan.find('.processing').hide();
                    wafStateDisabled.show();
                    warningWafBtnMsg.text(
                        $.mage.__(
                            'Please contact your sales rep or send an email to support@fastly.com to request Web Application Firewall activation for your Fastly service.'
                        )
                    ).show();
                    owaspAllowedMethodsRow.show();
                    owaspRestrictedExtensionsRow.show();
                }
            }
        });

        /**
         * Retrieve advanced information about the Fastly service
         *
         * @returns {*}
         */
        function getServiceInfo()
        {
            return $.ajax({
                type: "GET",
                url: config.getFastlyServiceInfoUrl
            });
        }

        /**
         * Retrieve WAF settings
         *
         * @param id
         * @param includeWafFirewallVersion
         * @returns {*}
         */
        function getWafSettings(id, includeWafFirewallVersion = false)
        {
            let queryParams = {
                "id": id
            };

            if (includeWafFirewallVersion) {
                queryParams.include_waf_firewall_versions = true;
            }

            return $.ajax({
                type: "POST",
                url: config.getWafSettingsUrl,
                data: queryParams
            });
        }
    }
});
