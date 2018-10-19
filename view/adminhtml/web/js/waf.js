define([
    "jquery",
    "setServiceLabel",
    "popup",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, popup, resetAllMessages, showErrorMessage) {
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
                    $.each(wafs, function (waf, info) {
                        getWafSettings(info.id).done(function (wafResponse) {
                            wafStateSpan.find('.processing').hide();
                            let wafSettings = wafResponse.waf_settings;
                            let wafData = wafSettings.data;
                            let wafAttributes = wafData.attributes;
                            if (wafAttributes.rule_statuses_block_count > 0) {
                                wafStateBlocking.show();
                            } else if (wafAttributes.rule_statuses_log_count > 0) {
                                wafStateLogging.show();
                            } else {
                                wafStateEnabled.show();
                            }
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
         * @returns {*}
         */
        function getWafSettings(id)
        {
            return $.ajax({
                type: "POST",
                url: config.getWafSettingsUrl,
                data: {
                    'id': id
                }
            });
        }
    }
});