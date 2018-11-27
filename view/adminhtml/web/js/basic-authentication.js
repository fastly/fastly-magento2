define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    "Magento_Ui/js/modal/confirm",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, showSuccessMessage, confirm) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* Auth button messages */
        let successAuthBtnMsg = $('#fastly-success-auth-button-msg');
        let errorAuthBtnMsg = $('#fastly-error-auth-button-msg');

        /* Auth list messages */
        let successAuthListBtnMsg = $('#fastly-success-auth-list-button-msg');
        let errorAuthListBtnMsg = $('#fastly-error-auth-list-button-msg');

        /* Auth state elements*/
        let authStateSpan = $('#auth_state_span');
        let authStateMsgSpan = $('#fastly_auth_state_message_span');

        let authStatus = true;
        let authDictStatus = null;

        let active_version = serviceStatus.active_version;

        authStateSpan.find('.processing').show();

        /**
         * Basic Authentication VCL snippet upload modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let authenticationOptions = {
            title: jQuery.mage.__(' '),
                content: function () {
                return document.getElementById('fastly-auth-template').textContent;
            },
            actionOk: function () {
                toggleAuth(active_version);
            }
        };

        /**
         * Basic Authentication container creation modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let authenticationContainerOptions = {
            title: jQuery.mage.__('Create container for authenticated users'),
                content: function () {
                return document.getElementById('fastly-auth-container-template').textContent;
            },
            actionOk: function () {
                createAuth(active_version);
            }
        };

        /**
         * Basic Authentication users modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let authenticationItemsOptions = {
            title: jQuery.mage.__('Basic Authentication users'),
                content: function () {
                return document.getElementById('fastly-auth-items-template').textContent;
            },
            actionOk: function () {
            }
        };

        /**
         * Basic Authentication delete all users modal overlay otions
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let authenticationContainerDeleteOptions = {
            title: jQuery.mage.__('Delete all authenticated users'),
                content: function () {
                return document.getElementById('fastly-auth-delete-template').textContent;
            },
            actionOk: function () {
                deleteMainAuth(active_version);
            }
        };

        /**
         * Trigger the Basic Authentication status call
         */
        getAuthSetting(active_version).done(function (response) {
            authStateSpan.find('.processing').hide();
            let authStateEnabled = authStateMsgSpan.find('#auth_state_enabled');
            let authStateDisabled = authStateMsgSpan.find('#auth_state_disabled');

            if (response.status === true) {
                if (authStateDisabled.is(":hidden")) {
                    authStateEnabled.show();
                }
            } else if (response.status === false) {
                if (authStateEnabled.is(":hidden")) {
                    authStateDisabled.show();
                }
            } else {
                authStateMsgSpan.find('#auth_state_unknown').show();
            }
        }).fail(function () {
            authStateSpan.find('.processing').hide();
            authStateMsgSpan.find('#auth_state_unknown').show();
        });


        /**
         * Queries the Fastly API to retrieve the Basic Authentication status
         *
         * @param active_version
         * @returns {*}
         */
        function getAuthSetting(active_version)
        {
            return $.ajax({
                type: "POST",
                url: config.checkAuthSettingUrl,
                showLoader: false,
                data: {'active_version': active_version}
            });
        }

        /**
         * Queries the Fastly API to retrieve the list of Basic Authentication users
         *
         * @param active_version
         * @returns {*}
         */
        function listAuths(active_version)
        {
            return $.ajax({
                type: "GET",
                url: config.getAuths,
                showLoader: true,
                data: {'active_version': active_version}
            });
        }

        /**
         * Process and display basic Authentication users
         *
         * @param auths
         */
        function processAuths(auths)
        {
            let html = '';
            $.each(auths, function (index, auth) {
                html += '<tr><td>' +
                    '<input name="auth_user" value="'+ auth.item_key +'" data-keyid="'+ auth.item_key_id +'" class="input-text admin__control-text dictionary-items-field" type="text" disabled></td>' +
                    '<td><input name="auth_pass" value="********" class="input-text admin__control-text dictionary-items-field" type="text" disabled></td>' +
                    '<td class="col-actions">' +
                    '<button class="action-delete remove_item_auth"  title="Delete" type="button"><span>Delete</span></button>' +
                    '</td></tr>';
            });
            overlay(authenticationItemsOptions);
            $('.upload-button').remove();

            if (html !== '') {
                $('#auth-items-table > tbody').html(html);
            }
        }

        /**
         * Save Basic Authentication user
         *
         * @param item_key
         * @param item_value
         * @returns {*}
         */
        function saveAuthItem(item_key, item_value)
        {
            return $.ajax({
                type: "GET",
                url: config.createAuthItem,
                showLoader: true,
                data: {'active_version': active_version, 'auth_user': item_key, 'auth_pass': item_value},
                beforeSend: function () {
                    resetAllMessages();
                }
            });
        }

        /**
         * Delete a Basic Authentication user
         *
         * @param item_key_id
         * @returns {*}
         */
        function deleteAuthItem(item_key_id)
        {
            return $.ajax({
                type: "GET",
                url: config.deleteAuthItem,
                showLoader: true,
                data: {'active_version': active_version, 'item_key_id': item_key_id},
                beforeSend: function () {
                    resetAllMessages();
                }
            });
        }

        /**
         * Toggle Basic Authentication VCL snippet
         *
         * @param active_version
         */
        function toggleAuth(active_version)
        {
            let activate_auth_flag = false;

            if ($('#fastly_activate_auth').is(':checked')) {
                activate_auth_flag = true;
            }

            $.ajax({
                type: "POST",
                url: config.toggleAuthSettingUrl,
                data: {
                    'activate_flag': activate_auth_flag,
                    'active_version': active_version
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        let disabledOrEnabled = 'disabled';

                        if (authStatus === false) {
                            disabledOrEnabled = 'enabled';
                        } else {
                            disabledOrEnabled = 'disabled';
                        }

                        successAuthBtnMsg.text($.mage.__('Basic Authentication is successfully ' + disabledOrEnabled + '.')).show();

                        if (disabledOrEnabled === 'enabled') {
                            authStateMsgSpan.find('#auth_state_disabled').hide();
                            authStateMsgSpan.find('#auth_state_enabled').show();
                        } else {
                            authStateMsgSpan.find('#auth_state_enabled').hide();
                            authStateMsgSpan.find('#auth_state_disabled').show();
                        }
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                }
            });
        }

        /**
         * Delete the Basic Authentication container and turn off Basic Authentication
         */
        function deleteMainAuth()
        {
            let activate_vcl = false;

            if ($('#fastly_activate_vcl').is(':checked')) {
                activate_vcl = true;
            }

            $.ajax({
                type: "POST",
                url: config.deleteAuth,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_vcl
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        successAuthBtnMsg.text($.mage.__('Basic Authentication is successfully turned off.')).show();
                        authStateMsgSpan.find('#auth_state_disabled').show();
                        authStateMsgSpan.find('#auth_state_enabled').hide();
                        active_version = response.active_version;
                        authDictStatus = false;
                        modal.modal('closeModal');
                        return successAuthListBtnMsg.text($.mage.__('Authentication users removed.')).show();
                    } else {
                        if (response.not_exists === true) {
                            authDictStatus = false;
                        }
                        resetAllMessages();
                        modal.modal('closeModal');
                        return errorAuthListBtnMsg.text($.mage.__(response.msg)).show();
                    }
                },
                error: function () {
                    authStateMsgSpan.find('#enable_auth_state_unknown').show();
                    return errorAuthListBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        /**
         * Create the Basic Authentication dictionary container
         */
        function createAuth()
        {
            let activate_vcl = false;

            if ($('#fastly_activate_vcl').is(':checked')) {
                activate_vcl = true;
            }

            $.ajax({
                type: "POST",
                url: config.createAuth,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_vcl
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        authDictStatus = true;
                        successAuthListBtnMsg.text($.mage.__('Authentication dictionary is successfully created.')).show();
                        active_version = response.active_version;
                        modal.modal('closeModal');
                        processAuths(response.auths);
                    } else if (response.status === 'empty') {
                        processAuths([]);
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }

                },
                error: function () {
                    return errorAuthListBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        /**
         * Add Basic Authentication user button on click event
         */
        $('body').on('click', '#add-auth-item', function () {
            $('#auth-items-table > tbody').append('<tr><td><input name="auth_user" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                '<td><input name="auth_pass" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                '<td class="col-actions">' +
                '<button class="action-delete fastly-save-action save_item_auth" title="Save" type="button"><span>Save</span></button>' +
                '<button class="action-delete remove_item_auth"  title="Delete" type="button" hidden><span>Delete</span></button>' +
                '</td></tr>');
        });

        /**
         * Save Basic Authentication user button on click event
         */
        $('body').on('click', '.save_item_auth', function () {
            let keyField = $(this).closest('tr').find("input[name='auth_user']");
            let valueField = $(this).closest('tr').find("input[name='auth_pass']");
            let item_key = keyField.val();
            let item_value = valueField.val();
            let errors = false;

            if (item_value === '') {
                errors = true;
                valueField.css('border-color', '#e22626');
            } else {
                valueField.css('border-color', '#878787');
            }

            if (errors) {
                resetAllMessages();
                return showErrorMessage($.mage.__('Please enter all required fields.'));
            }

            let self = this;

            saveAuthItem(item_key, item_value).done(function (response) {
                if (response.status === true) {
                    $(self).closest('tr').find("input[name='auth_user']").prop('disabled', true);
                    $(self).closest('tr').find("input[name='auth_user']").data('keyid', btoa(item_key + ':' + item_value));
                    $(self).closest('tr').find("input[name='auth_pass']").prop('disabled', true);
                    $(self).closest('tr').find(".save_item_auth").hide();
                    $(self).closest('tr').find(".remove_item_auth").show();
                    showSuccessMessage($.mage.__('Authentication entry is successfully saved.'));
                } else {
                    showErrorMessage(response.msg);
                }
            }).fail(function () {
                showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
            });
        });

        /**
         * Remove Basic Authentication user on click event
         */
        $('body').on('click', '.remove_item_auth', function () {
            let valueField = $(this).closest('tr').find("input[name='auth_user']");
            let self = this;
            let authItemKeyId = valueField.data('keyid');

            confirm({
                title: 'Delete Authentication Item',
                content: "Are you sure you want to delete this item?",
                actions: {
                    confirm: function () {
                        deleteAuthItem(authItemKeyId).done(function (response) {
                            if (response.status === true) {
                                $(self).closest('tr').remove();
                                showSuccessMessage($.mage.__('Authentication item is successfully deleted.'));
                            } else if (response.status === 'empty') {
                                showErrorMessage($.mage.__(response.msg));
                            }
                        }).fail(function () {
                            showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                        });
                    },
                    cancel: function () {}
                }
            });
        });

        /**
         * Remove Basic Authentication dictionary button on clikc event
         */
        $('body').on('click', '.remove_auth_dictionary', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                })
            ).done(function (service) {
                if (service.status === false) {
                    return errorAuthListBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                overlay(authenticationContainerDeleteOptions);
                setServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorAuthListBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Toggle Basic Authentication button on click event
         */
        $('#fastly_enable_auth_button').on('click', function () {
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
                    return errorAuthBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                getAuthSetting(active_version).done(function (response) {
                    authStatus = response.status;
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

                // Check if Users are available and Auth can be enabled
                let enableMsg = false;
                $.ajax({
                    type: "GET",
                    url: config.checkAuthUsersAvailable,
                    data: {'active_version': active_version},
                    showLoader: true,
                    success: function (response) {
                        if (response.status === 'empty') {
                            enableMsg = response.msg;
                        }

                        overlay(authenticationOptions);
                        setServiceLabel(active_version, next_version, service_name);
                        let upload_button = $('.upload-button span');

                        if (authStatus === false) {
                            $('.modal-title').text($.mage.__('You are about to enable Basic Authentication'));
                            upload_button.text('Enable');
                        } else {
                            $('.modal-title').text($.mage.__('You are about to disable Basic Authentication'));
                            upload_button.text('Disable');
                        }

                        if (enableMsg) {
                            let enableAuthOverlayMsg =  $('.fastly-message-error');
                            enableAuthOverlayMsg.text($.mage.__(response.msg)).show();
                        }
                    }
                });
            }).fail(function () {
                return errorAuthBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Add Basic Authentication dictionary container button on click event
         */
        $('#add-auth-container-button').on('click', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true,
                success: function (service) {
                    if (service.status === false) {
                        return errorAuthListBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                    }

                    active_version = service.active_version;
                    let next_version = service.next_version;
                    let service_name = service.service.name;

                    listAuths(active_version).done(function (authResp) {
                        if (authResp.status === true) {
                            processAuths(authResp.auths);
                        } else if (authResp.status === 'empty') {
                            processAuths([]);
                        } else {
                            overlay(authenticationContainerOptions);
                        }
                        setServiceLabel(active_version, next_version, service_name);
                    }).fail(function () {
                        return errorAuthListBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    });
                },
                fail: function () {
                    return errorAuthListBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        });
    }
});
