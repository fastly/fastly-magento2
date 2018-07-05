define([
    "jquery",
    "setServiceLabel",
    "popup",
    "resetAllMessages",
    "showErrorMessage",
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function ($, setServiceLabel, popup, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* Force TLS state elements*/
        var requestStateSpan = $('#request_state_span');
        var requestStateMsgSpan = $('#fastly_request_state_message_span');
        var forceTls = true;
        /* Force TLS button messages */
        var successTlsBtnMsg = $('#fastly-success-tls-button-msg');
        var errorTlsBtnMsg = $('#fastly-error-tls-button-msg');
        var warningTlsBtnMsg = $('#fastly-warning-tls-button-msg');

        requestStateSpan.find('.processing').show();

        /**
         * Force TLS options for the modal popup
         *
         * @type {{id: string, title: *, content: content, actionOk: actionOk}}
         */
        var tlsOptions = {
            id: 'fastly-tls-options',
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-tls-template').textContent;
            },
            actionOk: function () {
                toggleTls(serviceStatus.active_version);
            }
        };

        /* Call getTlsSetting function and display current status */
        getTlsSetting(serviceStatus.active_version, false).done(function (response) {
            requestStateSpan.find('.processing').hide();
            var tlsStateEnabled = requestStateMsgSpan.find('#force_tls_state_enabled');
            var tlsStateDisabled = requestStateMsgSpan.find('#force_tls_state_disabled');

            if (response.status === true) {
                if (tlsStateDisabled.is(":hidden")) {
                    tlsStateEnabled.show();
                }
            } else if (response.status === false){
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
        function getTlsSetting(active_version, loaderVisibility) {
            return $.ajax({
                type: "POST",
                url: config.checkTlsSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        /* Force TLS button on click event that triggers a modal popup */
        $('#fastly_force_tls_button').on('click', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

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
                        $('.modal-title').text($.mage.__('We are about to enable Force TLS.'));
                    } else {
                        $('.modal-title').text($.mage.__('We are about to disable Force TLS.'));
                    }
                    forceTls = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

                popup(tlsOptions);
                setServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorTlsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        // Toggles the Force TLS enable/disable process and displays the relevant message
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
                        var disabledOrEnabled = 'disabled';

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