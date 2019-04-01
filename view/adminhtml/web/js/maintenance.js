define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let suStateSpan = $('#su_state_span');
        let suStateMsgSpan = $('#su_state_message_span');
        let superUsers = true;

        let successSuBtnMsg = $('#fastly-success-su-button-msg');
        let errorSuBtnMsg = $('#fastly-error-su-button-msg');
        let successUpdateSuBtnMsg = $('#fastly-success-update-su-button-msg');
        let errorUpdateSuBtnMsg = $('#fastly-error-update-su-button-msg');

        let active_version = serviceStatus.active_version;

        suStateSpan.find('.processing').show();

        let suOptions = {
            id: 'fastly-su-options',
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-su-template').textContent;
            },
            actionOk: function () {
                toggleSu();
            }
        };

        getSuSetting(active_version).done(function (response) {
            suStateSpan.find('.processing').hide();
            let suStateEnabled = suStateMsgSpan.find('#su_state_enabled');
            let suStateDisabled = suStateMsgSpan.find('#su_state_disabled');

            if (response.status === true) {
                if (suStateDisabled.is(":hidden")) {
                    suStateEnabled.show();
                }
            } else if (response.status === false) {
                if (suStateEnabled.is(":hidden")) {
                    suStateDisabled.show();
                }
            } else {
                suStateMsgSpan.find('#su_state_unknown').show();
            }
        }).fail(function () {
            suStateSpan.find('.processing').hide();
            suStateMsgSpan.find('#su_state_unknown').show();
        });

        $('#update_su_button').on('click', function () {
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
                    return errorSuBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                $.ajax({
                    type: "POST",
                    url: config.updateSuIpsUrl,
                    showLoader: true,
                    data: {'active_version': active_version}
                }).done(function (response) {
                    resetAllMessages();
                    if (response.status === false) {
                        return errorUpdateSuBtnMsg.text($.mage.__(response.msg)).show();
                    } else {
                        return successUpdateSuBtnMsg.text($.mage.__('Admin IPs list successfully updated')).show();
                    }
                });
            });
        });

        $('#su_button').on('click', function () {
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
                    return errorSuBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;

                getSuSetting(active_version, true).done(function (response) {
                    overlay(suOptions);
                    let upload_button = $('.upload-button span');

                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('You are about to enable Maintenance Mode'));
                        upload_button.text('Enable');
                    } else {
                        $('.modal-title').text($.mage.__('You are about to disable Maintenance Mode'));
                        upload_button.text('Disable');
                    }
                    superUsers = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });


            }).fail(function () {
                return errorSuBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        function getSuSetting(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "POST",
                url: config.checkSuSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        function toggleSu()
        {
            $.ajax({
                type: "GET",
                url: config.toggleSuSettingUrl,
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        let disabledOrEnabled = 'disabled';

                        if (superUsers === false) {
                            disabledOrEnabled = 'enabled';
                        } else {
                            disabledOrEnabled = 'disabled';
                        }
                        successSuBtnMsg.text($.mage.__('Maintenance Mode successfully ' + disabledOrEnabled + '.')).show();
                        $('.su_state_span').hide();

                        if (disabledOrEnabled === 'enabled') {
                            suStateMsgSpan.find('#su_state_disabled').hide();
                            suStateMsgSpan.find('#su_state_enabled').show();
                        } else {
                            suStateMsgSpan.find('#su_state_enabled').hide();
                            suStateMsgSpan.find('#su_state_disabled').show();
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
