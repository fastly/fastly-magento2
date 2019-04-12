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
        let errorPathsBtnMsg = $('#fastly-error-paths-button-msg');
        let successPathsBtnMsg = $('#fastly-success-paths-button-msg');
        let errorRateLimitingBtnMsg = $('#fastly-error-rate-limiting-button-msg');
        let successRateLimitingBtnMsg = $('#fastly-success-rate-limiting-button-msg');
        let rateLimitingStateSpan = $('#rate_limiting_state_span');
        let rateLimitingStateMsgSpan = $('#rate_limiting_state_message_span');
        let rateLimiting = true;

        let rateLimitingOptions = {
            id: 'fastly-rate-limiting-options',
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-rate-limiting-template').textContent;
            },
            actionOk: function () {
                toggleRateLimiting(active_version);
            }
        };

        let pathsOptions = {
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-rate-limiting-paths-template').textContent;
            },
            actionOk: function () {
                saveRateLimitingPaths(active_version);
            }
        };

        getRateLimitingSetting(active_version, false).done(function (response) {
            rateLimitingStateSpan.find('.processing').hide();
            let rateLimitingStateEnabled = rateLimitingStateMsgSpan.find('#rate_limiting_state_enabled');
            let rateLimitingStateDisabled = rateLimitingStateMsgSpan.find('#rate_limiting_state_disabled');

            if (response.status === true) {
                if (rateLimitingStateDisabled.is(":hidden")) {
                    rateLimitingStateEnabled.show();
                }
            } else if (response.status === false) {
                if (rateLimitingStateEnabled.is(":hidden")) {
                    rateLimitingStateDisabled.show();
                }
            } else {
                rateLimitingStateMsgSpan.find('#rate_limiting_state_unknown').show();
            }
        }).fail(function () {
            rateLimitingStateSpan.find('.processing').hide();
            rateLimitingStateMsgSpan.find('#rate_limiting_state_unknown').show();
        });

        function getRateLimitingSetting(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "POST",
                url: config.checkRateLimitingSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        $('#fastly_paths_btn').on('click', function () {
            resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {
                if (service.status === false) {
                    return errorPathsBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;

                getRateLimitingSetting(active_version, true).done(function (resp) {
                    if (resp.status === false) {
                        return errorPathsBtnMsg.text($.mage.__('Please make sure that Path Protection is enabled.')).show();
                    }
                    $.when(
                        $.ajax({
                            type:"GET",
                            url: config.getPathsUrl,
                            showLoader: true
                        })
                    ).done(function (response) {
                        if (response.status === true) {
                            let paths = response.paths;
                            let pathsHtml = '';
                            if (paths.length > 0) {
                                $.each(paths, function (index, data) {
                                    pathsHtml += '<tr><td>' +
                                        '<input name="path[]" data-type="path" value="' + data.path + '" class="input-text admin__control-text path-field" type="text"></td>' +
                                        '<td><input name="comment[]" data-type="path" value="' + data.comment + '" class="input-text admin__control-text path-comment" type="text"></td>' +
                                        '<td class="col-actions">' +
                                        '<button class="action-delete remove_path"  title="Delete" type="button"><span>Delete</span></button>' +
                                        '</td></tr>';
                                });
                            } else {
                                pathsHtml += '<tr><td>' +
                                    '<input name="path[]" data-type="path" value="" class="input-text admin__control-text path-field" type="text"></td>' +
                                    '<td><input name="comment[]" data-type="path" value="" class="input-text admin__control-text path-comment" type="text"></td>' +
                                    '<td class="col-actions">' +
                                    '<button class="action-delete remove_path"  title="Delete" type="button"><span>Delete</span></button>' +
                                    '</td></tr>';
                            }
                            overlay(pathsOptions);
                            $('.upload-button span').text('Save');
                            $('.modal-title').text($.mage.__('Protected Paths management'));

                            $('#fastly-rate-limiting-table > tbody').html(pathsHtml);
                        }
                    });
                });
            });
        });

        $('#fastly_toggle_rate_limiting_btn').on('click', function () {
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
                    return errorRateLimitingBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                getRateLimitingSetting(active_version, true).done(function (response) {
                    overlay(rateLimitingOptions);
                    setServiceLabel(active_version, next_version, service_name);
                    let upload_button = $('.upload-button span');

                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('You are about to enable Path Protection'));
                        upload_button.text('Enable');
                    } else {
                        $('.modal-title').text($.mage.__('You are about to disable Path Protection'));
                        upload_button.text('Disable');
                    }
                    rateLimiting = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

            }).fail(function () {
                return errorRateLimitingBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        $('body').on('click', '#add-path', function () {
            $('#fastly-rate-limiting-table > tbody').append('<tr><td>' +
                '<input name="path[]" data-type="path" value="" class="input-text admin__control-text path-field" type="text"></td>' +
                '<td><input name="comment[]" data-type="path" value="" class="input-text admin__control-text path-comment" type="text"></td>' +
                '<td class="col-actions">' +
                '<button class="action-delete remove_path"  title="Delete" type="button"><span>Delete</span></button>' +
                '</td></tr>');
        });

        $('body').on('click', '.remove_path', function () {
            $(this).closest('tr').remove();
        });

        function saveRateLimitingPaths(active_version)
        {
            let paths = [];
            $('input[name="path[]"').each(function () {
                let path = $(this).val();
                console.log(path);
                let comment = $(this).closest('tr').find("input[name='comment[]']").val();
                paths.push({
                    path: path,
                    comment: comment
                });
            });

            $.ajax({
                type: "POST",
                url: config.updatePathsUrl,
                data: {
                    'active_version': active_version,
                    'activate_flag': true,
                    'paths': paths
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        successPathsBtnMsg.text($.mage.__('Paths successfully updated.')).show();
                        active_version = response.active_version;
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return errorPathsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        function toggleRateLimiting(active_version)
        {
            let activate_rate_limiting_flag = false;

            if ($('#fastly_activate_rate_limiting').is(':checked')) {
                activate_rate_limiting_flag = true;
            }

            $.ajax({
                type: "POST",
                url: config.toggleRateLimitingUrl,
                data: {
                    'activate_flag': activate_rate_limiting_flag,
                    'active_version': active_version
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        let disabledOrEnabled = 'disabled';

                        if (rateLimiting === false) {
                            disabledOrEnabled = 'enabled';
                        } else {
                            disabledOrEnabled = 'disabled';
                        }

                        successRateLimitingBtnMsg.text($.mage.__('Path Protection successfully ' + disabledOrEnabled + '.')).show();

                        if (disabledOrEnabled === 'enabled') {
                            rateLimitingStateMsgSpan.find('#rate_limiting_state_disabled').hide();
                            rateLimitingStateMsgSpan.find('#rate_limiting_state_enabled').show();
                        } else {
                            rateLimitingStateMsgSpan.find('#rate_limiting_state_enabled').hide();
                            rateLimitingStateMsgSpan.find('#rate_limiting_state_disabled').show();
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