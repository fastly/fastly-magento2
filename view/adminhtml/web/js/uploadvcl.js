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



        var authDictStatus = null;
        var authStatus = true;
        var backends = null;
        var dictionaries = null;
        var dictionary_id = null;
        var acls = null;
        var auths = null;
        var active_version = '';
        var next_version = '';
        var service_name;
        var isAlreadyConfigured = true;
        /* Image button message */
        var successImageBtnMsg = $('#fastly-success-imgopt-button-msg');
        var errorImageBtnMsg = $('#fastly-error-imgopt-button-msg');
        var warningImageBtnMsg = $('#fastly-warning-imgopt-button-msg');
        /* VCL button messages */
        var successVclBtnMsg = $('#fastly-success-vcl-button-msg');
        var errorVclBtnMsg = $('#fastly-error-vcl-button-msg');
        var warningVclBtnMsg = $('#fastly-warning-vcl-button-msg');
        /* Custom snippet button messages */
        var successCustomSnippetBtnMsg = $('#fastly-success-custom-snippet-button-msg');
        var errorCustomSnippetBtnMsg = $('#fastly-error-custom-snippet-button-msg');
        var warningCUstomSnippetBtnMsg = $('#fastly-warning-custom-snippet-button-msg');
        /* Blocking button messages */
        var successBlockingBtnMsg = $('#fastly-success-blocking-button-msg');
        var errorBlockingBtnMsg = $('#fastly-error-blocking-button-msg');
        var warningBlockingBtnMsg = $('#fastly-warning-blocking-button-msg');
        /* Error page HTML button */
        var successHtmlBtnMsg = $('#fastly-success-html-page-button-msg');
        var errorHtmlBtnMsg = $('#fastly-error-html-page-button-msg');
        var warningHtmlBtnMsg = $('#fastly-warning-html-page-button-msg');
        /* WAF page HTML button */
        var successWafBtnMsg = $('#fastly-success-waf-page-button-msg');
        var errorWafBtnMsg = $('#fastly-error-waf-page-button-msg');
        var warningWafBtnMsg = $('#fastly-warning-waf-page-button-msg');
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

        var wafPageRow = $('#row_system_full_page_cache_fastly_fastly_error_maintenance_page_waf_page');

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

            // Process backends
            processBackends: function (backends) {
                $('#fastly-backends-list').html('');
                $.each(backends, function (index, backend) {
                    var html = "<tr id='fastly_" + index + "'>";
                    html += "<td><input data-backendId='"+ index + "' id='backend_" + index + "' value='"+ backend.name +"' disabled='disabled' class='input-text' type='text'></td>";
                    html += "<td class='col-actions'><button class='action-delete fastly-edit-backend-icon' data-backend-id='" + index + "' id='fastly-edit-backend_"+ index + "' title='Edit backend' type='button'></td></tr>";
                    $('#fastly-backends-list').append(html);
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

            editCustomSnippet: function (snippet_id, loaderVisibility) {
                return $.ajax({
                    type: "GET",
                    url: config.editCustomSnippet,
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

                // WAF page button messages
                successWafBtnMsg.hide();
                errorWafBtnMsg.hide();
                warningWafBtnMsg.hide();


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
            }
        };
    };
});
