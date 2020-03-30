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
        let successButtonMsg = $("#fastly-success-import-button-msg");
        let errorButtonMsg = $("#fastly-error-import-button-msg");
        let warningButtonMsg = $("#fastly-warning-import-button-msg");

        let active_version = serviceStatus.active_version;

        let importOptions = {
            id: 'fastly-import-options',
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-import-template').textContent;
            },
            actionOk: function () {
                fastlyImport(active_version);
            }
        };

        $('#fastly_import').on('click', function () {
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
                    return errorButtonMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;

                getImportData(active_version, true).done(function (response) {
                    overlay(importOptions);
                    $('.modal-title').text($.mage.__('Import Edge ACLs, Edge Dictionaries, Active Edge Modules and Custom Snippets'));
                    $('.upload-button span').text('Import');
                    let html = '';

                    let acls = [];
                    let aclDetails = []
                    for (const name in response.acls) {
                        if (response.acls.hasOwnProperty(name)) {
                            acls.push({id: name, name: name});
                            aclDetails.push({
                                id: name,
                                data: response.acls[name].items.map((item) => {
                                    return importExportRenderer.stringifyAclDetail(null, item);
                                })
                            })
                        }
                    }

                    html += importExportRenderer.renderEdgeAcls(acls, false);
                    for (const detail of aclDetails) {
                        html += importExportRenderer.renderDetails(detail.id, detail.data)
                    }

                    let dictionaries = [];
                    let dictionaryDetails = []
                    for (const name in response.dictionaries) {
                        if (response.dictionaries.hasOwnProperty(name)) {
                            dictionaries.push({id: name, name: name});
                            dictionaryDetails.push({
                                id: name,
                                data: response.dictionaries[name].items.map((item) => {
                                    return importExportRenderer.stringifyDictionaryDetail(null, item);
                                })
                            })
                        }
                    }

                    html += importExportRenderer.renderEdgeDisctionaries(dictionaries, false);
                    for (const detail of dictionaryDetails) {
                        html += importExportRenderer.renderDetails(detail.id, detail.data)
                    }

                    html += importExportRenderer.renderCustomSnippets(response.custom_snippets);

                    let edgeModules = [];
                    for (const name in response.active_modules) {
                        if (response.active_modules.hasOwnProperty(name)) {
                            let values = response.active_modules[name].manifest_values;
                            edgeModules.push({
                                manifest_id: name,
                                manifest_name: response.active_modules[name].manifest_content.name,
                                manifest_description: response.active_modules[name].manifest_content.description,
                                values: (Array.isArray(values)) ? values.pop() : {},
                            });
                        }
                    }
                    html += importExportRenderer.renderActiveEdgeModules(edgeModules);

                    $('.question').html(html);
                });
            });
        });

        function getImportData(active_version, loaderVisibility)
        {
            let fileInput = document.getElementById('system_full_page_cache_fastly_fastly_tools_import_file');
            let file = fileInput.files[0];
            let formData = new FormData();
            formData.append('file', file);
            formData.append('form_key', window.FORM_KEY)
            return $.ajax({
                type: "POST",
                url: config.getImportDataUrl,
                showLoader: loaderVisibility,
                data: formData,
                processData: false,
                contentType: false
            });
        }

        function fastlyImport()
        {
            let formData = new FormData(document.getElementById('fastly-import-form'));
            let fileInput = document.getElementById('system_full_page_cache_fastly_fastly_tools_import_file');
            let file = fileInput.files[0];
            formData.append('file', file);
            formData.append('form_key', window.FORM_KEY);
            formData.append('active_version', active_version);

            return $.ajax({
                type: "POST",
                url: config.importDataUrl,
                showLoader: true,
                data: formData,
                processData: false,
                contentType: false
            }).done(function (response) {
                if (response.status) {
                    modal.modal('closeModal');
                    return successButtonMsg.text($.mage.__('Successfully imported.')).show();
                } else {
                    resetAllMessages();
                    showErrorMessage(response.msg);
                }
            });
        }
    }
});