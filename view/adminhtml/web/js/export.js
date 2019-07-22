define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {
        /* Export button messages */
        let successExportBtnMsg = $('#fastly-success-export-button-msg');
        let errorExportBtnMsg = $('#fastly-error-export-button-msg');

        let active_version = serviceStatus.active_version;
        let customSnippets;
        let acls;
        let dictionaries;
        let activeModules;
        let adminTimeout = $("#system_full_page_cache_fastly_fastly_advanced_configuration_admin_path_timeout").val();


        let exportOptions = {
            id: 'fastly-export-options',
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-export-template').textContent;
            },
            actionOk: function () {
                fastlyExport(active_version);
            }
        };

        $('#fastly_export').on('click', function () {
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
                    return errorExportBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;

                getExportData(active_version, true).done(function (response) {
                    overlay(exportOptions);
                    $('.modal-title').text($.mage.__('Export Edge ACLs, Edge Dictionaries, Active Edge Modules and Custom Snippets'));
                    $('.upload-button span').text('Export');
                    let html = '';
                    customSnippets = response.custom_snippets;
                    dictionaries = response.dictionaries;
                    acls = response.acls;
                    activeModules = response.active_modules;

                    html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                    html += '<label class="admin__field-label"><b>Edge ACLs</b></label></div></div>';
                    $.each(acls, function (index, acl) {
                        html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                        html += '<div class="admin__field-option admin__control-table"><input class="admin__control-checkbox export-checkbox export-acl" type="checkbox" name="' + acl.name + '" id="' + acl.id + '" checked/>';
                        html += '<label class="admin__field-label"></label><label for="' + acl.id + '">' + acl.name + '</label>';
                        html += '<button class="action-delete export-list-icon acl-items-btn" title="Show Items" type="button"></div></div></div>';
                    });
                    if (acls === undefined || acls.length == 0) {
                        html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                        html += '<div class="admin__field-option admin__control-table">';
                        html += '<label class="admin__field-label"></label><label>There are no Edge ACLs</label></div></div></div>';
                    }

                    html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                    html += '<label class="admin__field-label"><b>Edge Dictionaries</b></label></div></div>';
                    $.each(dictionaries, function (index, dictionary) {
                        html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                        if (dictionary.name.split('_', 1)[0] === 'magentomodule') {
                            html += '<div class="admin__field-option admin__control-table"><input class="admin__control-checkbox export-checkbox export-dictionary" type="checkbox" name="' + dictionary.name + '" id="' + dictionary.id + '"/>';
                        } else {
                            html += '<div class="admin__field-option admin__control-table"><input class="admin__control-checkbox export-checkbox export-dictionary" type="checkbox" name="' + dictionary.name + '" id="' + dictionary.id + '" checked/>';
                        }
                        html += '<label class="admin__field-label"></label><label for="' + dictionary.id + '">' + dictionary.name + '</label>';
                        html += '<button class="action-delete export-list-icon dictionary-items-btn" title="Show Items" type="button"></div></div></div>';
                    });
                    if (dictionaries === undefined || dictionaries.length == 0) {
                        html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                        html += '<div class="admin__field-option admin__control-table">';
                        html += '<label class="admin__field-label"></label><label>There are no Edge Dictionaries</label></div></div></div>';
                    }

                    html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                    html += '<label class="admin__field-label"><b>Custom Snippets</b></label></div></div>';
                    $.each(customSnippets, function (index, content) {
                        html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                        html += '<div class="admin__field-option admin__control-table"><input class="admin__control-checkbox export-checkbox export-custom-snippet" type="checkbox" name="' + index + '" checked/>';
                        html += '<label class="admin__field-label"></label><label for="' + index + '">' + index + '</label></div></div></div>';
                        html += '<div class="admin__field field"><div class="admin__field-note export-note" id="' + index + '">' + content;
                        html += '</div></div>';
                    });
                    if (customSnippets === undefined || customSnippets.length == 0) {
                        html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                        html += '<div class="admin__field-option admin__control-table">';
                        html += '<label class="admin__field-label"></label><label>There are no Custom Snippets</label></div></div></div>';
                    }

                    html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                    html += '<label class="admin__field-label"><b>Active Edge Modules</b></label></div></div>';
                    $.each(activeModules, function (index, edgeModule) {
                        html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                        html += '<div class="admin__field-option admin__control-table"><input class="admin__control-checkbox export-checkbox export-active-modules" type="checkbox" name="' + edgeModule.manifest_name + '" id="' + edgeModule.manifest_id + '" checked/>';
                        html += '<label class="admin__field-label"></label><label for="' + edgeModule.id + '">' + edgeModule.manifest_name + '</label></div></div></div>';
                        html += '<div class="admin__field field"><div class="admin__field-note export-note" id="' + index + '">' + edgeModule.manifest_description;
                        html += '</div></div>';
                    });
                    if (activeModules === undefined || activeModules.length == 0) {
                        html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                        html += '<div class="admin__field-option admin__control-table">';
                        html += '<label class="admin__field-label"></label><label>There are no Active Modules</label></div></div></div>';
                    }

                    html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                    html += '<label class="admin__field-label"><b>Advanced Configuration</b></label></div></div>';
                    html += '<div class="admin__field field"><div class="admin__field-control export-field">';
                    html += '<div class="admin__field-option admin__control-table"><input class="admin__control-checkbox export-checkbox export-admin-timeout" type="checkbox" name="admin-timeout" id="admin-timeout" checked/>';
                    html += '<label class="admin__field-label"></label><label for="admin-timeout">Admin Path Timeout</label></div></div></div>';
                    html += '<div class="admin__field field"><div class="admin__field-note export-note">' + adminTimeout + 's';
                    html += '</div></div>';

                    $('.question').html(html);
                });
            });
        });

        function getExportData(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.getExportDataUrl,
                showLoader: loaderVisibility
            });
        }

        function fastlyExport()
        {
            let checkedAcls = {};
            $('.export-acl:checked').each(function () {
                let aclId = $(this).attr('id');
                $.each(acls, function (index, acl) {
                    if (aclId === acl.id) {
                        checkedAcls[acl.id] = acl.name
                    }
                });
            });

            let checkedDictionaries = {};
            $('.export-dictionary:checked').each(function () {
                let dictionaryId = $(this).attr('id');
                $.each(dictionaries, function (index, dictionary) {
                    if (dictionaryId === dictionary.id) {
                        checkedDictionaries[dictionary.id] = dictionary.name
                    }
                });
            });

            let checkedCustomSnippets = {};
            $('.export-custom-snippet:checked').each(function () {
                let snippetName = $(this).attr('name');
                $.each(customSnippets, function (index, content) {
                    if (snippetName === index) {
                        checkedCustomSnippets[index] = content;
                    }
                });
            });

            let checkedActiveModules = {};
            $('.export-active-modules:checked').each(function () {
                let module_id = $(this).attr('id');
                $.each(activeModules, function (index, content) {
                    if (module_id === content.manifest_id) {
                        checkedActiveModules[index] = content;
                    }
                });
            });

            let adminTimeoutPath;
            if ($('.export-admin-timeout').prop('checked')) {
                adminTimeoutPath = adminTimeout;
            }


            if ($.isEmptyObject(checkedAcls) && $.isEmptyObject(checkedDictionaries) && $.isEmptyObject(checkedCustomSnippets
                && $.isEmptyObject(checkedActiveModules))) {
                resetAllMessages();
                showErrorMessage('At least one item must be selected.');
                return;
            }
            $.ajax({
                type: "POST",
                url: config.exportDataUrl,
                showLoader: true,
                data: {
                    'acls': checkedAcls,
                    'dictionaries': checkedDictionaries,
                    'custom_snippets': checkedCustomSnippets,
                    'active_modules': checkedActiveModules,
                    'admin_timeout': adminTimeoutPath
                }
            }).done(function (response) {
                if (response !== false) {
                    $('#fastly-export-form').submit();
                    modal.modal('closeModal');
                    return successExportBtnMsg.text($.mage.__('Successfully exported.')).show();
                } else {
                    resetAllMessages();
                    showErrorMessage(response.msg);
                }
            });
        }

        $('body').on('click', '.acl-items-btn', function () {
            let acl_id = $(this).closest('.admin__field-option').find(".admin__control-checkbox").attr('id');
            let acl_field = $(this).parents('div.field');
            $.ajax({
                type: "POST",
                url: config.getAclItems,
                showLoader: true,
                data: {'acl_id': acl_id}
            }).done(function (response) {
                if (response.status !== false) {
                    let itemsHtml = '';
                    if (response.aclItems.length > 0) {
                        $.each(response.aclItems, function (index, item) {
                            let ip_output;
                            if (item.subnet) {
                                ip_output = item.ip + '/' + item.subnet;
                            } else {
                                ip_output = item.ip;
                            }
                            if (item.negated === '1') {
                                ip_output = '!' + ip_output;
                            }
                            let created_at = new Date(item.created_at);
                            itemsHtml += '<div class="admin__field field '+acl_id+'"><div class="admin__field-note export-note">' + ip_output;
                            if (item.comment !== "") {
                                itemsHtml += ' (' + item.comment + ')';
                            }
                            itemsHtml += ' ' + created_at.toUTCString();
                            itemsHtml += '</div></div>';
                        });
                        $('.'+acl_id).remove();
                        acl_field.after(itemsHtml);
                        return;
                    } else {
                        itemsHtml += '<div class="admin__field field export-acl-note '+acl_id+'"><div class="admin__field-note export-note">no items</div></div>';
                        $('.'+acl_id).remove();
                        acl_field.after(itemsHtml);
                        return;
                    }
                }

                modal.modal('closeModal');
                return errorExportBtnMsg.text($.mage.__(response.msg)).show();
            });
        });

        $('body').on('click', '.dictionary-items-btn', function () {
            let dictionary_id = $(this).closest('.admin__field-option').find(".admin__control-checkbox").attr('id');
            let dictionary_field = $(this).parents('div.field');
            $.ajax({
                type: "POST",
                url: config.getDictionaryItems,
                showLoader: true,
                data: {'dictionary_id': dictionary_id}
            }).done(function (response) {
                if (response.status !== false) {
                    let itemsHtml = '<div class="admin__field field export-dictionary-items '+dictionary_id+'">';
                    if (response.dictionaryItems.length > 0) {
                        $.each(response.dictionaryItems, function (index, item) {
                            itemsHtml += '<div class="admin__field field"><div class="admin__field-note export-note">' + item.item_key;
                            itemsHtml += ' (' + item.item_value + ')';
                            itemsHtml += '</div></div>';
                        });
                        $('.'+dictionary_id).remove();
                        dictionary_field.after(itemsHtml);
                        return;
                    } else {
                        itemsHtml += '<div class="admin__field field export-dictionary-note"><div class="admin__field-note export-note">no items</div></div>';
                        $('.'+dictionary_id).remove();
                        dictionary_field.after(itemsHtml);
                        return;
                    }
                }

                modal.modal('closeModal');
                return errorExportBtnMsg.text($.mage.__(response.msg)).show();
            });
        });
    }
});