define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate',
    'importExportRenderer'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, translate, importExportRenderer) {
    return function (config, serviceStatus, isAlreadyConfigured) {
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

                    html += importExportRenderer.renderEdgeAcls(acls);

                    html += importExportRenderer.renderEdgeDisctionaries(dictionaries);

                    html += importExportRenderer.renderCustomSnippets(customSnippets);

                    html += importExportRenderer.renderActiveEdgeModules(activeModules);

                    html += `<div class="admin__field field">
                        <div class="admin__field-control export-field">
                            <label class="admin__field-label"><b>Advanced Configuration</b></label>
                        </div>
                    </div>`;

                    html += importExportRenderer.renderAdminPathTimeout(adminTimeout);

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
                let snippetName = $(this).attr('id');
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
                    let acls = [];
                    $.each(response.aclItems, function (index, item) {
                        acls.push(importExportRenderer.stringifyAclDetail(index, item));
                    });
                    $('.'+acl_id).remove();
                    let itemsHtml = importExportRenderer.renderDetails(acl_id, acls)
                    acl_field.after(itemsHtml);
                    return;
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
                    let dictionaries = [];
                    $.each(response.dictionaryItems, function (index, item) {
                        dictionaries.push(importExportRenderer.stringifyDictionaryDetail(index, item));
                    });
                    $('.'+dictionary_id).remove();
                    let itemsHtml = importExportRenderer.renderDetails(dictionary_id, dictionaries)
                    dictionary_field.after(itemsHtml);
                    return;
                }

                modal.modal('closeModal');
                return errorExportBtnMsg.text($.mage.__(response.msg)).show();
            });
        });
    }
});