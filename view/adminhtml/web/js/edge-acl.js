define([
    "jquery",
    "setServiceLabel",
    "popup",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function ($, setServiceLabel, popup, resetAllMessages, showErrorMessage, showSuccessMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* ACL button messages */
        let successAclBtnMsg = $('#fastly-success-acl-button-msg');
        let errorAclBtnMsg = $('#fastly-error-acl-button-msg');
        let warningAclBtnMsg = $('#fastly-warning-acl-button-msg');

        /* ACL negated checkbox title */
        let acl_negated_title = "Negates another ACL entry.\n\n" +
            "Example: If you have a purge_allow_acl that has "+
            "192.168.1.0/24 but you add negated IP : 192.168.1.4, "+
            "it means every IP in 192.168.1.0/24 range has access except for 192.168.1.4.";

        let itemsHtml;
        let aclItems;
        let acl_id;
        let acls;

        let active_version = serviceStatus.active_version;

        let aclContainerOptions = {
            title: jQuery.mage.__('Create ACL container'),
            content: function () {
                return document.getElementById('fastly-acl-container-template').textContent;
            },
            actionOk: function () {
                createAcl(active_version);
            }
        };

        let aclItemOptions = {
            title: jQuery.mage.__('ACL items'),
            content: function () {
                return document.getElementById('fastly-acl-items-template').textContent;
            },
            actionOk: function () {
            }
        };

        let aclDeleteContainerOptions = {
            title: jQuery.mage.__('Delete ACL containers'),
            content: function () {
                return document.getElementById('fastly-delete-acl-container-template').textContent;
            },
            actionOk: function () {
                deleteAcl(active_version);
            }
        };

        listAcls(active_version, false).done(function (response) {
            $('.loading-acls').hide();
            if (response.status !== false) {
                if (response.status !== false) {
                    if (response.acls.length > 0) {
                        acls = response.acls;
                        processAcls(response.acls);
                    } else {
                        $('.no-acls').show();
                    }
                }
            }
        }).fail(function () {
            return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
        });

        function createAcl()
        {
            let activate_vcl = false;

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
                    if (response.status === true) {
                        successAclBtnMsg.text($.mage.__('ACL is successfully created.')).show();
                        active_version = response.active_version;
                        // Fetch acls
                        listAcls(active_version, false).done(function (list) {
                            $('.loading-acls').hide();
                            if (list.status !== false) {
                                if (list.status !== false) {
                                    if (list.acls.length > 0) {
                                        acls = list.acls;
                                        processAcls(list.acls);
                                        $('.no-acls').hide();
                                    } else {
                                        $('.no-acls').show();
                                    }
                                }
                            }
                        }).fail(function () {
                            return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                        });
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        // Process ACLs
        function processAcls(acls)
        {
            let html = '';
            $.each(acls, function (index, acl) {
                html += "<tr id='fastly_acl_" + index + "'>";
                html += "<td><input data-aclId='"+ acl.id + "' id='acl_" + index + "' value='"+ acl.name +"' disabled='disabled' class='input-text' type='text'></td>";
                html += "<td class='col-actions'>" +
                    "<button class='action-delete fastly-edit-acl-icon' data-acl-id='" + acl.id + "' data-acl-name='" + acl.name + "'id='fastly-edit-acl_"+ index + "' title='Edit ACL' type='button'>" +
                    "<button class='action-delete fastly-delete-acl-icon' data-acl-id='" + acl.name + "' id='fastly-delete-acl_"+ index + "' title='Delete ACL' type='button'>" +
                    "</td></tr>";
            });
            if (html !== '') {
                $('.no-acls').hide();
            }
            $('#fastly-acls-list').html(html);
        }

        // Queries Fastly API to retrieve ACLs
        function listAcls(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.getAcls,
                showLoader: loaderVisibility,
                data: {'active_version': active_version},
                beforeSend: function () {
                    $('.loading-acls').show();
                }
            });
        }

        // Save Acl entry item
        function saveEdgeAclItem(acl_id, item_value, negated_field, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.createAclItem,
                showLoader: loaderVisibility,
                data: {'acl_id': acl_id, 'item_value': item_value, 'negated_field': negated_field},
                beforeSend: function () {
                    resetAllMessages();
                }
            });
        }

        // delete ACL
        function deleteAcl()
        {
            let activate_vcl = false;

            if ($('#fastly_activate_vcl').is(':checked')) {
                activate_vcl = true;
            }

            let del_acl = $("input[name='acl']").val();

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
                    if (response.status === true) {
                        successAclBtnMsg.text($.mage.__('ACL successfully deleted.')).show();
                        active_version = response.active_version;
                        // Fetch ACLs
                        listAcls(active_version, false).done(function (aclResp) {
                            $('.loading-acls').hide();
                            if (aclResp.status !== false) {
                                if (aclResp.acls.length > 0) {
                                    acls = aclResp.acls;
                                    processAcls(aclResp.acls);
                                } else {
                                    $('#fastly-acls-list').html('');
                                    $('.no-acls').show();
                                }
                            }
                        }).fail(function () {
                            return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                        });
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        // Delete Edge ACL item
        function deleteEdgeAclItem(acl_id, acl_item_id, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.deleteAclItem,
                showLoader: loaderVisibility,
                data: {'acl_id': acl_id, 'acl_item_id': acl_item_id},
                beforeSend: function () {
                    resetAllMessages();
                }
            });
        }

        $('#add-acl-container-button').on('click', function () {
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
                    return errorAclBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                popup(aclContainerOptions);
                setServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorAclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        $('body').on('click', 'button.fastly-edit-acl-icon', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (checkService) {
                active_version = checkService.active_version;
                let next_version = checkService.next_version;
                let service_name = checkService.service.name;
                setServiceLabel(active_version, next_version, service_name);
            });

            acl_id = $(this).data('acl-id');
            let acl_name = $(this).data('acl-name');

            // Handle ACLs
            if (acls != null && acl_id != null) {
                $.ajax({
                    type: "POST",
                    url: config.getAclItems,
                    showLoader: true,
                    data: {'acl_id': acl_id}
                }).done(function (response) {
                    if (response.status !== false) {
                        aclItems = response.aclItems;
                        itemsHtml = '';
                        if (response.aclItems.length > 0) {
                            $.each(response.aclItems, function (index, item) {
                                let negated = item.negated === 1 ? ' checked' : '';
                                let ip_output;
                                if (item.subnet) {
                                    ip_output = item.ip + '/' + item.subnet;
                                } else {
                                    ip_output = item.ip;
                                }
                                itemsHtml += '<tr><td>' +
                                    '<input name="value" data-type="acl" data-id="'+ item.id +'" value="'+ ip_output +'" class="input-text admin__control-text acl-items-field" type="text" disabled></td>' +
                                    '<td><div class="admin__field-option" title="'+ acl_negated_title +'"><input name="negated" class="admin__control-checkbox" type="checkbox" id="acl_entry_'+ item.id +'"'+negated+' disabled><label class="admin__field-label" for="acl_entry_'+ item.id +'"></label></div></td>' +
                                    '<td class="col-actions">' +
                                    '<button class="action-delete remove_acl_item"  title="Delete" type="button"><span>Delete</span></button>' +
                                    '</td></tr>';
                            });
                        } else {
                            itemsHtml += '<tr><td>' +
                                '<input name="value" data-type="acl" data-id="" value="" class="input-text admin__control-text acl-items-field" type="text"></td>' +
                                '<td><div class="admin__field-option" title=""><input name="negated" class="admin__control-checkbox" type="checkbox" id=""><label class="admin__field-label" for=""></label></div></td>' +
                                '<td class="col-actions">' +
                                '<button class="action-delete fastly-save-action save_acl_item" title="Save" type="button"><span>Save</span></button>' +
                                '</td></tr>';
                        }
                    }

                    popup(aclItemOptions);
                    $('.modal-title').text($.mage.__('"'+ acl_name +'" ACL container items'));
                    $('.upload-button').remove();

                    $('#acl-items-table > tbody').html(itemsHtml);
                });
            }
        });

        $('body').on('click', '#add-acl-item', function (e) {
            let aclTimestamp = Math.round(e.timeStamp);
            $('#acl-items-table > tbody').append('<tr>' +
                '<td><input name="value" data-type="acl" data-id="" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                '<td><div class="admin__field-option" title="'+acl_negated_title+'"><input name="negated" class="admin__control-checkbox" type="checkbox" id="acl_entry_'+ aclTimestamp +'"><label class="admin__field-label" for="acl_entry_'+ aclTimestamp +'"></label></div></td>' +
                '<td class="col-actions">' +
                '<button class="action-delete fastly-save-action save_acl_item" title="Save" type="button"><span>Save</span></button>' +
                '</td></tr>');
        });

        $('body').on('click', '.save_acl_item', function () {
            let valueField = $(this).closest('tr').find("input[name='value']");
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

            let negated_field = $(this).closest('tr').find("input[name='negated']")[0].checked ? 1 : 0;
            saveEdgeAclItem(acl_id, item_value, negated_field, true).done(function (response) {
                if (response.status === true) {
                    $(self).closest('tr').find("input[name='value']").prop('disabled', true);
                    let newElement = $(self).closest('tr').find("input[name='value']")[0];
                    newElement.setAttribute('data-id', response.id);

                    showSuccessMessage($.mage.__('Acl item is successfully saved.'));
                } else {
                    showErrorMessage(response.msg);
                }
            }).fail(function () {
                showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
            });
        });

        $('body').on('click', '.remove_acl_item', function () {
            let valueField = $(this).closest('tr').find("input[name='value']");
            let acl_item_id = valueField.data('id');
            let self = this;

            if (confirm("Are you sure you want to delete this item?")) {
                deleteEdgeAclItem(acl_id, acl_item_id, true).done(function (response) {
                    if (response.status === true) {
                        $(self).closest('tr').remove();
                        showSuccessMessage($.mage.__('ACL item is successfully deleted.'));
                    }
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });
            }
        });

        // delete ACL container button
        $('body').on('click', 'button.fastly-delete-acl-icon', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (checkService) {
                active_version = checkService.active_version;
                let next_version = checkService.next_version;
                let service_name = checkService.service.name;
                setServiceLabel(active_version, next_version, service_name);
            });

            acl_id = $(this).data('acl-id');

            if (acls != null && acl_id != null) {
                let aclHtml = '<input name="acl" value="' + acl_id + '" class="input-text admin__control-text acl-field" type="hidden" disabled>';
                popup(aclDeleteContainerOptions);
                let containerWarning = $('#fastly-container-warning');
                $('.modal-title').text($.mage.__('Delete "'+ acl_id +'" ACL container'));
                containerWarning.text($.mage.__('You are about to delete the "' + acl_id + '" ACL container.'));
                containerWarning.show();
                if (aclHtml !== '') {
                    $('#delete-acl-container').html(aclHtml);
                }
            }
        });

    }
});