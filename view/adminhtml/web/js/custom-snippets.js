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
        /* Custom snippet button messages */
        let successCustomSnippetBtnMsg = $('#fastly-success-custom-snippet-button-msg');
        let errorCustomSnippetBtnMsg = $('#fastly-error-custom-snippet-button-msg');
        let warningCUstomSnippetBtnMsg = $('#fastly-warning-custom-snippet-button-msg');

        let active_version = serviceStatus.active_version;
        let snippets;

        let customSnippetOptions = {
            title: jQuery.mage.__('You are about to create a custom snippet '),
                content: function () {
                return document.getElementById('fastly-custom-snippet-template').textContent;
            },
            actionOk: function () {
                setCustomSnippet();
            }
        };

        let customSnippetEditOptions = {
            title: jQuery.mage.__('You are about to edit a custom snippet '),
                content: function () {
                return document.getElementById('fastly-custom-snippet-edit-template').textContent;
            },
            actionOk: function () {
                updateCustomSnippet();
            }
        };

        getCustomSnippets(false).done(function (response) {
            $('.loading-snippets').hide();
            if (response.status !== false) {
                if (response.snippets.length > 0) {
                    snippets = response.snippets;
                    processCustomSnippets(snippets);
                } else {
                    $('.no-snippets').show();
                }
            }
        });

        // custom snippet creation
        function setCustomSnippet()
        {
            let custom_name = $('#custom_snippet_name').val();
            let custom_type = $('#custom_snippet_type').val();
            let custom_priority = $('#custom_snippet_priority').val();
            let custom_vcl = $('#custom_snippet_content').val();
            let msgWarning = $('.fastly-message-error');

            if (!custom_name || !custom_type || !custom_priority || !custom_vcl) {
                msgWarning.text($.mage.__('Please fill out the required fields.')).show();
                return;
            }

            $.ajax({
                type: "POST",
                url: config.createCustomSnippetUrl,
                data: {
                    'name': custom_name,
                    'type': custom_type,
                    'priority': custom_priority,
                    'vcl': custom_vcl,
                    'edit': false
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        active_version = response.active_version;
                        modal.modal('closeModal');
                        successCustomSnippetBtnMsg.text($.mage.__('Custom snippet successfully created.')).show();
                        getCustomSnippets().done(function (snippetsResp) {
                            $('.loading-snippets').hide();
                            if (snippetsResp.status !== false) {
                                if (snippetsResp.snippets.length > 0) {
                                    snippets = snippetsResp.snippets;
                                    processCustomSnippets(snippets);
                                } else {
                                    $('.no-snippets').show();
                                }
                            }
                        });
                    } else {
                        msgWarning.text($.mage.__(response.msg)).show();
                    }
                },
                error: function () {
                    return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        // Retrieve custom snippets
        function getCustomSnippets()
        {
            return $.ajax({
                type: "GET",
                url: config.getCustomSnippetsUrl,
                showLoader: false
            });
        }

        // Process custom snippets
        function processCustomSnippets(snippets)
        {
            let html = '';
            $.each(snippets, function (index, snippet) {
                html += "<tr id='fastly_" + index + "'>";
                html += "<td><input data-snippetId='"+ index + "' id='snippet_" + index + "' value='"+ snippet +"' disabled='disabled' class='input-text' type='text'></td>";
                html += "<td class='col-actions'><button class='action-delete fastly-edit-snippet-icon' data-snippet-id='" + snippet + "' id='fastly-edit-snippet"+ index + "' title='Edit custom snippet' type='button'></button>";
                html += "<span>&nbsp;&nbsp;</span><button class='action-delete fastly-delete-snippet-icon' data-snippet-id='" + snippet + "' id='fastly-delete-snippet"+ index + "' title='Delete custom snippet' type='button'></button></td></tr>"
            });
            if (html !== '') {
                $('.no-snippets').hide();
            }
            $('#fastly-snippets-list').html(html);
        }

        function deleteCustomSnippet(snippet_id)
        {
            return $.ajax({
                type: "GET",
                url: config.deleteCustomSnippet,
                showLoader: true,
                data: {'snippet_id': snippet_id},
                beforeSend: function () {
                    resetAllMessages();
                }
            });
        }

        function updateCustomSnippet()
        {
            let custom_name = $('#custom_snippet_name').val();
            let original_name = $('#original_snippet_name').val();
            let custom_type = $('#custom_snippet_type').val();
            let custom_priority = $('#custom_snippet_priority').val();
            let custom_vcl = $('#custom_snippet_content').val();
            let msgWarning = $('.fastly-message-error');

            if (!custom_name || !custom_type || !custom_priority || !custom_vcl) {
                msgWarning.text($.mage.__('Please fill out the required fields.')).show();
                return;
            }

            $.ajax({
                type: "POST",
                url: config.createCustomSnippetUrl,
                data: {
                    'name': custom_name,
                    'type': custom_type,
                    'priority': custom_priority,
                    'vcl': custom_vcl,
                    'edit': true,
                    'original': original_name
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        active_version = response.active_version;
                        modal.modal('closeModal');
                        successCustomSnippetBtnMsg.text($.mage.__('Custom snippet successfully updated.')).show();
                        getCustomSnippets(false).done(function (snippetsResp) {
                            $('.loading-snippets').hide();
                            if (snippetsResp.status !== false) {
                                if (snippetsResp.snippets.length > 0) {
                                    let snippets = snippetsResp.snippets;
                                    processCustomSnippets(snippets);
                                } else {
                                    $('.no-snippets').show();
                                }
                            }
                        })
                    } else {
                        msgWarning.text($.mage.__(response.msg)).show();
                    }
                },
                error: function () {
                    return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        /**
         * Custom snippet upload
         */
        $('#fastly_custom_vcl_button').on('click', function () {
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
                    return errorCustomSnippetBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;
                setServiceLabel(active_version, next_version, service_name);
                popup(customSnippetOptions);
                $('.upload-button span').text('Create');
            }).fail(function () {
                return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        $('body').on('click', 'button.fastly-delete-snippet-icon', function () {
            let snippet_id = $(this).data('snippet-id');
            let closestTr = $(this).closest('tr');
            if (confirm("Are you sure you want to delete "+ snippet_id +"?")) {
                deleteCustomSnippet(snippet_id).done(function (response) {
                    if (response.status === true) {
                        closestTr.remove();
                        $('#fastly-success-snippet-button-msg').text($.mage.__('Custom snippet successfully deleted.')).show();
                    }
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });
            }
        });

        $('body').on('click', 'button.fastly-edit-snippet-icon', function () {
            let snippet_id = $(this).data('snippet-id');
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
                    return errorCustomSnippetBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;
                setServiceLabel(active_version, next_version, service_name);
                $.when(
                    $.ajax({
                        type: "GET",
                        url: config.getCustomSnippet,
                        data: {'snippet_id': snippet_id},
                        showLoader: true,
                    })
                ).done(function (response) {
                    if (response.status === true) {
                        popup(customSnippetEditOptions);
                        $('.upload-button span').text('Save');
                        $('#custom_snippet_name').val(response.name);
                        $('#original_snippet_name').val(response.original);
                        $('#custom_snippet_type').val(response.type);
                        $('#custom_snippet_priority').val(response.priority);
                        $('#custom_snippet_content').val(response.content);
                    }
                });
            }).fail(function () {
                return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });
    }
});