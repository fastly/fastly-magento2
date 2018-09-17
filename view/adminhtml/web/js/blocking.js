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

        /* Blocking state elements*/
        let blockingStateSpan = $('#blocking_state_span');
        let blockingStateMsgSpan = $('#fastly_blocking_state_message_span');
        let blocking = true;
        /* Blocking button messages */
        let successBlockingBtnMsg = $('#fastly-success-blocking-button-msg');
        let errorBlockingBtnMsg = $('#fastly-error-blocking-button-msg');
        let warningBlockingBtnMsg = $('#fastly-warning-blocking-button-msg');

        let active_version = serviceStatus.active_version;

        blockingStateSpan.find('.processing').show();

        /**
         * Blocking options for the modal popup
         *
         * @type {{id: string, title: *, content: (function(): string), actionOk: actionOk}}
         */
        let blockingOptions = {
            id: 'fastly-blocking-options',
            title: jQuery.mage.__(' '),
                content: function () {
                return document.getElementById('fastly-blocking-template').textContent;
            },
            actionOk: function () {
                toggleBlocking(active_version);
            }
        };

        /* Call getBlockingSetting function and display current status */
        getBlockingSetting(active_version, false).done(function (response) {
            blockingStateSpan.find('.processing').hide();
            let blockingStateEnabled = blockingStateMsgSpan.find('#blocking_state_enabled');
            let blockingStateDisabled = blockingStateMsgSpan.find('#blocking_state_disabled');

            if (response.status === true) {
                if (blockingStateDisabled.is(":hidden")) {
                    blockingStateEnabled.show();
                }
            } else if (response.status === false) {
                if (blockingStateEnabled.is(":hidden")) {
                    blockingStateDisabled.show();
                }
            } else {
                blockingStateMsgSpan.find('#blocking_state_unknown').show();
            }
        }).fail(function () {
            blockingStateSpan.find('.processing').hide();
            blockingStateMsgSpan.find('#blocking_state_unknown').show();
        });

        /**
         * Queries Fastly API to retrieve blocking setting
         *
         * @param active_version
         * @param loaderVisibility
         * @returns {*}
         */
        function getBlockingSetting(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "POST",
                url: config.checkBlockingSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        /* Blocking button on click event that triggers a modal popup */
        $('#fastly_blocking_button').on('click', function () {
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
                    return errorBlockingBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                getBlockingSetting(active_version, true).done(function (response) {
                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('We are about to enable Blocking'));
                    } else {
                        $('.modal-title').text($.mage.__('We are about to disable Blocking'));
                    }
                    blocking = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

                popup(blockingOptions);
                setServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorBlockingBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        function toggleBlocking(active_version)
        {
            let activate_blocking_flag = false;

            if ($('#fastly_activate_blocking').is(':checked')) {
                activate_blocking_flag = true;
            }

            $.ajax({
                type: "POST",
                url: config.toggleBlockingSettingUrl,
                data: {
                    'activate_flag': activate_blocking_flag,
                    'active_version': active_version
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        let disabledOrEnabled = 'disabled';

                        if (blocking === false) {
                            disabledOrEnabled = 'enabled';
                        } else {
                            disabledOrEnabled = 'disabled';
                        }

                        successBlockingBtnMsg.text($.mage.__('Blocking is successfully ' + disabledOrEnabled + '.')).show();

                        if (disabledOrEnabled === 'enabled') {
                            blockingStateMsgSpan.find('#blocking_state_disabled').hide();
                            blockingStateMsgSpan.find('#blocking_state_enabled').show();
                        } else {
                            blockingStateMsgSpan.find('#blocking_state_enabled').hide();
                            blockingStateMsgSpan.find('#blocking_state_disabled').show();
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