define([
    "jquery",
    'mage/template',
    "Magento_Ui/js/modal/modal",
    'mage/translate',
    'mage/validation'
], function ($) {

    return function (config) {

        var requestStateSpan = '';
        var requestStateMsgSpan = '';
        var blockingStateSpan = '';
        var blockingStateMsgSpan = '';
        var imageStateSpan = '';
        var imageStateMsgSpan = '';

        $('#system_full_page_cache_caching_application').on('change', function () {
            if ($(this).val() == 'fastly') {
                init();
            }
        });

        $(document).ready(function () {
            if (config.isFastlyEnabled) {
                init();
            }

            /**
             * Add new dictionary item
             */

            $('body').on('click', '#add-dictionary-item', function (e) {
                $('#dictionary-items-table > tbody').append('<tr><td><input name="key" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                    '<td><input name="value" data-type="dictionary" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                    '<td class="col-actions">' +
                    '<button class="action-delete fastly-save-action save_item" title="Save" type="button"><span>Save</span></button>' +
                    '<button class="action-delete remove_item"  title="Delete" type="button"><span>Delete</span></button>' +
                    '</td></tr>');
            });

            /**
             * Add new acl item
             */

            $('body').on('click', '#add-acl-item', function (e) {
                var aclTimestamp = Math.round(e.timeStamp);
                $('#acl-items-table > tbody').append('<tr>' +
                    '<td><input name="value" data-type="acl" data-id="" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                    '<td><div class="admin__field-option" title="'+acl_negated_title+'"><input name="negated" class="admin__control-checkbox" type="checkbox" id="acl_entry_'+ aclTimestamp +'"><label class="admin__field-label" for="acl_entry_'+ aclTimestamp +'"></label></div></td>' +
                    '<td class="col-actions">' +
                    '<button class="action-delete fastly-save-action save_item" title="Save" type="button"><span>Save</span></button>' +
                    '<button class="action-delete remove_item"  title="Delete" type="button"><span>Delete</span></button>' +
                    '</td></tr>');
            });

            /**
             * Add new auth item
             */

            $('body').on('click', '#add-auth-item', function (e) {
                $('#auth-items-table > tbody').append('<tr><td><input name="auth_user" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                    '<td><input name="auth_pass" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                    '<td class="col-actions">' +
                    '<button class="action-delete fastly-save-action save_item_auth" title="Save" type="button"><span>Save</span></button>' +
                    '<button class="action-delete remove_item_auth"  title="Delete" type="button"><span>Delete</span></button>' +
                    '</td></tr>');
            });

            /**
             * Handles dictionary and ACL item removing
             */

            $('body').on('click', '.remove_item', function (e) {
                e.preventDefault();
                var valueField = $(this).closest('tr').find("input[name='value']");
                var item_key = $(this).closest('tr').find("input[name='key']").val();
                var self = this;
                var type = valueField.data('type');

                if (confirm("Are you sure you want to delete this item?")) {
                    if (type === 'acl') {
                        var acl_item_id = valueField.data('id');
                        vcl.deleteAclItem(acl_id, acl_item_id, true).done(function (response) {
                            if (response.status == true) {
                                $(self).closest('tr').remove();
                                vcl.showSuccessMessage($.mage.__('Acl item is successfully deleted.'));
                            }
                        }).fail(function () {
                            vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                        });
                    } else {
                        vcl.deleteEdgeDictionaryItem(dictionary_id, item_key, true).done(function (response) {
                            if (response.status == true) {
                                $(self).closest('tr').remove();
                                vcl.showSuccessMessage($.mage.__('Dictionary item is successfully deleted.'));
                            }
                        }).fail(function () {
                            vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                        });
                    }
                }
            });

            /**
             * Remove Auth dictionary (all users)
             */

            $('body').on('click', '.remove_auth_dictionary', function (e) {
                if (isAlreadyConfigured != true) {
                    $(this).attr('disabled', true);
                    return alert($.mage.__('Please save config prior to continuing.'));
                }

                vcl.resetAllMessages();

                $.when(
                    $.ajax({
                        type: "GET",
                        url: config.serviceInfoUrl,
                        showLoader: true
                    })
                ).done(function (service) {

                    if (service.status == false) {
                        return errorHtmlBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                    }

                    active_version = service.active_version;
                    next_version = service.next_version;
                    service_name = service.service.name;

                    vcl.getErrorPageRespObj(active_version, true).done(function (response) {
                        if (response.status == true) {
                            $('#error_page_html').text(response.errorPageResp.content).html();
                        }
                    }).fail(function () {
                        vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                    });

                    vcl.showPopup('fastly-auth-container-delete');
                    vcl.setActiveServiceLabel(active_version, next_version, service_name);

                }).fail(function () {
                    return errorHtmlBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                });
            });

            /**
             * Handles AUTH item removing
             */

            $('body').on('click', '.remove_item_auth', function (e) {
                e.preventDefault();
                var valueField = $(this).closest('tr').find("input[name='auth_user']");
                var self = this;
                var authItemKeyId = valueField.data('keyid');

                if (confirm("Are you sure you want to delete this item?")) {
                    vcl.deleteAuthItem(authItemKeyId, true).done(function (response) {
                        if (response.status == true) {
                            $(self).closest('tr').remove();
                            vcl.showSuccessMessage($.mage.__('Authentication item is successfully deleted.'));
                        } else if (response.status == 'empty') {
                            vcl.showSuccessMessage($.mage.__(response.msg));
                        }
                    }).fail(function () {
                        vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                    });
                }
            });

            /**
             * Handles dictionary and ACL item saving
             */

            $('body').on('click', '.save_item', function (e) {
                e.preventDefault();
                var keyField = $(this).closest('tr').find("input[name='key']");
                var valueField = $(this).closest('tr').find("input[name='value']");
                var item_key = keyField.val();
                var item_value = valueField.val();
                var errors = false;
                var type = valueField.data('type');

                if (item_key == '' && type !== 'acl') {
                    errors = true;
                    keyField.css('border-color', '#e22626');
                } else {
                    keyField.css('border-color', '#878787');
                }

                if (item_value == '') {
                    errors = true;
                    valueField.css('border-color', '#e22626');
                } else {
                    valueField.css('border-color', '#878787');
                }

                if (errors) {
                    vcl.resetAllMessages();
                    return vcl.showErrorMessage($.mage.__('Please enter all required fields.'));
                }

                var self = this;
                if (type === 'acl') {
                    var negated_field = $(this).closest('tr').find("input[name='negated']")[0].checked ? 1 : 0;
                    vcl.saveAclItem(acl_id, item_value, negated_field, true).done(function (response) {
                        if (response.status == true) {
                            $(self).closest('tr').find("input[name='value']").prop('disabled', true);
                            var newElement = $(self).closest('tr').find("input[name='value']")[0];
                            newElement.setAttribute('data-id', response.id);

                            vcl.showSuccessMessage($.mage.__('Acl item is successfully saved.'));
                        } else {
                            vcl.showErrorMessage(response.msg);
                        }
                    }).fail(function () {
                        vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                    });
                } else {
                    vcl.saveEdgeDictionaryItem(dictionary_id, item_key, item_value, true).done(function (response) {
                        if (response.status == true) {
                            $(self).closest('tr').find("input[name='key']").prop('disabled', true);
                            vcl.showSuccessMessage($.mage.__('Dictionary item is successfully saved.'));
                        } else {
                            vcl.showErrorMessage(response.msg);
                        }
                    }).fail(function () {
                        vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                    });
                }
            });

            /**
             * Handles AUTH item saving
             */

            $('body').on('click', '.save_item_auth', function (e) {
                e.preventDefault();
                var keyField = $(this).closest('tr').find("input[name='auth_user']");
                var valueField = $(this).closest('tr').find("input[name='auth_pass']");
                var item_key = keyField.val();
                var item_value = valueField.val();
                var errors = false;

                if (item_value == '') {
                    errors = true;
                    valueField.css('border-color', '#e22626');
                } else {
                    valueField.css('border-color', '#878787');
                }

                if (errors) {
                    vcl.resetAllMessages();
                    return vcl.showErrorMessage($.mage.__('Please enter all required fields.'));
                }

                var self = this;

                vcl.saveAuthItem(item_key, item_value, true).done(function (response) {
                    if (response.status == true) {
                        $(self).closest('tr').find("input[name='auth_user']").prop('disabled', true);
                        $(self).closest('tr').find("input[name='auth_user']").data('keyid', btoa(item_key + ':' + item_value));
                        $(self).closest('tr').find("input[name='auth_pass']").prop('disabled', true);
                        $(self).closest('tr').find(".action-delete .fastly-save-action save_item_auth").context.hide()
                        vcl.showSuccessMessage($.mage.__('Authentication entry is successfully saved.'));
                    } else {
                        vcl.showErrorMessage(response.msg);
                    }
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });
            });
        });

        function init()
        {
            $.ajax({
                type: "GET",
                url: config.isAlreadyConfiguredUrl
            }).done(function (response) {
                if (response.status == true) {
                    isAlreadyConfigured = response.flag;
                }
            });

            // Checking service status & presence of force_tls request setting
            requestStateSpan = $('#request_state_span');
            requestStateMsgSpan = $('#fastly_request_state_message_span');
            // Check service status & presence of blocking request setting
            blockingStateSpan = $('#blocking_state_span');
            blockingStateMsgSpan = $('#fastly_blocking_state_message_span');
            // Check service status & presence of image optimization request setting
            imageStateSpan = $('#imgopt_state_span');
            imageStateMsgSpan = $('#fastly_imgopt_state_message_span');
            // Checking service status & presence of basic auth request setting
            authStateSpan = $('#auth_state_span');
            authStateMsgSpan = $('#fastly_auth_state_message_span');
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                beforeSend: function (xhr) {
                    requestStateSpan.find('.processing').show();
                    blockingStateSpan.find('.processing').show();
                    imageStateSpan.find('.processing').show();
                }
            }).done(function (checkService) {
                if (checkService.status != false) {
                    active_version = checkService.active_version;
                    next_version = checkService.next_version;
                    // Fetch force tls req setting status
                    var tls = vcl.getTlsSetting(checkService.active_version, false);

                    tls.done(function (checkReqSetting) {
                        requestStateSpan.find('.processing').hide();
                        if (checkReqSetting.status != false) {
                            requestStateMsgSpan.find('#force_tls_state_enabled').show();
                        } else {
                            requestStateMsgSpan.find('#force_tls_state_disabled').show();
                        }
                    }).fail(function () {
                        requestStateSpan.find('.processing').hide();
                        requestStateMsgSpan.find('#force_tls_state_unknown').show();
                    });

                    var blocking = vcl.getBlockingSetting(checkService.active_version, false);

                    blocking.done(function (checkReqSetting) {
                        blockingStateSpan.find('.processing').hide();
                        if (checkReqSetting.status != false) {
                            blockingStateMsgSpan.find('#blocking_state_enabled').show();
                        } else {
                            blockingStateMsgSpan.find('#blocking_state_disabled').show();
                        }
                    }).fail(function () {
                        blockingStateSpan.find('.processing').hide();
                        blockingStateMsgSpan.find('#blocking_state_unknown').show();
                    });

                    var fastlyIo = vcl.getFastlyIoSetting(false);
                    var imageOptimization = vcl.getImageSetting(checkService.active_version, false);

                    fastlyIo.done(function (checkIoSetting) {
                        if (checkIoSetting.status == false) {
                            if (config.isIoEnabled) {
                                ioToggle.removeAttrs('disabled');
                            } else {
                                ioToggle.attr('disabled', 'disabled');
                            }
                        }
                    });

                    imageOptimization.done(function (checkReqSetting) {
                        imageStateSpan.find('.processing').hide();
                        if (checkReqSetting.status != false) {
                            imageStateMsgSpan.find('#imgopt_state_enabled').show();
                            fastlyIo.done(function (checkIoSetting) {
                                if (checkIoSetting.status == true) {
                                    imgBtn.removeClass('disabled');
                                    warningIoMsg.hide();
                                } else {
                                    warningIoMsg.text(
                                        $.mage.__(
                                            'Please contact your sales rep or send an email to support@fastly.com to request image optimization activation for your Fastly service.'
                                        )
                                    ).show();
                                }
                            });
                        } else {
                            imageStateMsgSpan.find('#imgopt_state_disabled').show();
                            fastlyIo.done(function (checkIoSetting) {
                                if (checkIoSetting.status == true) {
                                    imgBtn.removeClass('disabled');
                                    warningIoMsg.hide();
                                } else {
                                    imgBtn.addClass('disabled');
                                    warningIoMsg.text(
                                        $.mage.__(
                                            'Please contact your sales rep or send an email to support@fastly.com to request image optimization activation for your Fastly service.'
                                        )
                                    ).show();
                                }
                            });
                        }
                    }).fail(function () {
                        imageStateSpan.find('.processing').hide();
                        imageStateMsgSpan.find('#imgopt_state_unknown').show();
                    });

                    // Fetch basic auth setting status
                    var auth = vcl.getAuthSetting(checkService.active_version, false);
                    auth.done(function (checkReqSetting) {
                        authStateSpan.find('.processing').hide();
                        if (checkReqSetting.status != false) {
                            authStateMsgSpan.find('#enable_auth_state_enabled').show();
                        } else {
                            authStateMsgSpan.find('#enable_auth_state_disabled').show();
                        }
                    }).fail(function () {
                        authStateSpan.find('.processing').hide();
                        authStateMsgSpan.find('#enable_auth_state_unknown').show();
                    });

                    // Fetch basic auth dictionary status
                    var authDict = vcl.getAuthDictionary(checkService.active_version, true);
                    authDict.done(function (checkReqSetting) {
                        authStateSpan.find('.processing').hide();
                        authDictStatus = checkReqSetting.status;
                    }).fail(function () {
                        authStateSpan.find('.processing').hide();
                    });

                    // Fetch backends
                    vcl.getBackends(active_version, false).done(function (backendsResp) {
                        $('.loading-backends').hide();
                        if (backendsResp.status != false) {
                            if (backendsResp.backends.length > 0) {
                                backends = backendsResp.backends;
                                vcl.processBackends(backendsResp.backends);
                            } else {
                                $('.no-backends').show();
                            }
                        }
                    }).fail(function () {
                        // TO DO: implement
                    });

                    // Fetch dictionaries
                    vcl.listDictionaries(active_version, false).done(function (dictResp) {
                        $('.loading-dictionaries').hide();
                        if (dictResp.status != false) {
                            if (dictResp.status != false) {
                                if (dictResp.dictionaries.length > 0) {
                                    dictionaries = dictResp.dictionaries;
                                    vcl.processDictionaries(dictResp.dictionaries);
                                } else {
                                    $('.no-dictionaries').show();
                                }
                            }
                        }
                    }).fail(function () {
                        return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    });

                    // Fetch ACLs
                    vcl.listAcls(active_version, false).done(function (aclResp) {
                        $('.loading-acls').hide();
                        if (aclResp.status != false) {
                            if (aclResp.status != false) {
                                if (aclResp.acls.length > 0) {
                                    acls = aclResp.acls;
                                    vcl.processAcls(aclResp.acls);
                                } else {
                                    $('.no-acls').show();
                                }
                            }
                        }
                    }).fail(function () {
                        return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    });
                } else {
                    requestStateSpan.find('.processing').hide();
                    requestStateMsgSpan.find('#force_tls_state_unknown').show();
                    blockingStateSpan.find('.processing').hide();
                    blockingStateMsgSpan.find('#blocking_state_unknown').show();
                    imageStateSpan.find('.processing').hide();
                    imageStateMsgSpan.find('#imgopt_state_unknown').show();
                }
            }).fail(function () {
                requestStateMsgSpan.find('#force_tls_state_unknown').show();
                imageStateMsgSpan.find('#imgopt_state_unknown').show();
            });
        }

        /**
         * Backend Edit icon
         */

        $('body').on('click', 'button.fastly-edit-backend-icon', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl
            }).done(function (checkService) {
                active_version = checkService.active_version;
                next_version = checkService.next_version;
                service_name = checkService.service.name;
                vcl.setActiveServiceLabel(active_version, next_version, service_name);
            });

            var backend_id = $(this).data('backend-id');
            if (backends != null && backend_id != null) {
                vcl.showPopup('fastly-backend-options');
                var backend_name = "Backend " + backends[backend_id].name;
                $('.modal-title').text($.mage.__(backend_name));
                $('#backend_name').val(backends[backend_id].name);
                $('#backend_shield option[value=\'' + backends[backend_id].shield +'\']').attr('selected','selected');
                $('#backend_connect_timeout').val(backends[backend_id].connect_timeout);
                $('#backend_between_bytes_timeout').val(backends[backend_id].between_bytes_timeout);
                $('#backend_first_byte_timeout').val(backends[backend_id].first_byte_timeout);
            }
        });

        /**
         * delete dictionary button
         */
        $('body').on('click', 'button#delete-dictionary-container-button', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl
            }).done(function (checkService) {
                active_version = checkService.active_version;
                next_version = checkService.next_version;
                service_name = checkService.service.name;
                vcl.setActiveServiceLabel(active_version, next_version, service_name);
            });

            $.ajax({
                type: "POST",
                url: config.getDictionaries,
                showLoader: true,
                data: {'active_version': active_version}
            }).done(function (response) {
                if (response.status == true) {
                    edgeDictionaries = response.dictionaries;
                    var dictionariesHtml = '';
                    if (edgeDictionaries.length > 0) {
                        $.each(edgeDictionaries, function (index, item) {
                            dictionariesHtml += '<tr>' +
                                '<td><input name="key" value="'+ item.id +'" class="input-text admin__control-text dictionary-items-field" type="hidden" disabled>' +
                                '<input name="value" data-type="dictionary" value="'+ item.name +'" class="input-text admin__control-text dictionary-items-field" type="text" disabled></td>' +
                                '<td class="col-actions">' +
                                '<input name="dictionary-delete[]" class="admin__control-checkbox" type="checkbox" value="'+ item.name +'"><label class="admin__field-label"></label>' +
                                '</td></tr>';
                        });
                    } else {
                        edgeDictionaries = [];
                    }

                    vcl.showPopup('fastly-delete-dictionary-container-options');
                    vcl.setActiveServiceLabel(active_version, next_version, service_name);

                    if (dictionariesHtml != '') {
                        $('#delete-dictionary-table > tbody').html(dictionariesHtml);
                    }
                }
            });
        });
        // delete dictionary container button
        $('body').on('click', 'button.fastly-delete-dictionary-icon', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl
            }).done(function (checkService) {
                active_version = checkService.active_version;
                next_version = checkService.next_version;
                service_name = checkService.service.name;
                vcl.setActiveServiceLabel(active_version, next_version, service_name);
            });

            dictionary_id = $(this).data('dictionary-id');

            if (dictionaries != null && dictionary_id != null) {
                var dictionaryHtml = '<input name="dictionary" value="' + dictionary_id + '" class="input-text admin__control-text dictionary-field" type="hidden" disabled>';
                vcl.showPopup('fastly-delete-dictionary-container-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);
                var containerWarning = $('#fastly-container-warning');
                containerWarning.text($.mage.__('You are about to delete the "' + dictionary_id + '" dictionary container.'));
                containerWarning.show();
                if (dictionaryHtml != '') {
                    $('#delete-dictionary-container').html(dictionaryHtml);
                }
            }
        });

        // delete acl container button
        $('body').on('click', 'button.fastly-delete-acl-icon', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl
            }).done(function (checkService) {
                active_version = checkService.active_version;
                next_version = checkService.next_version;
                service_name = checkService.service.name;
                vcl.setActiveServiceLabel(active_version, next_version, service_name);
            });

            acl_id = $(this).data('acl-id');

            if (acls != null && acl_id != null) {
                var aclHtml = '<input name="acl" value="' + acl_id + '" class="input-text admin__control-text acl-field" type="hidden" disabled>';
                vcl.showPopup('fastly-delete-acl-container-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);
                var containerWarning = $('#fastly-container-warning');
                containerWarning.text($.mage.__('You are about to delete the "' + acl_id + '" ACL container.'));
                containerWarning.show();
                if (aclHtml != '') {
                    $('#delete-acl-container').html(aclHtml);
                }
            }
        });

        /**
         * Dictionary/ACL Edit icon
         */

        $('body').on('click', 'button.fastly-edit-dictionary-icon', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl
            }).done(function (checkService) {
                active_version = checkService.active_version;
                next_version = checkService.next_version;
                service_name = checkService.service.name;
                vcl.setActiveServiceLabel(active_version, next_version, service_name);
            });

            dictionary_id = $(this).data('dictionary-id');
            acl_id = $(this).data('acl-id');
            // Handle Dictionaries
            if (dictionary_id) {
                if (dictionaries != null && dictionary_id != null) {
                    $.ajax({
                        type: "POST",
                        url: config.getDictionaryItems,
                        showLoader: true,
                        data: {'dictionary_id': dictionary_id}
                    }).done(function (response) {
                        if (response.status == true) {
                            dictionaryItems = response.dictionaryItems;
                            var itemsHtml = '';
                            if (response.dictionaryItems.length > 0) {
                                $.each(response.dictionaryItems, function (index, item) {
                                    itemsHtml += '<tr><td>' +
                                        '<input name="key" value="'+ item.item_key +'" class="input-text admin__control-text dictionary-items-field" type="text" disabled></td>' +
                                        '<td><input name="value" data-type="dictionary" value="'+ item.item_value +'" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                                        '<td class="col-actions">' +
                                        '<button class="action-delete fastly-save-action save_item" title="Save" type="button"><span>Save</span></button>' +
                                        '<button class="action-delete remove_item"  title="Delete" type="button"><span>Delete</span></button>' +
                                        '</td></tr>';
                                });
                            }
                        } else {
                            dictionaryItems = [];
                        }
                        vcl.showPopup('fastly-edge-items');
                        $('.upload-button').remove();

                        if (itemsHtml != '') {
                            $('#dictionary-items-table > tbody').html(itemsHtml);
                        }
                    });
                }
            } else {
                // Handle ACLs
                if (acls != null && acl_id != null) {
                    $.ajax({
                        type: "POST",
                        url: config.getAclItems,
                        showLoader: true,
                        data: {'acl_id': acl_id}
                    }).done(function (response) {
                        if (response.status == true) {
                            aclItems = response.aclItems;
                            var itemsHtml = '';
                            if (response.aclItems.length > 0) {
                                $.each(response.aclItems, function (index, item) {
                                    var negated = item.negated == 1 ? ' checked' : '';
                                    if (item.subnet) {
                                        ip_output = item.ip + '/' + item.subnet;
                                    } else {
                                        ip_output = item.ip;
                                    }
                                    itemsHtml += '<tr><td>' +
                                        '<input name="value" data-type="acl" data-id="'+ item.id +'" value="'+ ip_output +'" class="input-text admin__control-text dictionary-items-field" type="text" disabled></td>' +
                                        '<td><div class="admin__field-option" title="'+ acl_negated_title +'"><input name="negated" class="admin__control-checkbox" type="checkbox" id="acl_entry_'+ item.id +'"'+negated+'><label class="admin__field-label" for="acl_entry_'+ item.id +'"></label></div></td>' +
                                        '<td class="col-actions">' +
                                        '<button class="action-delete fastly-save-action save_item" title="Save" type="button"><span>Save</span></button>' +
                                        '<button class="action-delete remove_item"  title="Delete" type="button"><span>Delete</span></button>' +
                                        '</td></tr>';
                                });
                            }
                        } else {
                            aclItems = [];
                        }
                        vcl.showPopup('fastly-acl-items');
                        $('.upload-button').remove();

                        if (itemsHtml != '') {
                            $('#acl-items-table > tbody').html(itemsHtml);
                        }
                    });
                }
            }
        });

        /**
         * Manage Auth Icon (Edit)
         */

        $('body').on('click', '#add-auth-container-button', function () {

            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true,
                success: function (service) {

                    if (service.status == false) {
                        return errorHtmlBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                    }

                    active_version = service.active_version;
                    next_version = service.next_version;
                    service_name = service.service.name;

                    vcl.getErrorPageRespObj(active_version, true).done(function (response) {
                        if (response.status == true) {
                            $('#error_page_html').text(response.errorPageResp.content).html();
                        }
                    }).fail(function () {
                        vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                    });

                    if (authDictStatus != false) {
                        vcl.listAuths(active_version, false).done(function (authResp) {
                            $('.loading-dictionaries').hide();
                            if (authResp.status === true) {
                                if (authResp.auths.length > 0) {
                                    auths = authResp.auths;
                                    vcl.processAuths(authResp.auths);
                                } else {
                                    $('.no-dictionaries').show();
                                }
                            } else if (authResp.status == 'empty') {
                                auths = authResp.auths;
                                vcl.processAuths([]);
                            }
                        }).fail(function () {
                            return errorAuthBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                        });
                    } else {
                        vcl.showPopup('fastly-auth-container-options');
                        vcl.setActiveServiceLabel(active_version, next_version, service_name);
                    }
                },
                fail: function () {
                    return errorHtmlBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        });

        /**
         * VCL Upload button
         */

        $('#fastly_vcl_upload_button').on('click', function () {

            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                })
            ).done(function (service) {

                if (service.status == false) {
                    return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;
                vcl.showPopup('fastly-uploadvcl-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorVclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });


        $('#fastly_push_image_config').on('click', function () {
            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {

                if (service.status == false) {
                    return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;
                vcl.getImageSetting(active_version, true).done(function (response) {
                    if (response.status == false) {
                        $('.modal-title').text($.mage.__('We are about to upload the Fastly image optimization snippet'));
                    } else {
                        $('.modal-title').text($.mage.__('We are about to remove the Fastly image optimization snippet'));
                    }
                    imageOptimization = response.status;
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });
                vcl.showPopup('fastly-image-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);

            }).fail(function (msg) {
                return errorImageBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Force TLS button
         */
        $('#fastly_force_tls_button').on('click', function () {
            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {

                if (service.status == false) {
                    return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;
                vcl.getTlsSetting(active_version, true).done(function (response) {
                    if (response.status == false) {
                        $('.modal-title').text($.mage.__('We are about to turn on TLS'));
                    } else {
                        $('.modal-title').text($.mage.__('We are about to turn off TLS'));
                    }
                    forceTls = response.status;
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });
                vcl.showPopup('fastly-tls-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);

            }).fail(function (msg) {
                return errorTlsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Blocking button
         */

        $('#fastly_blocking_button').on('click', function () {

            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {

                if (service.status == false) {
                    return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;
                vcl.getBlockingSetting(active_version, true).done(function (response) {
                    if (response.status == false) {
                        $('.modal-title').text($.mage.__('We are about to turn on blocking'));
                    } else {
                        $('.modal-title').text($.mage.__('We are about to turn off blocking'));
                    }
                    blocking = response.status;
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });
                vcl.showPopup('fastly-blocking-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);

            }).fail(function (msg) {
                return errorBlockingBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Enable Auth button
         */

        $('#fastly_enable_auth_button').on('click', function () {

            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {

                if (service.status == false) {
                    return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;
                vcl.getAuthSetting(active_version, true).done(function (response) {
                    if (response.status == false) {
                        $('.modal-title').text($.mage.__('We are about to turn on Basic Authentication'));
                    } else {
                        $('.modal-title').text($.mage.__('We are about to turn off Basic Authentication'));
                    }
                    authStatus = response.status;
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

                // Check if Users are available and Auth can be enabled
                var enableMsg = false;
                $.ajax({
                    type: "GET",
                    url: config.checkAuthUsersAvailable,
                    data: {
                        'active_version': active_version
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == 'empty') {
                            enableMsg = response.msg;
                        }

                        vcl.showPopup('fastly-auth-options');
                        vcl.setActiveServiceLabel(active_version, next_version, service_name);

                        if (enableMsg) {
                            var enableAuthPopupMsg =  $('.fastly-message-error');
                            enableAuthPopupMsg.text($.mage.__(response.msg));
                            enableAuthPopupMsg.show();
                        }
                    }
                });
            }).fail(function (msg) {
                return errorAuthBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Set Error Page HTML button
         */

        $('#fastly_error_page_button').on('click', function () {

            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                })
            ).done(function (service) {

                if (service.status == false) {
                    return errorHtmlBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;

                vcl.getErrorPageRespObj(active_version, true).done(function (response) {
                    if (response.status == true) {
                        $('#error_page_html').text(response.errorPageResp.content).html();
                    }
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });

                vcl.showPopup('fastly-error-page-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorHtmlBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Add dictionary container button
         */

        $('#add-dictionary-container-button').on('click', function () {

            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                })
            ).done(function (service) {

                if (service.status == false) {
                    return errorHtmlBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;

                vcl.getErrorPageRespObj(active_version, true).done(function (response) {
                    if (response.status == true) {
                        $('#error_page_html').text(response.errorPageResp.content).html();
                    }
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });

                vcl.showPopup('fastly-dictionary-container-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorHtmlBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Add acl container button
         */

        $('#add-acl-container-button').on('click', function () {

            if (isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            vcl.resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                })
            ).done(function (service) {

                if (service.status == false) {
                    return errorHtmlBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;

                vcl.getErrorPageRespObj(active_version, true).done(function (response) {
                    if (response.status == true) {
                        $('#error_page_html').text(response.errorPageResp.content).html();
                    }
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });

                vcl.showPopup('fastly-acl-container-options');
                vcl.setActiveServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorHtmlBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        var authDictStatus = null;
        var authDict = null;
        var enableAuthBtn = null;
        var edgeDictionaries = null;
        var edgeAcls = null;
        var authStatus = true;
        var backends = null;
        var dictionaries = null;
        var dictionary_id = null;
        var acls = null;
        var auths = null;
        var acl_id = null;
        var dictionaryItems = null;
        var active_version = '';
        var next_version = '';
        var service_name;
        var forceTls = true;
        var blocking = true;
        var imageOptimization = true;
        var isAlreadyConfigured = true;
        /* Image button message */
        var successImageBtnMsg = $('#fastly-success-imgopt-button-msg');
        var errorImageBtnMsg = $('#fastly-error-imgopt-button-msg');
        var warningImageBtnMsg = $('#fastly-warning-imgopt-button-msg');
        /* IO setting status message */
        var successIoMsg = $('#fastly-success-io-msg');
        var errorIoMsg = $('#fastly-error-io-msg');
        var warningIoMsg = $('#fastly-warning-io-msg');
        /* VCL button messages */
        var successVclBtnMsg = $('#fastly-success-vcl-button-msg');
        var errorVclBtnMsg = $('#fastly-error-vcl-button-msg');
        var warningVclBtnMsg = $('#fastly-warning-vcl-button-msg');
        /* TLS button messages */
        var successTlsBtnMsg = $('#fastly-success-tls-button-msg');
        var errorTlsBtnMsg = $('#fastly-error-tls-button-msg');
        var warningTlsBtnMsg = $('#fastly-warning-tls-button-msg');
        /* Blocking button messages */
        var successBlockingBtnMsg = $('#fastly-success-blocking-button-msg');
        var errorBlockingBtnMsg = $('#fastly-error-blocking-button-msg');
        var warningBlockingBtnMsg = $('#fastly-warning-blocking-button-msg');
        /* Error page HTML button */
        var successHtmlBtnMsg = $('#fastly-success-html-page-button-msg');
        var errorHtmlBtnMsg = $('#fastly-error-html-page-button-msg');
        var warningHtmlBtnMsg = $('#fastly-warning-html-page-button-msg');
        /* Dictionary button */
        var successDictionaryBtnMsg = $('#fastly-success-edge-button-msg');
        var errorDictionaryBtnMsg = $('#fastly-error-edge-button-msg');
        /* Acl button */
        var successAclBtnMsg = $('#fastly-success-acl-button-msg');
        var errorAclBtnMsg = $('#fastly-error-acl-button-msg');
        /* ACL negated checkbox title */
        var acl_negated_title = "Negates another ACL entry.\n\n" +
            "Example: If you have a purge_allow_acl that has "+
            "192.168.1.0/24 but you add negated IP : 192.168.1.4, "+
            "it means every IP in 192.168.1.0/24 range has access except for 192.168.1.4.";
        /* Auth button messages */
        var successAuthBtnMsg = $('#fastly-success-auth-button-msg');
        var errorAuthBtnMsg = $('#fastly-error-auth-button-msg');
        var warningAuthBtnMsg = $('#fastly-warning-auth-button-msg');
        var deleteAuthBtnMsgError = $('#fastly-error-auth-list-button-msg');
        var deleteAuthBtnMsgSuccess = $('#fastly-success-auth-list-button-msg');
        var imgBtn = $('#fastly_push_image_config');
        var ioToggle = $('#system_full_page_cache_fastly_fastly_image_optimization_configuration_image_optimizations');

        var vcl = {

            showPopup: function (divId) {
                var self = this;

                this.modal = jQuery('<div/>').attr({id: divId}).html(this.uploadVclConfig[divId].content()).modal({
                    modalClass: 'magento',
                    title: this.uploadVclConfig[divId].title,
                    type: 'slide',
                    closed: function (e, modal) {
                        modal.modal.remove();
                    },
                    opened: function () {
                        if (self.uploadVclConfig[divId].opened) {
                            self.uploadVclConfig[divId].opened.call(self);
                        }
                    },
                    buttons: [{
                        text: jQuery.mage.__('Cancel'),
                        'class': 'action cancel',
                        click: function () {
                            this.closeModal();
                        }
                    }, {
                        text: jQuery.mage.__('Upload'),
                        'class': 'action primary upload-button',
                        click: function () {
                            self.uploadVclConfig[divId].actionOk.call(self);
                        }
                    }]
                });
                this.modal.modal('openModal');
            },

            // Queries Fastly API to retrive services
            getService: function () {
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true,
                    success: function (response) {
                        if (response != false) {
                            vcl.setActiveServiceLabel(active_version, next_version);
                        }
                    },
                    error: function (msg) {
                        // error handling
                    }
                });
            },

            // Queries Fastly API to retrieve image optimization snippet setting
            getImageSetting: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "POST",
                    url: config.checkImageSettingUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            },

            // Queries Fastly Api to retrieve the Fastly service image optimization setting
            getFastlyIoSetting: function () {
                return $.ajax({
                    type: "GET",
                    url: config.checkFastlyIoSettingUrl
                });
            },

            // Queries Fastly API to retrieve Tls setting
            getTlsSetting: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "POST",
                    url: config.checkTlsSettingUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            },

            // Queries Fastly API to retrieve blocking setting
            getBlockingSetting: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "POST",
                    url: config.checkBlockingSettingUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            },

            // Queries Fastly API to retrive Basic Auth status
            getAuthSetting: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "POST",
                    url: config.checkAuthSettingUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            },

            // Queries Fastly API to retrive Basic Auth status
            getAuthDictionary: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "POST",
                    url: config.checkAuthDictionaryUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            },

            // Queries Fastly API to retrive Backends
            getBackends: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.fetchBackendsUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version},
                    beforeSend: function (xhr) {
                        $('.loading-backends').show();
                    }
                });
            },

            // Queries Fastly API to retrive Backends
            getErrorPageRespObj: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.getErrorPageRespObj,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            },

            // Process backends
            processBackends: function (backends) {
                $.each(backends, function (index, backend) {
                    var html = "<tr id='fastly_" + index + "'>";
                    html += "<td><input data-backendId='"+ index + "' id='backend_" + index + "' value='"+ backend.name +"' disabled='disabled' class='input-text' type='text'></td>";
                    html += "<td class='col-actions'><button class='action-delete fastly-edit-backend-icon' data-backend-id='" + index + "' id='fastly-edit-backend_"+ index + "' title='Edit backend' type='button'></td></tr>";
                    $('#fastly-backends-list').append(html);

                });
            },

            // Process dictionaries
            processDictionaries: function (dictionaries) {
                var html = '';
                $.each(dictionaries, function (index, dictionary) {
                    html += "<tr id='fastly_dict_" + index + "'>";
                    html += "<td><input data-dictionaryId='"+ dictionary.id + "' id='dict_" + index + "' value='"+ dictionary.name +"' disabled='disabled' class='input-text' type='text'></td>";
                    html += "<td class='col-actions'>" +
                        "<button class='action-delete fastly-edit-dictionary-icon' data-dictionary-id='" + dictionary.id + "' id='fastly-edit-dictionary_"+ index + "' title='Edit dictionary' type='button'>" +
                        "<button class='action-delete fastly-delete-dictionary-icon' data-dictionary-id='" + dictionary.name + "' id='fastly-delete-dictionary_"+ index + "' title='Delete dictionary' type='button'>" +
                        "</td></tr>";
                });
                if (html != '') {
                    $('.no-dictionaries').hide();
                }
                $('#fastly-dictionaries-list').html(html);
            },

            // Queries Fastly API to retrieve Dictionaries
            listDictionaries: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.getDictionaries,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version},
                    beforeSend: function (xhr) {
                        $('.loading-dictionaries').show();
                    }
                });
            },

            // Queries Fastly API to retrieve ACLs
            listAcls: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.getAcls,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version},
                    beforeSend: function (xhr) {
                        $('.loading-acls').show();
                    }
                });
            },

            // Queries Fastly API to retrieve ACLs
            listAuths: function (active_version, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.getAuths,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version},
                    beforeSend: function (xhr) {
                        $('.loading-dictionaries').show();
                    }
                });
            },

            // Process ACLs
            processAcls: function (acls) {
                var html = '';
                $.each(acls, function (index, acl) {
                    html += "<tr id='fastly_acl_" + index + "'>";
                    html += "<td><input data-aclId='"+ acl.id + "' id='acl_" + index + "' value='"+ acl.name +"' disabled='disabled' class='input-text' type='text'></td>";
                    html += "<td class='col-actions'>" +
                        "<button class='action-delete fastly-edit-dictionary-icon' data-acl-id='" + acl.id + "' id='fastly-edit-acl_"+ index + "' title='Edit Acl' type='button'>" +
                        "<button class='action-delete fastly-delete-acl-icon' data-acl-id='" + acl.name + "' id='fastly-delete-acl_"+ index + "' title='Delete Acl' type='button'>" +
                        "</td></tr>";
                });
                if (html != '') {
                    $('.no-acls').hide();
                }
                $('#fastly-acls-list').html(html);
            },

            // Process AUTHs
            processAuths: function (auths) {
                var html = '';
                $.each(auths, function (index, auth) {
                    html += '<tr><td>' +
                        '<input name="auth_user" value="'+ auth.item_key +'" data-keyid="'+ auth.item_key_id +'" class="input-text admin__control-text dictionary-items-field" type="text" disabled></td>' +
                        '<td><input name="auth_pass" value="********" class="input-text admin__control-text dictionary-items-field" type="text" disabled></td>' +
                        '<td class="col-actions">' +
                        '<button class="action-delete remove_item_auth"  title="Delete" type="button"><span>Delete</span></button>' +
                        '</td></tr>';
                });

                if (html != '') {
                    $('.no-dictionaries').hide();
                }
                vcl.showPopup('fastly-auth-items');
                $('.upload-button').remove();

                if (html != '') {
                    $('#auth-items-table > tbody').html(html);
                }
            },

            // Delete Edge Dictionary item
            deleteEdgeDictionaryItem: function (dictionary_id, item_key, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.deleteDictionaryItem,
                    showLoader: loaderVisibility,
                    data: {'dictionary_id': dictionary_id, 'item_key': item_key},
                    beforeSend: function (xhr) {
                        vcl.resetAllMessages();
                    }
                });
            },

            // Save Edge Dictionary item
            saveEdgeDictionaryItem: function (dictionary_id, item_key, item_value, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.createDictionaryItem,
                    showLoader: loaderVisibility,
                    data: {'dictionary_id': dictionary_id, 'item_key': item_key, 'item_value': item_value},
                    beforeSend: function (xhr) {
                        vcl.resetAllMessages();
                    }
                });
            },

            // Delete Acl entry item
            deleteAclItem: function (acl_id, acl_item_id, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.deleteAclItem,
                    showLoader: loaderVisibility,
                    data: {'acl_id': acl_id, 'acl_item_id': acl_item_id},
                    beforeSend: function (xhr) {
                        vcl.resetAllMessages();
                    }
                });
            },

            // Save Acl entry item
            saveAclItem: function (acl_id, item_value, negated_field, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.createAclItem,
                    showLoader: loaderVisibility,
                    data: {'acl_id': acl_id, 'item_value': item_value, 'negated_field': negated_field},
                    beforeSend: function (xhr) {
                        vcl.resetAllMessages();
                    }
                });
            },

            // Save Auth item
            saveAuthItem: function (item_key, item_value, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.createAuthItem,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version, 'auth_user': item_key, 'auth_pass': item_value},
                    beforeSend: function (xhr) {
                        vcl.resetAllMessages();
                    }
                });
            },

            // Delete Auth item
            deleteAuthItem: function (item_key_id, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.deleteAuthItem,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version, 'item_key_id': item_key_id},
                    beforeSend: function (xhr) {
                        vcl.resetAllMessages();
                    }
                });
            },

            // Setting up label text
            setActiveServiceLabel: function (active_version, next_version, service_name) {
                var msgWarning = $('.fastly-message-warning');
                msgWarning.text($.mage.__('You are about to clone service **' + service_name + '** active version') + ' ' + active_version + '. '
                    + $.mage.__('We\'ll make changes to version ') + ' ' + next_version + '.');
                msgWarning.show();
            },

            // Upload process
            submitVcl: function () {
                var activate_vcl_flag = false;

                if ($('#fastly_activate_vcl').is(':checked')) {
                    activate_vcl_flag = true;
                }

                $.ajax({
                    type: "POST",
                    url: config.vclUploadUrl,
                    data: {
                        'activate_flag': activate_vcl_flag,
                        'active_version': active_version
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            active_version = response.active_version;
                            vcl.modal.modal('closeModal');
                            successVclBtnMsg.text($.mage.__('VCL file is successfully uploaded to the Fastly service.')).show();
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        // error handling
                    }
                });
            },

            // Toggle image optimization process
            pushImageConfig: function (active_version) {
                var activate_image_flag = false;

                if ($('#fastly_activate_image_vcl').is(':checked')) {
                    activate_image_flag = true;
                }

                $.ajax({
                    type: "POST",
                    url: config.toggleImageSettingUrl,
                    data: {
                        'activate_flag': activate_image_flag,
                        'active_version': active_version
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            vcl.modal.modal('closeModal');
                            var onOrOff = 'removed';
                            var disabledOrEnabled = 'disabled';
                            if (imageOptimization == false) {
                                onOrOff = 'uploaded';
                                disabledOrEnabled = 'enabled';
                            } else {
                                onOrOff = 'removed';
                                disabledOrEnabled = 'disabled';
                            }
                            var fastlyIo = vcl.getFastlyIoSetting(false);
                            var ioStatus = false;

                            fastlyIo.done(function (checkIoSetting) {
                                if (checkIoSetting.status != false) {
                                    ioStatus = true;
                                }
                            });
                            successImageBtnMsg.text($.mage.__('The image optimization snippet has been successfully ' + onOrOff + '.')).show();
                            $('.request_imgopt_state_span').hide();
                            if (disabledOrEnabled == 'enabled') {
                                imageStateMsgSpan.find('#imgopt_state_disabled').hide();
                                imageStateMsgSpan.find('#imgopt_state_enabled').show();
                                if (ioStatus === true) {
                                    imgBtn.removeClass('disabled');
                                }
                            } else {
                                imageStateMsgSpan.find('#imgopt_state_enabled').hide();
                                imageStateMsgSpan.find('#imgopt_state_disabled').show();
                                if (ioStatus === false) {
                                    imgBtn.addClass('disabled');
                                }
                            }
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        // error handling
                    }
                });
            },

            // Toggle Tls process
            toggleTls: function (active_version) {
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
                        if (response.status == true) {
                            vcl.modal.modal('closeModal');
                            var onOrOff = 'off';
                            var disabledOrEnabled = 'disabled';
                            if (forceTls == false) {
                                onOrOff = 'on';
                                disabledOrEnabled = 'enabled';
                            } else {
                                onOrOff = 'off';
                                disabledOrEnabled = 'disabled';
                            }
                            successTlsBtnMsg.text($.mage.__('The Force TLS request setting is successfully turned ' + onOrOff + '.')).show();
                            $('.request_tls_state_span').hide();
                            if (disabledOrEnabled == 'enabled') {
                                requestStateMsgSpan.find('#force_tls_state_disabled').hide();
                                requestStateMsgSpan.find('#force_tls_state_enabled').show();
                            } else {
                                requestStateMsgSpan.find('#force_tls_state_enabled').hide();
                                requestStateMsgSpan.find('#force_tls_state_disabled').show();
                            }
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        // error handling
                    }
                });
            },

            // Toggle Blocking process
            toggleBlocking: function (active_version) {
                var activate_blocking_flag = false;

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
                        if (response.status == true) {
                            vcl.modal.modal('closeModal');
                            var onOrOff = 'OFF';
                            var disabledOrEnabled = 'disabled';
                            if (blocking == false) {
                                onOrOff = 'ON';
                                disabledOrEnabled = 'enabled';
                            } else {
                                onOrOff = 'OFF';
                                disabledOrEnabled = 'disabled';
                            }
                            successBlockingBtnMsg.text($.mage.__('The Blocking request setting is successfully turned ' + onOrOff + '.')).show();
                            $('.request_blocking_state_span').hide();
                            if (disabledOrEnabled == 'enabled') {
                                blockingStateMsgSpan.find('#blocking_state_disabled').hide();
                                blockingStateMsgSpan.find('#blocking_state_enabled').show();
                            } else {
                                blockingStateMsgSpan.find('#blocking_state_enabled').hide();
                                blockingStateMsgSpan.find('#blocking_state_disabled').show();
                            }
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        // error handling
                    }
                });
            },

            // Toggle Auth process
            toggleAuth: function (active_version) {
                var activate_auth_flag = false;

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
                        if (response.status == true) {
                            vcl.modal.modal('closeModal');
                            var onOrOff = 'off';
                            var disabledOrEnabled = 'disabled';
                            if (authStatus == false) {
                                onOrOff = 'on';
                                disabledOrEnabled = 'enabled';
                            } else {
                                onOrOff = 'off';
                                disabledOrEnabled = 'disabled';
                            }
                            successAuthBtnMsg.text($.mage.__('Basic Authentication is successfully turned ' + onOrOff + '.')).show();
                            $('.request_tls_state_span').hide();
                            if (disabledOrEnabled == 'enabled') {
                                authStateMsgSpan.find('#enable_auth_state_disabled').hide();
                                authStateMsgSpan.find('#enable_auth_state_enabled').show();
                            } else {
                                authStateMsgSpan.find('#enable_auth_state_enabled').hide();
                                authStateMsgSpan.find('#enable_auth_state_disabled').show();
                            }
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        // error handling
                    }
                });
            },

            // Reconfigure backend
            configureBackend: function () {
                var activate_backend = false;

                if ($('#fastly_activate_backend').is(':checked')) {
                    activate_backend = true;
                }

                $.ajax({
                    type: "POST",
                    url: config.configureBackendUrl,
                    data: {
                        'active_version': active_version,
                        'activate_flag': activate_backend,
                        'name': $('#backend_name').val(),
                        'shield': $('#backend_shield').val(),
                        'connect_timeout': $('#backend_connect_timeout').val(),
                        'between_bytes_timeout': $('#backend_between_bytes_timeout').val(),
                        'first_byte_timeout': $('#backend_first_byte_timeout').val()
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            $('#fastly-success-backend-button-msg').text($.mage.__('Backend is successfully updated.')).show();
                            active_version = response.active_version;
                            vcl.modal.modal('closeModal');
                            $('#fastly-backends-list').html('');
                            vcl.getBackends(response.active_version, false).done(function (backendsResp) {
                                $('.loading-backends').hide();
                                if (backendsResp.status != false) {
                                    if (backendsResp.backends.length > 0) {
                                        backends = backendsResp.backends;
                                        vcl.processBackends(backendsResp.backends);
                                    }
                                }
                            }).fail(function () {
                                $('#fastly-error-backend-button-msg').text($.mage.__('Error while updating the backend. Please, try again.')).show();
                            });
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        // error handling
                    }
                });
            },

            // Save Error Page Html
            saveErrorHtml: function () {
                var activate_vcl = false;

                if ($('#fastly_activate_vcl').is(':checked')) {
                    activate_vcl = true;
                }
                var errorHtmlChars = $('#error_page_html').val().length;
                var maxChars = 65535;
                if (errorHtmlChars >= maxChars) {
                    var msgWarning = $('.fastly-message-error');
                    msgWarning.text($.mage.__('The HTML must contain less than ' + maxChars + ' characters. Current number of characters: ' + errorHtmlChars));
                    msgWarning.show();
                    return;
                }
                $.ajax({
                    type: "POST",
                    url: config.saveErrorPageHtmlUrl,
                    data: {
                        'active_version': active_version,
                        'activate_flag': activate_vcl,
                        'html': $('#error_page_html').val()
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            successHtmlBtnMsg.text($.mage.__('Error page HTML is successfully updated.')).show();
                            active_version = response.active_version;
                            vcl.modal.modal('closeModal');
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        return errorHtmlBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            // CreateDictionary
            createDictionary: function () {
                var activate_vcl = false;

                if ($('#fastly_activate_vcl').is(':checked')) {
                    activate_vcl = true;
                }

                $.ajax({
                    type: "POST",
                    url: config.createDictionary,
                    data: {
                        'active_version': active_version,
                        'activate_flag': activate_vcl,
                        'dictionary_name': $('#dictionary_name').val()
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            successDictionaryBtnMsg.text($.mage.__('Dictionary is successfully created.')).show();
                            active_version = response.active_version;
                            // Fetch dictionaries
                            vcl.listDictionaries(active_version, false).done(function (dictResp) {
                                $('.loading-dictionaries').hide();
                                if (dictResp.status != false) {
                                    if (dictResp.status != false) {
                                        if (dictResp.dictionaries.length > 0) {
                                            dictionaries = dictResp.dictionaries;
                                            vcl.processDictionaries(dictResp.dictionaries);
                                            $('.no-dictionaries').hide();
                                        } else {
                                            $('.no-dictionaries').show();
                                        }
                                    }
                                }
                            }).fail(function () {
                                return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                            });
                            vcl.modal.modal('closeModal');
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            // delete dictionary
            deleteDictionary: function () {
                var activate_vcl = false;

                if ($('#fastly_activate_vcl').is(':checked')) {
                    activate_vcl = true;
                }

                var del_dictionary = $("input[name='dictionary").val();

                $.ajax({
                    type: "POST",
                    url: config.deleteDictionary,
                    data: {
                        'active_version': active_version,
                        'activate_flag': activate_vcl,
                        'dictionary': del_dictionary
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            successDictionaryBtnMsg.text($.mage.__('Dictionary successfully deleted.')).show();
                            active_version = response.active_version;
                            // Fetch dictionaries
                            vcl.listDictionaries(active_version, false).done(function (dictResp) {
                                $('.loading-dictionaries').hide();
                                if (dictResp.status != false) {
                                    if (dictResp.dictionaries.length > 0) {
                                        dictionaries = dictResp.dictionaries;
                                        vcl.processDictionaries(dictResp.dictionaries);
                                    } else {
                                        $('#fastly-dictionaries-list').html('');
                                        $('.no-dictionaries').show();
                                    }
                                }
                            }).fail(function () {
                                return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                            });
                            vcl.modal.modal('closeModal');
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            // delete acl
            deleteAcl: function () {
                var activate_vcl = false;

                if ($('#fastly_activate_vcl').is(':checked')) {
                    activate_vcl = true;
                }

                var del_acl = $("input[name='acl']").val();

                $.ajax({
                    type: "POST",
                    url: config.deleteAcl,
                    data: {
                        'active_version': active_version,
                        'activate_flag': activate_vcl,
                        'acl': del_acl
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            successAclBtnMsg.text($.mage.__('ACL successfully deleted.')).show();
                            active_version = response.active_version;
                            // Fetch dictionaries
                            vcl.listAcls(active_version, false).done(function (aclResp) {
                                $('.loading-acls').hide();
                                if (aclResp.status != false) {
                                    if (aclResp.acls.length > 0) {
                                        acls = aclResp.acls;
                                        vcl.processAcls(aclResp.acls);
                                    } else {
                                        $('#fastly-acls-list').html('');
                                        $('.no-acls').show();
                                    }
                                }
                            }).fail(function () {
                                return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                            });
                            vcl.modal.modal('closeModal');
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            // CreateDictionaryItems
            createDictionaryItems: function () {
                var activate_vcl = false;

                if ($('#fastly_activate_vcl').is(':checked')) {
                    activate_vcl = true;
                }

                var keys = [];
                $("input[type='text'][name^='key']").each(function () {
                    keys.push(this.value);
                });

                var values = [];
                $("input[type='text'][name^='value']").each(function () {
                    values.push(this.value);
                });

                $.ajax({
                    type: "POST",
                    url: config.createDictionaryItems,
                    data: {
                        'active_version': active_version,
                        'activate_flag': activate_vcl,
                        'dictionary_id': dictionary_id,
                        'values': values,
                        'keys': keys,
                        'old_items': dictionaryItems
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            successDictionaryBtnMsg.text($.mage.__('Dictionary items are successfully saved.')).show();
                            active_version = response.active_version;
                            vcl.modal.modal('closeModal');
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            showErrorMessage: function (msg) {
                var msgError = $('.fastly-message-error');
                msgError.html($.mage.__(msg));
                msgError.show();
                msgError.focus();
            },

            showSuccessMessage: function (msg) {
                var msgSuccess = $('.fastly-message-success');
                msgSuccess.html($.mage.__(msg));
                msgSuccess.show();
                msgSuccess.focus();
            },

            resetAllMessages: function () {
                var msgWarning = $('.fastly-message-warning');
                var msgError = $('.fastly-message-error');
                var msgSuccess = $('.fastly-message-success');

                // Modal window warning messages
                msgWarning.text();
                msgWarning.hide();

                // Modal windows error messages
                msgError.text();
                msgError.hide();

                // Modal windows success messages
                msgSuccess.text();
                msgSuccess.hide();

                // Vcl button messages
                successVclBtnMsg.hide();
                errorVclBtnMsg.hide();
                warningTlsBtnMsg.hide();

                // Image button messages
                successImageBtnMsg.hide();
                errorImageBtnMsg.hide();

                // Tls button messages
                successTlsBtnMsg.hide();
                errorTlsBtnMsg.hide();
                warningTlsBtnMsg.hide();

                // Blocking button messages
                successTlsBtnMsg.hide();
                errorTlsBtnMsg.hide();
                warningTlsBtnMsg.hide();

                // Error page button messages
                successHtmlBtnMsg.hide();
                errorHtmlBtnMsg.hide();
                warningHtmlBtnMsg.hide();


                // Edge button messages
                successDictionaryBtnMsg.hide();
                errorDictionaryBtnMsg.hide();

                // Acl button messages
                successAclBtnMsg.hide();
                errorAclBtnMsg.hide();

                // Auth messages
                successAuthBtnMsg.hide();
                errorAuthBtnMsg.hide();
                warningAuthBtnMsg.hide();

                deleteAuthBtnMsgError.hide();
                deleteAuthBtnMsgSuccess.hide();
            },

            // CreateAcl
            createAcl: function () {
                var activate_vcl = false;

                if ($('#fastly_activate_vcl').is(':checked')) {
                    activate_vcl = true;
                }

                $.ajax({
                    type: "POST",
                    url: config.createAcl,
                    data: {
                        'active_version': active_version,
                        'activate_flag': activate_vcl,
                        'acl_name': $('#acl_name').val()
                    },
                    showLoader: true,
                    success: function (response) {
                        if (response.status == true) {
                            successAclBtnMsg.text($.mage.__('Acl is successfully created.')).show();
                            active_version = response.active_version;
                            vcl.listAcls(active_version, false).done(function (aclResp) {
                                $('.loading-acls').hide();
                                if (aclResp.status != false) {
                                    if (aclResp.status != false) {
                                        if (aclResp.acls.length > 0) {
                                            acls = aclResp.acls;
                                            vcl.processAcls(aclResp.acls);
                                            $('.no-acls').hide();
                                        } else {
                                            $('.no-acls').show();
                                        }
                                    }
                                }
                            }).fail(function () {
                                return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                            });
                            vcl.modal.modal('closeModal');
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            // CreateAuth
            createAuth: function () {
                var activate_vcl = false;

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
                        if (response.status == true) {
                            authDictStatus = true;
                            successDictionaryBtnMsg.text($.mage.__('Authentication dictionary is successfully created.')).show();
                            active_version = response.active_version;
                            vcl.listAuths(active_version, false).done(function (authResp) {
                                $('.loading-dictionaries').hide();
                                if (authResp.status != false) {
                                    if (authResp.status === true) {
                                        if (authResp.auths.length > 0) {
                                            auths = authResp.auths;
                                            vcl.processAuths(authResp.auths);
                                        } else {
                                            $('.no-dictionaries').show();
                                        }
                                    } else if (authResp.status == 'empty') {
                                        auths = authResp.auths;
                                        vcl.processAuths([]);
                                    }
                                }
                            }).fail(function () {
                                return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                            });
                            vcl.modal.modal('closeModal');
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function (msg) {
                        return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            // Delete Authentication dictionary
            deleteMainAuth: function () {
                var activate_vcl = false;

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
                        if (response.status == true) {
                            // Change to Auth to disabled
                            successAuthBtnMsg.text($.mage.__('Basic Authentication is successfully turned off.')).show();
                            authStateMsgSpan.find('#enable_auth_state_enabled').hide();
                            authStateMsgSpan.find('#enable_auth_state_disabled').show();
                            active_version = response.active_version;
                            authDictStatus = false;
                            vcl.modal.modal('closeModal');
                            return deleteAuthBtnMsgSuccess.text($.mage.__('Authentication users removed.')).show();
                        } else {
                            if (response.not_exists == true) {
                                authDictStatus = false;
                            }
                            vcl.resetAllMessages();
                            vcl.modal.modal('closeModal');
                            return deleteAuthBtnMsgError.text($.mage.__(response.msg)).show();
                        }
                    },
                    error: function (msg) {
                        requestStateMsgSpan.find('#enable_auth_state_unknown').show();
                        return deleteAuthBtnMsgError.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            uploadVclConfig: {
                'fastly-uploadvcl-options': {
                    title: jQuery.mage.__('You are about to upload VCL to Fastly '),
                    content: function () {
                        return document.getElementById('fastly-uploadvcl-template').textContent;
                    },
                    actionOk: function () {
                        vcl.submitVcl(active_version);
                    }
                },
                'fastly-tls-options': {
                    title: jQuery.mage.__(''),
                    content: function () {
                        return document.getElementById('fastly-tls-template').textContent;
                    },
                    actionOk: function () {
                        vcl.toggleTls(active_version);
                    }
                },
                'fastly-image-options': {
                    title: jQuery.mage.__('Activate image optimization'),
                    content: function () {
                        return document.getElementById('fastly-image-template').textContent;
                    },
                    actionOk: function () {
                        vcl.pushImageConfig(active_version);
                    }
                },
                'fastly-blocking-options': {
                    title: jQuery.mage.__(''),
                    content: function () {
                        return document.getElementById('fastly-blocking-template').textContent;
                    },
                    actionOk: function () {
                        vcl.toggleBlocking(active_version);
                    }
                },
                'fastly-auth-options': {
                    title: jQuery.mage.__(''),
                    content: function () {
                        return document.getElementById('fastly-auth-template').textContent;
                    },
                    actionOk: function () {
                        vcl.toggleAuth(active_version);
                    }
                },
                'fastly-backend-options': {
                    title: jQuery.mage.__(''),
                    content: function () {
                        return document.getElementById('fastly-backend-template').textContent;
                    },
                    actionOk: function () {
                        if ($('#backend-upload-form').valid()) {
                            vcl.configureBackend(active_version);
                        }
                    }
                },
                'fastly-error-page-options': {
                    title: jQuery.mage.__('Update Error Page Content'),
                    content: function () {
                        return document.getElementById('fastly-error-page-template').textContent;
                    },
                    actionOk: function () {
                        vcl.saveErrorHtml(active_version);
                    }
                },
                'fastly-dictionary-container-options': {
                    title: jQuery.mage.__('Create dictionary container'),
                    content: function () {
                        return document.getElementById('fastly-dictionary-container-template').textContent;
                    },
                    actionOk: function () {
                        vcl.createDictionary(active_version);
                    }
                },
                'fastly-delete-dictionary-container-options': {
                    title: jQuery.mage.__('Delete dictionary containers'),
                    content: function () {
                        return document.getElementById('fastly-delete-dictionary-container-template').textContent;
                    },
                    actionOk: function () {
                        vcl.deleteDictionary(active_version);
                    }
                },
                'fastly-delete-acl-container-options': {
                    title: jQuery.mage.__('Delete ACL container'),
                    content: function () {
                        return document.getElementById('fastly-delete-acl-container-template').textContent;
                    },
                    actionOk: function () {
                        vcl.deleteAcl(active_version);
                    }
                },
                'fastly-acl-container-options': {
                    title: jQuery.mage.__('Create ACL container'),
                    content: function () {
                        return document.getElementById('fastly-acl-container-template').textContent;
                    },
                    actionOk: function () {
                        vcl.createAcl(active_version);
                    }
                },
                'fastly-auth-container-options': {
                    title: jQuery.mage.__('Create container for authenticated users'),
                    content: function () {
                        return document.getElementById('fastly-auth-container-template').textContent;
                    },
                    actionOk: function () {
                        vcl.createAuth(active_version);
                    }
                },
                'fastly-auth-container-delete': {
                    title: jQuery.mage.__('Delete all authenticated users'),
                    content: function () {
                        return document.getElementById('fastly-auth-delete-template').textContent;
                    },
                    actionOk: function () {
                        vcl.deleteMainAuth(active_version);
                    }
                },
                'fastly-edge-items': {
                    title: jQuery.mage.__('Dictionary items'),
                    content: function () {
                        return document.getElementById('fastly-edge-items-template').textContent;
                    },
                    actionOk: function () {
                    }
                },
                'fastly-acl-items': {
                    title: jQuery.mage.__('Acl items'),
                    content: function () {
                        return document.getElementById('fastly-acl-items-template').textContent;
                    },
                    actionOk: function () {
                    }
                },
                'fastly-auth-items': {
                    title: jQuery.mage.__('Basic Auth users'),
                    content: function () {
                        return document.getElementById('fastly-auth-items-template').textContent;
                    },
                    actionOk: function () {
                    }
                }
            }
        };
    };
});
