define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "Magento_Ui/js/modal/prompt",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* WAF bypass state elements*/
        let wafBypassStateSpan = $('#waf_bypass_state_span');
        let wafBypassStateMsgSpan = $('#fastly_waf_bypass_state_message_span');
        let wafBypass = true;
        /* WAF bypass button messages */
        let successWafBypassBtnMsg = $('#fastly-success-waf-bypass-button-msg');
        let errorWafBypassBtnMsg = $('#fastly-error-waf-bypass-button-msg');
        /* Update WAF bypass button messages */
        let wafBypassSuccessBtnMsg = $('#fastly-update-waf-bypass-success-button-msg');
        let wafBypassErrorBtnMsg = $('#fastly-update-waf-bypass-error-button-msg');

        let active_version = serviceStatus.active_version;

        wafBypassStateSpan.find('.processing').show();

        /**
         * WAF bypass modal overlay options
         *
         * @type {{id: string, title: *, content: (function(): string), actionOk: actionOk}}
         */
        let wafBypassOptions = {
            id: 'fastly-waf-bypass-options',
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-waf-bypass-template').textContent;
            },
            actionOk: function () {
                toggleWafBypass(active_version);
            }
        };

        /**
         * Trigger the WAF bypass status call
         */
        getWafBypassSetting(active_version, false).done(function (response) {
            wafBypassStateSpan.find('.processing').hide();
            let wafBypassStateEnabled = wafBypassStateMsgSpan.find('#waf_bypass_state_enabled');
            let wafBypassStateDisabled = wafBypassStateMsgSpan.find('#waf_bypass_state_disabled');

            if (response.status === true) {
                if (wafBypassStateDisabled.is(":hidden")) {
                    wafBypassStateEnabled.show();
                }
            } else if (response.status === false) {
                if (wafBypassStateEnabled.is(":hidden")) {
                    wafBypassStateDisabled.show();
                }
            } else {
                wafBypassStateMsgSpan.find('#waf_bypass_state_unknown').show();
            }
        }).fail(function () {
            wafBypassStateSpan.find('.processing').hide();
            wafBypassStateMsgSpan.find('#waf_bypass_state_unknown').show();
        });

        /**
         * Queries Fastly API to retrieve WAF bypass setting
         *
         * @param active_version
         * @param loaderVisibility
         * @returns {*}
         */
        function getWafBypassSetting(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "POST",
                url: config.checkWafBypassSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        /**
         * Toggle waf_bypass button on click event
         */
        $('#fastly_waf_bypass_button').on('click', function () {
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
                    return errorWafBypassBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                getWafBypassSetting(active_version, true).done(function (response) {
                    overlay(wafBypassOptions);
                    setServiceLabel(active_version, next_version, service_name);
                    let upload_button = $('.upload-button span');

                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('You are about to enable WAF Bypass'));
                        upload_button.text('Enable');
                    } else {
                        $('.modal-title').text($.mage.__('You are about to disable WAF Bypass'));
                        upload_button.text('Disable');
                    }
                    wafBypass = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

            }).fail(function () {
                return errorWafBypassBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Update WAF bypass button on click event
         */
        $('#fastly_update_bypass_button').on('click', function () {
            resetAllMessages();

            $.ajax({
                type: "POST",
                url: config.updateWafBypassUrl,
                showLoader: true,
                data: {
                    'service_id': $('#system_full_page_cache_fastly_fastly_service_id').val(),
                    'api_key': $('#system_full_page_cache_fastly_fastly_api_key').val(),
                    'acls': $('#system_full_page_cache_fastly_fastly_web_application_firewall_waf_allow_by_acl').serializeArray(),
                },
                cache: false,
                success: function (response) {
                    if (response.status === false) {
                        return wafBypassErrorBtnMsg.text($.mage.__('Please make sure that WAF Bypass is enabled.')).show();
                    } else {
                        return wafBypassSuccessBtnMsg.text($.mage.__('WAF Bypass snippet has been updated successfully.')).show();
                    }
                },
                error: function () {
                    return wafBypassErrorBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        });

        /**
         * Toggle WAF bypass VCL snippet
         *
         * @param active_version
         */
        function toggleWafBypass(active_version)
        {
            let activate_waf_bypass_flag = false;

            if ($('#fastly_activate_waf_bypass').is(':checked')) {
                activate_waf_bypass_flag = true;
            }

            $.ajax({
                type: "POST",
                url: config.toggleWafBypassSettingUrl,
                data: {
                    'activate_flag': activate_waf_bypass_flag,
                    'active_version': active_version
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        let disabledOrEnabled = 'disabled';

                        if (wafBypass === false) {
                            disabledOrEnabled = 'enabled';
                        } else {
                            disabledOrEnabled = 'disabled';
                        }

                        successWafBypassBtnMsg.text($.mage.__('WAF Bypass is successfully ' + disabledOrEnabled + '.')).show();

                        if (disabledOrEnabled === 'enabled') {
                            wafBypassStateMsgSpan.find('#waf_bypass_state_disabled').hide();
                            wafBypassStateMsgSpan.find('#waf_bypass_state_enabled').show();
                        } else {
                            wafBypassStateMsgSpan.find('#waf_bypass_state_enabled').hide();
                            wafBypassStateMsgSpan.find('#waf_bypass_state_disabled').show();
                        }
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                }
            });
        }
    }
});