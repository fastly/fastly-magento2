define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* Force TLS state elements*/
        let requestStateSpan = $('#request_state_span');
        let requestStateMsgSpan = $('#fastly_request_state_message_span');
        let forceTls = true;
        /* Force TLS button messages */
        let successTlsBtnMsg = $('#fastly-success-tls-button-msg');
        let errorTlsBtnMsg = $('#fastly-error-tls-button-msg');
        let warningTlsBtnMsg = $('#fastly-warning-tls-button-msg');

        let active_version = serviceStatus.active_version;

        requestStateSpan.find('.processing').show();

        /**
         * Force TLS modal overlay options
         *
         * @type {{id: string, title: *, content: (function(): string), actionOk: actionOk}}
         */
        let tlsOptions = {
            id: 'fastly-tls-options',
            title: jQuery.mage.__(' '),
                content: function () {
                return document.getElementById('fastly-tls-template').textContent;
            },
            actionOk: function () {
                toggleTls(active_version);
            }
        };

        /**
         * Trigger the TLS setting status call
         *
         * @description sets and displays the status of the TLS VCL snippet
         */
        getTlsSetting(active_version, false).done(function (response) {
            requestStateSpan.find('.processing').hide();
            let tlsStateEnabled = requestStateMsgSpan.find('#force_tls_state_enabled');
            let tlsStateDisabled = requestStateMsgSpan.find('#force_tls_state_disabled');

            if (response.status === true) {
                if (tlsStateDisabled.is(":hidden")) {
                    tlsStateEnabled.show();
                }
            } else if (response.status === false) {
                if (tlsStateEnabled.is(":hidden")) {
                    tlsStateDisabled.show();
                }
            } else {
                requestStateMsgSpan.find('#force_tls_state_unknown').show();
            }
        }).fail(function () {
            requestStateSpan.find('.processing').hide();
            requestStateMsgSpan.find('#force_tls_state_unknown').show();
        });

        /**
         * Queries Fastly API to retrieve TLS setting
         *
         * @param active_version
         * @param loaderVisibility
         * @returns {*}
         */
        function getTlsSetting(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "POST",
                url: config.checkTlsSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        /**
         * Force TLS button click event
         *
         * @description checks the Fastly service status and displays the TLS VCL snippet upload overlay
         */
        $('#fastly_force_tls_button').on('click', function () {
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
                    return errorTlsBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                getTlsSetting(active_version, true).done(function (response) {
                    overlay(tlsOptions);
                    setServiceLabel(active_version, next_version, service_name);
                    let upload_button = $('.upload-button span');

                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('You are about to enable Force TLS'));
                        upload_button.text('Enable');
                    } else {
                        $('.modal-title').text($.mage.__('You are about to disable Force TLS'));
                        upload_button.text('Disable');
                    }
                    forceTls = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

            }).fail(function () {
                return errorTlsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Toggle TLS
         *
         * @description uploads/removes the TLS VCL snippet and shows the new status and messages
         * @param active_version
         */
        function toggleTls(active_version)
        {
            let activate_tls_flag = false;

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
                        let disabledOrEnabled = 'disabled';

                        if (forceTls === false) {
                            disabledOrEnabled = 'enabled';
                        } else {
                            disabledOrEnabled = 'disabled';
                        }
                        successTlsBtnMsg.text($.mage.__('Force TLS is successfully ' + disabledOrEnabled + '.')).show();
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
                }
            });
        }
    }
});