define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "Magento_Ui/js/modal/prompt",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, prompt) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* Blocking state elements*/
        let blockingStateSpan = $('#blocking_state_span');
        let blockingStateMsgSpan = $('#fastly_blocking_state_message_span');
        let blocking = true;
        /* Blocking button messages */
        let successBlockingBtnMsg = $('#fastly-success-blocking-button-msg');
        let errorBlockingBtnMsg = $('#fastly-error-blocking-button-msg');
        /* Update Blocking button messages */
        let blockingSuccessBtnMsg = $('#fastly-update-blocking-success-button-msg');
        let blockingErrorBtnMsg = $('#fastly-update-blocking-error-button-msg');
        let blockingWarningBtnMsg = $('#fastly-update-blocking-warning-button-msg');
        /* <select> elements */
        let blockingTypeSelect = $('#system_full_page_cache_fastly_fastly_blocking_blocking_type');
        let countryListSelect = $('#system_full_page_cache_fastly_fastly_blocking_block_by_country');
        let aclSelect = $('#system_full_page_cache_fastly_fastly_blocking_block_by_acl');

        countryListSelect.add(aclSelect).on('change', function () {
            blockingWarningBtnMsg.text($.mage.__('Changes not active until Update Blocking Configuration is clicked')).show();
        });


        let active_version = serviceStatus.active_version;

        blockingStateSpan.find('.processing').show();

        /**
         * Blocking modal overlay options
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

        /**
         * Trigger the Blocking status call
         */
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

        /**
         * Toggle Blocking button on click event
         */
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
                    overlay(blockingOptions);
                    setServiceLabel(active_version, next_version, service_name);
                    let upload_button = $('.upload-button span');

                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('You are about to enable Blocking'));
                        upload_button.text('Enable');
                    } else {
                        $('.modal-title').text($.mage.__('You are about to disable Blocking'));
                        upload_button.text('Disable');
                    }
                    blocking = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

            }).fail(function () {
                return errorBlockingBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Update Blocking button on click event
         */
        $('#fastly_update_blocking_button').on('click', function () {
            resetAllMessages();

            $.ajax({
                type: "POST",
                url: config.updateBlockingUrl,
                showLoader: true,
                data: {
                    'service_id': $('#system_full_page_cache_fastly_fastly_service_id').val(),
                    'api_key': $('#system_full_page_cache_fastly_fastly_api_key').val(),
                    'acls': $('#system_full_page_cache_fastly_fastly_blocking_block_by_acl').serializeArray(),
                    'countries': $('#system_full_page_cache_fastly_fastly_blocking_block_by_country').serializeArray(),
                    'blocking_type': $('#system_full_page_cache_fastly_fastly_blocking_blocking_type').val()
                },
                cache: false,
                success: function (response) {
                    if (response.status === false) {
                        return blockingErrorBtnMsg.text($.mage.__('Please make sure that blocking is enabled.')).show();
                    } else {
                        return blockingSuccessBtnMsg.text($.mage.__('Blocking snippet has been updated successfully.')).show();
                    }
                },
                error: function () {
                    return blockingErrorBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        });

        /**
         * Blocking Type on change event
         */
        $('#system_full_page_cache_fastly_fastly_blocking_blocking_type').on('change', function () {
            if (this.value === '1') {
                prompt({
                    title: 'Blocking Type: Allowlist',
                    content: 'Turning on this feature will block ALL access except for users from designated countries/ACLs. ' +
                        'Please make sure you as the admin user are in one of the lists since you WILL lose access to the admin pages. ' +
                        'Only way to fix it is via Fastly management UI. Please type I ACKNOWLEDGE' +
                        ' in the box below if you are sure you want to do this.',
                    actions: {
                        confirm: function (input) {
                            if (input !== 'I ACKNOWLEDGE') {
                                $('#system_full_page_cache_fastly_fastly_blocking_blocking_type').val('0');
                            }
                            blockingWarningBtnMsg.text($.mage.__('Please Update Blocking Configuration')).show();
                        },
                        cancel: function () {
                            $('#system_full_page_cache_fastly_fastly_blocking_blocking_type').val('0');
                        },
                        always: function () {}
                    }
                });
            }
        });

        /**
         * Toggle Blocking VCL snippet
         *
         * @param active_version
         */
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
