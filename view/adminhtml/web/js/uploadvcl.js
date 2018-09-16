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

        $(document).ready(function () {




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



        $('body').on('click', 'button.fastly-delete-snippet-icon', function () {
            let snippet_id = $(this).data('snippet-id');
            let closestTr = $(this).closest('tr');
            if (confirm("Are you sure you want to delete "+ snippet_id +"?")) {
                vcl.deleteCustomSnippet(snippet_id, true).done(function (response) {
                    if (response.status == true) {
                        closestTr.remove();
                        $('#fastly-success-snippet-button-msg').text($.mage.__('Custom snippet successfully deleted.')).show();
                    }
                }).fail(function () {
                    vcl.showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });
            }
        });

        $('body').on('click', 'button.fastly-edit-snippet-icon', function () {
            let snippet_id = $(this).data('snippet-id');
            if (isAlreadyConfigured !== true) {
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

                if (service.status === false) {
                    return errorCustomSnippetBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;
                $.when(
                    $.ajax({
                        type: "GET",
                        url: config.getCustomSnippet,
                        data: {'snippet_id': snippet_id},
                        showLoader: true,
                    })
                ).done(function (response) {
                    if (response.status === true) {
                        vcl.showPopup('fastly-custom-snippet-edit-options');
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



        /**
         * Custom snippet upload
         */

        $('#fastly_custom_vcl_button').on('click', function () {

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
                    return errorCustomSnippetBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                service_name = service.service.name;
                vcl.showPopup('fastly-custom-snippet-options');
                $('.upload-button span').text('Create');

            }).fail(function () {
                return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

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

            // Retrieve custom snippets
            getCustomSnippets: function (loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.getCustomSnippetsUrl,
                    showLoader: loaderVisibility,
                    beforeSend: function (xhr) {
                        $('.loading-snippets').show();
                    }
                });
            },

            // Process custom snippets
            processCustomSnippets: function (snippets) {
                var html = '';
                $.each(snippets, function (index, snippet) {
                    html += "<tr id='fastly_" + index + "'>";
                    html += "<td><input data-snippetId='"+ index + "' id='snippet_" + index + "' value='"+ snippet +"' disabled='disabled' class='input-text' type='text'></td>";
                    html += "<td class='col-actions'><button class='action-delete fastly-edit-snippet-icon' data-snippet-id='" + snippet + "' id='fastly-edit-snippet"+ index + "' title='Edit custom snippet' type='button'></button>";
                    html += "<span>&nbsp;&nbsp;</span><button class='action-delete fastly-delete-snippet-icon' data-snippet-id='" + snippet + "' id='fastly-delete-snippet"+ index + "' title='Delete custom snippet' type='button'></button></td></tr>"
                });
                if (html != '') {
                    $('.no-snippets').hide();
                }
                $('#fastly-snippets-list').html(html);
            },

            deleteCustomSnippet: function (snippet_id, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.deleteCustomSnippet,
                    showLoader: loaderVisibility,
                    data: {'snippet_id': snippet_id},
                    beforeSend: function (xhr) {
                        vcl.resetAllMessages();
                    }
                });
            },

            // custom snippet creation
            setCustomSnippet: function () {
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
                        if (response.status == true) {
                            active_version = response.active_version;
                            vcl.modal.modal('closeModal');
                            successCustomSnippetBtnMsg.text($.mage.__('Custom snippet successfully created.')).show();
                            vcl.getCustomSnippets(false).done(function (snippetsResp) {
                                $('.loading-snippets').hide();
                                if (snippetsResp.status != false) {
                                    if (snippetsResp.snippets.length > 0) {
                                        snippets = snippetsResp.snippets;
                                        vcl.processCustomSnippets(snippets);
                                    } else {
                                        $('.no-snippets').show();
                                    }
                                }
                            }).fail(function () {
                                // TO DO: implement
                            });
                        } else {
                            msgWarning.text($.mage.__(response.msg)).show();
                        }
                    },
                    error: function (msg) {
                        return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },

            updateCustomSnippet: function () {
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
                            vcl.modal.modal('closeModal');
                            successCustomSnippetBtnMsg.text($.mage.__('Custom snippet successfully updated.')).show();
                            vcl.getCustomSnippets(false).done(function (snippetsResp) {
                                $('.loading-snippets').hide();
                                if (snippetsResp.status !== false) {
                                    if (snippetsResp.snippets.length > 0) {
                                        let snippets = snippetsResp.snippets;
                                        vcl.processCustomSnippets(snippets);
                                    } else {
                                        $('.no-snippets').show();
                                    }
                                }
                            }).fail(function () {
                                // TO DO: implement
                            });
                        } else {
                            msgWarning.text($.mage.__(response.msg)).show();
                        }
                    },
                    error: function (msg) {
                        return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    }
                });
            },


            uploadVclConfig: {
                'fastly-custom-snippet-options': {
                    title: jQuery.mage.__('You are about to create a custom snippet '),
                    content: function () {
                        return document.getElementById('fastly-custom-snippet-template').textContent;
                    },
                    actionOk: function () {
                        vcl.setCustomSnippet();
                    }
                },
                'fastly-custom-snippet-edit-options': {
                    title: jQuery.mage.__('You are about to edit a custom snippet '),
                    content: function () {
                        return document.getElementById('fastly-custom-snippet-edit-template').textContent;
                    },
                    actionOk: function () {
                        vcl.updateCustomSnippet();
                    }
                }
            }
        };
    };
});
