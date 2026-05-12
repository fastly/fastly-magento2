define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config) {

        let configurationTab = $('#system_full_page_cache_fastly_fastly_advanced_configuration-head');

        /* HTTP/3 status elements*/
        let requestStateSpan = $('#http3-request_state_span');
        let requestStateMsgSpan = $('#fastly_http_3_status_message_span');
        let http3IsEnabled = true;

        /* HTTP/3 button messages */
        let successBtnMsg = $('#fastly-success-http3-button-msg');
        let errorBtnMsg = $('#fastly-error-http3-button-msg');

        requestStateSpan.find('.processing').show();

        let activeVersion = "";

        /**
         * HTTP/3 status change modal overlay options
         *
         * @type {{id: string, title: *, content: (function(): string), actionOk: actionOk}}
         */
        let http3Options = {
            id: 'fastly-http3-options',
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-http3-template').textContent;
            },
            actionOk: function () {
                toggleHttp3Status(activeVersion);
            }
        };

        configurationTab.one('click', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {
                if (service.status === false) {
                    return errorBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                activeVersion = service.active_version;

                checkHttp3Status(activeVersion, false).done(function (response) {
                    requestStateSpan.find('.processing').hide();
                    let http3EnabledStatus = requestStateMsgSpan.find('#http3_state_enabled');
                    let http3DisabledStatus = requestStateMsgSpan.find('#http3_state_disabled');

                    if (response.status === true) {
                        if (http3DisabledStatus.is(":hidden")) {
                            http3EnabledStatus.show();
                        }
                    } else if (response.status === false) {
                        if (http3EnabledStatus.is(":hidden")) {
                            http3DisabledStatus.show();
                        }
                    } else {
                        requestStateMsgSpan.find('#http3_state_unknown').show();
                    }
                }).fail(function () {
                    requestStateSpan.find('.processing').hide();
                    requestStateMsgSpan.find('#http3_state_unknown').show();
                });

            }).fail(function () {
                return errorBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });

        });



        /**
         * Queries Fastly API to retrieve HTTP/3 snippet
         *
         * @param activeVersion
         * @param loaderVisibility
         * @returns {*}
         */
        function checkHttp3Status(activeVersion, loaderVisibility)
        {
            return $.ajax({
                type: "POST",
                url: config.checkHttp3StatusUrl,
                showLoader: loaderVisibility,
                data: {
                    'active_version': activeVersion,
                    'form_key': window.FORM_KEY
                }
            });
        }

        /**
         * HTTP/3 status change button click event
         *
         * @description checks the Fastly service status and displays the HTTP/3 VCL snippet upload overlay
         */
        $('#fastly_enable_http3_button').on('click', function () {

            resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {
                if (service.status === false) {
                    return errorBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                activeVersion = service.active_version;
                let nextVersion = service.next_version;
                let serviceName = service.service.name;

                checkHttp3Status(activeVersion, true).done(function (response) {
                    overlay(http3Options);
                    setServiceLabel(activeVersion, nextVersion, serviceName);
                    let upload_button = $('.upload-button span');

                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('You are about to enable HTTP/3'));
                        upload_button.text('Enable');
                    } else {
                        $('.modal-title').text($.mage.__('You are about to disable HTTP/3'));
                        upload_button.text('Disable');
                    }
                    http3IsEnabled = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

            }).fail(function () {
                return errorBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Toggle HTTP/3 status
         *
         * @description uploads/removes the HTTP 3 VCL snippet and shows the new status and messages
         * @param activeVersion
         */
        function toggleHttp3Status(activeVersion)
        {
            let activateHttp3Flag = false;

            if ($('#fastly_activate_http3').is(':checked')) {
                activateHttp3Flag = true;
            }

            $.ajax({
                type: "POST",
                url: config.toggleHttp3Url,
                data: {
                    'activate_flag': activateHttp3Flag,
                    'active_version': activeVersion
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        let disabledOrEnabled = 'disabled';
                        let statusStr = $.mage.__('disabled');

                        if (http3IsEnabled === false) {
                            disabledOrEnabled = 'enabled';
                            statusStr = $.mage.__('enabled');
                        } else {
                            disabledOrEnabled = 'disabled';
                        }
                        successBtnMsg.text($.mage.__('HTTP/3 is successfully %1.').replace('%1', statusStr)).show();
                        $('.request_http3_state_span').hide();

                        if (disabledOrEnabled === 'enabled') {
                            requestStateMsgSpan.find('#http3_state_disabled').hide();
                            requestStateMsgSpan.find('#http3_state_enabled').show();
                        } else {
                            requestStateMsgSpan.find('#http3_state_enabled').hide();
                            requestStateMsgSpan.find('#http3_state_disabled').show();
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
