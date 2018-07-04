define([
    "jquery",
    "message",
    "popup",
    "resetAllMessages",
    "showErrorMessage",
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function ($, message, popup, resetAllMessages, showErrorMessage) {
    return function (config, checkService, isAlreadyConfigured, active_version) {

            var requestStateSpan = $('#request_state_span');
            var requestStateMsgSpan = $('#fastly_request_state_message_span');
            var forceTls = true;
            /* TLS button messages */
            var successTlsBtnMsg = $('#fastly-success-tls-button-msg');
            var errorTlsBtnMsg = $('#fastly-error-tls-button-msg');
            var warningTlsBtnMsg = $('#fastly-warning-tls-button-msg');

            var tlsOptions = {
                    id: 'fastly-uploadvcl-options',
                    title: jQuery.mage.__(''),
                    content: function () {
                        return document.getElementById('fastly-tls-template').textContent;
                    },
                    actionOk: function () {
                        toggleTls(active_version);
                    }
                };

                $('#system_full_page_cache_fastly_fastly_advanced_configuration-head').unbind('click').on('click', function () {
                    if ($(this).attr("class") === "open") {
                        var tls = getTlsSetting(checkService.active_version, false);

                        tls.done(function (checkReqSetting) {
                            requestStateSpan.find('.processing').hide();
                            var tlsStateEnabled = requestStateMsgSpan.find('#force_tls_state_enabled');
                            var tlsStateDisabled = requestStateMsgSpan.find('#force_tls_state_disabled');
                            if (checkReqSetting.status !== false) {
                                if (tlsStateDisabled.is(":hidden")) {
                                    tlsStateEnabled.show();
                                }
                            } else {
                                if (tlsStateEnabled.is(":hidden")) {
                                    tlsStateDisabled.show();
                                }
                            }
                        }).fail(function () {
                            requestStateSpan.find('.processing').hide();
                            requestStateMsgSpan.find('#force_tls_state_unknown').show();
                        });
                    }
                });

            function getTlsSetting (active_version, loaderVisibility) {
                return $.ajax({
                    type: "POST",
                    url: config.checkTlsSettingUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            }

        /**
         * Force TLS button
         */
        $('#fastly_force_tls_button').on('click', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            // Tls button messages
            successTlsBtnMsg.hide();
            errorTlsBtnMsg.hide();
            warningTlsBtnMsg.hide();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {

                if (service.status === false) {
                    return errorTlsBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                var active_version = service.active_version;
                var next_version = service.next_version;
                var service_name = service.service.name;

                getTlsSetting (active_version, true).done(function (response) {
                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('We are about to turn on TLS'));
                    } else {
                        $('.modal-title').text($.mage.__('We are about to turn off TLS'));
                    }
                    forceTls = response.status;
                }).fail(function () {
                    message.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

                popup(tlsOptions);
                message(active_version, next_version, service_name);

            }).fail(function (msg) {
                return errorTlsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        // Toggle TLS process
        function toggleTls(active_version) {
            var activate_tls_flag = false;

            if ($('#fastly_activate_tls').is(':checked')) {
                activate_tls_flag = true;
            }

            $.ajax({
                type: "POST",
                url: config.toggleTlsSettingUrl,
                data: {
                    'activate_flag': activate_tls_flag,
                    'active_version': active_version
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        var onOrOff = 'off';
                        var disabledOrEnabled = 'disabled';
                        if (forceTls === false) {
                            onOrOff = 'on';
                            disabledOrEnabled = 'enabled';
                        } else {
                            onOrOff = 'off';
                            disabledOrEnabled = 'disabled';
                        }
                        successTlsBtnMsg.text($.mage.__('The Force TLS request setting is successfully turned ' + onOrOff + '.')).show();
                        $('.request_tls_state_span').hide();
                        if (disabledOrEnabled === 'enabled') {
                            requestStateMsgSpan.find('#force_tls_state_disabled').hide();
                            requestStateMsgSpan.find('#force_tls_state_enabled').show();
                        } else {
                            requestStateMsgSpan.find('#force_tls_state_enabled').hide();
                            requestStateMsgSpan.find('#force_tls_state_disabled').show();
                        }
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function (msg) {
                    // error handling
                }
            });
        }
    }
});