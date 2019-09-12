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
        /* Custom snippet button messages */
        let successCustomSnippetBtnMsg = $('#fastly-success-custom-snippet-button-msg');
        let errorCustomSnippetBtnMsg = $('#fastly-error-custom-snippet-button-msg');

        let active_version = serviceStatus.active_version;
        let snippets;
        let snippet_id;
        let closestTr;

        invokeAppendingTableRowWithDivOnTable();
        checkUpdateFlag();

        function checkUpdateFlag()
        {
            $.ajax({
                type: 'GET',
                url: config.getUpdateFlag,
                showLoader: false,
                success: function (response) {
                    if (response.flag !== true) {    //if VCL is not Uploaded
                        $(".changed-vcl-snippet-warning").text($.mage.__(response.msg)).show().off('click');
                    }
                }
            })
        }

        /**
         * Custom Snippet creation modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let customSnippetOptions = {
            title: jQuery.mage.__('You are about to create a Custom Snippet '),
            content: function () {
                return document.getElementById('fastly-custom-snippet-template').textContent;
            },
            actionOk: function () {
                saveCustomSnippet();
            }
        };

        let deleteCustomSnippetOptions = {
            title: jQuery.mage.__('You are about to delete a Custom Snippet '),
            content: function () {
                return document.getElementById('fastly-delete-custom-snippet-template').textContent;
            },
            actionOk: function () {
                deleteCustomSnippet(snippet_id);
            }
        };

        /**
         * Custom Snippet edit modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let customSnippetEditOptions = {
            title: jQuery.mage.__('You are about to edit a Custom Snippet '),
            content: function () {
                return document.getElementById('fastly-custom-snippet-edit-template').textContent;
            },
            actionOk: function () {
                updateCustomSnippet();
            }
        };

        function invokeAppendingTableRowWithDivOnTable()
        {
            if ($('#warning-message-after-change').length !== 0) {
                return;
            }

            appendTableRowWithDiv(
                $('#row_system_full_page_cache_fastly_fastly_custom_snippets .form-list > tbody'),
                'warning-message-after-change',
                'message message-warning changed-vcl-snippet-warning',
                {
                    'font-size': '1.2rem',
                    'margin-top': '5px',
                    'padding': '1.4rem 4rem 1.4rem 5.5rem',
                    'display': 'none'
                }
            );
        }

        function appendTableRowWithDiv(field, id, classSelector, style)
        {
            let tr = document.createElement('tr');
            let td = document.createElement('td');
            let td2 = document.createElement('td');
            let td3 = document.createElement('td');
            let div = document.createElement('div');
            div.setAttribute('class', classSelector);
            div.setAttribute('id', id);
            td.setAttribute('class', 'label');
            tr.append(td);
            td2.setAttribute('class', 'value');
            td2.append(div);
            tr.append(td2);
            tr.append(td3);
            field.append(tr);
            $("#" + id).css(style);
        }

        /**
         * Trigger the Custom Snippet list call
         */
        getCustomSnippets(false).done(function (response) {
            $('.loading-snippets').hide();
            if (response.status !== false) {
                snippets = response.snippets;
                processCustomSnippets(snippets);
            } else {
                $('.no-snippets').show();
            }
        });

        /**
         * Save a Custom Snippet
         */
        function saveCustomSnippet()
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
                        setUpdateFlagToFalse();
                        getCustomSnippets().done(function (snippetsResp) {
                            $('.loading-snippets').hide();
                            if (snippetsResp.status !== false) {
                                snippets = snippetsResp.snippets;
                                processCustomSnippets(snippets);
                            } else {
                                $('.no-snippets').show();
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

        /**
         * Retrieve a Custom Snippet
         *
         * @returns {*}
         */
        function getCustomSnippets()
        {
            return $.ajax({
                type: "GET",
                url: config.getCustomSnippetsUrl,
                showLoader: false
            });
        }

        /**
         * Process and display Custom Snippets
         *
         * @param snippets
         */
        function processCustomSnippets(snippets)
        {
            let html = '';
            $.each(snippets, function (index, snippet) {
                html += "<tr id='fastly_" + index + "'>";
                html += "<td><input data-snippetId='" + index + "' id='snippet_" + index + "' value='" + snippet + "' disabled='disabled' class='input-text' type='text'></td>";
                html += "<td class='col-actions'><button class='action-delete fastly-edit-snippet-icon' data-snippet-id='" + snippet + "' id='fastly-edit-snippet" + index + "' title='Edit custom snippet' type='button'></button>";
                html += "<span>&nbsp;&nbsp;</span><button class='action-delete fastly-delete-snippet-icon' data-snippet-id='" + snippet + "' id='fastly-delete-snippet" + index + "' title='Delete custom snippet' type='button'></button></td></tr>"
            });
            if (html !== '') {
                $('.no-snippets').hide();
            }
            $('#fastly-snippets-list').html(html);
        }

        /**
         * set flag "updated" to false inside core_config_data when VCL is modified
         */
        function setUpdateFlagToFalse()
        {
            $.ajax({
                type: 'GET',
                url: config.changeUpdateFlag,
                showLoader: true,
                success: function (response) {
                    $(".changed-vcl-snippet-warning").text($.mage.__(response.msg)).show().off('click');
                }
            })
        }

        /**
         * Delete a Custom Snippet
         *
         * @param snippet_id
         * @returns {*}
         */
        function deleteCustomSnippet(snippet_id)
        {
            let activate_flag = false;

            if ($('#fastly_delete_custom_snippet_activate').is(':checked')) {
                activate_flag = true;
            }
            return $.ajax({
                type: "GET",
                url: config.deleteCustomSnippet,
                showLoader: true,
                data: {
                    'snippet_id': snippet_id,
                    'active_version': active_version,
                    'activate_flag': activate_flag
                },
                beforeSend: function () {
                    resetAllMessages();
                },
                success: function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        closestTr.remove();
                        successCustomSnippetBtnMsg.text($.mage.__('Custom snippet successfully deleted.')).show();
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                }
            });
        }

        /**
         * Update a Custom Snippet
         */
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
                        setUpdateFlagToFalse();
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
         * Custom Snippet create button on click event
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
                overlay(customSnippetOptions);
                $('.upload-button span').text('Create');
            }).fail(function () {
                return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Custom Snippet delete button on click event
         */
        $('body').on('click', 'button.fastly-delete-snippet-icon', function () {
            snippet_id = $(this).data('snippet-id');
            closestTr = $(this).closest('tr');

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
                overlay(deleteCustomSnippetOptions);
                $('.fastly-message-notice').text('You are about to delete the ' + snippet_id + ' custom snippet.').show();
                $('.maintenance-checkbox-container').hide();
                $.when(
                    $.ajax({
                        type: "GET",
                        url: config.checkCustomSnippet,
                        data: {
                            'snippet_id': snippet_id,
                            'active_version': active_version
                        },
                        showLoader: true
                    })
                ).done(function (response) {
                    if (response.status === true) {
                        setServiceLabel(active_version, next_version, service_name);
                        $('.maintenance-checkbox-container').show();
                    }
                });
                $('.upload-button span').text('Delete');
            }).fail(function () {
                return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Custom Snippet edit button on click event
         */
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
                        overlay(customSnippetEditOptions);
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