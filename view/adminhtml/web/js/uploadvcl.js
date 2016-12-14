define([
    "jquery",
    'mage/template',
    "Magento_Ui/js/modal/modal",
    'mage/translate',
    'mage/validation'
], function($){

    return function (config) {0

        var requestStateSpan = '';
        var requestStateMsgSpan = '';

        $(document).ready(function () {
            if (config.isFastlyEnabled) {

                $.ajax({
                    type: "GET",
                    url: config.isAlreadyConfiguredUrl
                }).done(function (response) {
                    if(response.status == true) {
                        isAlreadyConfigured = response.flag;
                    }
                });

                // Checking service status & presence of force_tls request setting
                requestStateSpan = $('#request_state_span');
                requestStateMsgSpan = $('#fastly_request_state_message_span');
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    beforeSend: function (xhr) {
                        requestStateSpan.find('.processing').show();
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
                            }
                        ).fail(function () {
                            requestStateSpan.find('.processing').hide();
                            requestStateMsgSpan.find('#force_tls_state_unknown').show();
                        });

                        // Fetch backends
                        vcl.getBackends(active_version, false).done(function (backendsResp) {
                            $('.loading-backends').hide();
                            if(backendsResp.status != false) {
                                if(backendsResp.backends.length > 0) {
                                    backends = backendsResp.backends;
                                    vcl.processBackends(backendsResp.backends);
                                } else {
                                    $('.no-backends').show();
                                }
                            }

                        }).fail(function () {
                            // TO DO: implement
                        });
                    } else {
                        requestStateSpan.find('.processing').hide();
                        requestStateMsgSpan.find('#force_tls_state_unknown').show();
                    }
                }).fail(function () {
                    requestStateMsgSpan.find('#force_tls_state_unknown').show();
                });
            }
        });

        $('body').on('click', 'button.fastly-edit-backend-icon', function() {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl
            }).done(function (checkService) {
                active_version = checkService.active_version;
                next_version = checkService.next_version;
                vcl.setActiveServiceLabel(active_version, next_version);
            });

            var backend_id = $(this).data('backend-id');
            if(backends != null && backend_id != null) {
                vcl.showPopup('fastly-backend-options');
                var backend_name = "Backend " + backends[backend_id].name;
                $('.modal-title').text($.mage.__(backend_name));
                $('#backend_name').val(backends[backend_id].name);
                $('#backend_shield option[value=' + backends[backend_id].shield +']').attr('selected','selected');
                $('#backend_connect_timeout').val(backends[backend_id].connect_timeout);
                $('#backend_between_bytes_timeout').val(backends[backend_id].between_bytes_timeout);
                $('#backend_first_byte_timeout').val(backends[backend_id].first_byte_timeout);
            }
        });

        $('#fastly_vcl_upload_button').on('click', function () {

            if(isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please, save configuration and clear cache.'));
            }

            vcl.resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                })
            ).done(function (service) {

                if(service.status == false) {
                    return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API key and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                vcl.showPopup('fastly-uploadvcl-options');
                vcl.setActiveServiceLabel(active_version, next_version);

            }).fail(function () {
                return errorVclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        $('#fastly_force_tls_button').on('click', function () {

            if(isAlreadyConfigured != true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please, save configuration and clear cache.'));
            }

            vcl.resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {

                if(service.status == false) {
                    return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API key and try again.')).show();
                }

                active_version = service.active_version;
                next_version = service.next_version;
                vcl.getTlsSetting(active_version, true).done(function (response) {
                        if(response.status == false) {
                            forceTls = response.status;
                            $('.modal-title').text($.mage.__('We are about to turn on TLS'));
                        } else {
                            $('.modal-title').text($.mage.__('We are about to turn off TLS'));
                        }
                    }
                ).fail(function () {

                    }
                );
                vcl.showPopup('fastly-tls-options');
                vcl.setActiveServiceLabel(active_version, next_version);

            }).fail(function (msg) {
                return errorTlsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        var backends = null;
        var active_version = '';
        var next_version = '';
        var forceTls = true;
        var isAlreadyConfigured = true;
        /* VCL button messages */
        var successVclBtnMsg = $('#fastly-success-vcl-button-msg');
        var errorVclBtnMsg = $('#fastly-error-vcl-button-msg');
        var warningVclBtnMsg = $('#fastly-warning-vcl-button-msg');

        /* TLS button messages */
        var successTlsBtnMsg = $('#fastly-success-tls-button-msg');
        var errorTlsBtnMsg = $('#fastly-error-tls-button-msg');
        var warningTlsBtnMsg = $('#fastly-warning-tls-button-msg');

        var vcl = {

            showPopup: function(divId) {
                var self = this;

                this.modal = jQuery('<div/>').attr({id: divId}).html(this.uploadVclConfig[divId].content()).modal({
                    modalClass: 'magento',
                    title: this.uploadVclConfig[divId].title,
                    type: 'slide',
                    closed: function(e, modal){
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
                        'class': 'action primary',
                        click: function () {
                            self.uploadVclConfig[divId].actionOk.call(self);
                        }
                    }]
                });
                this.modal.modal('openModal');
            },

            // Queries Fastly API to retrive services
            getService: function() {
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true,
                    success: function(response)
                    {
                        if(response != false) {
                            vcl.setActiveServiceLabel(active_version, next_version);
                        }
                    },
                    error: function(msg)
                    {
                        // TODO: error handling
                    }
                });
            },

            // Queries Fastly API to retrive Tls setting
            getTlsSetting: function(active_version, loaderVisibility) {
                return $.ajax({
                    type: "POST",
                    url: config.checkTlsSettingUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            },

            // Queries Fastly API to retrive Backends
            getBackends: function(active_version, loaderVisibility) {
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

            isAlreadyConfigured: function () {
                return $.ajax({
                    type: "GET",
                    url: config.isAlreadyConfiguredUrl
                });
            },

            // Process backends
            processBackends: function(backends) {
                $.each(backends, function (index, backend) {
                    var html = "<tr id='fastly_" + index + "'>";
                    html += "<td><input data-backendId='"+ index + "' id='backend_" + index + "' name='test' value='"+ backend.name +"' disabled='disabled' class='input-text' type='text'></td>";
                    html += "<td class='col-actions'><button class='action-delete fastly-edit-backend-icon' data-backend-id='" + index + "' id='fastly-edit-backend_"+ index + "' title='Edit backend' type='button'></td></tr>";
                    $('#fastly-backends-list').append(html);
                });
            },

            // Setting up label text
            setActiveServiceLabel: function (active_version, next_version) {
                var msgWarning = $('.fastly-message-warning');
                msgWarning.text($.mage.__('You are about to clone active version') + ' ' + active_version + '. '
                    + $.mage.__('We\'ll make changes to version ') + ' ' + next_version + '.');
                msgWarning.show();
            },

            // Upload process
            submitVcl: function () {
                var activate_vcl_flag = false;

                if($('#fastly_activate_vcl').is(':checked')) {
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
                    success: function(response)
                    {
                        if(response.status == true)
                        {
                            active_version = response.active_version;
                            vcl.modal.modal('closeModal');
                            successVclBtnMsg.text($.mage.__('VCL file is successfully uploaded to the Fastly service.')).show();
                        } else {
                            vcl.resetAllMessages();
                            vcl.showErrorMessage(response.msg);
                        }
                    },
                    error: function(msg)
                    {
                        // TODO: error handling
                    }
                });
            },

            // Toggle Tls process
            toggleTls: function (active_version) {
                var activate_tls_flag = false;

                if($('#fastly_activate_tls').is(':checked')) {
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
                    success: function(response)
                    {
                        if(response.status == true)
                        {
                            vcl.modal.modal('closeModal');
                            var onOrOff = 'off';
                            var disabledOrEnabled = 'disabled';
                            if(forceTls == false) {
                                onOrOff = 'on';
                                disabledOrEnabled = 'enabled';
                            } else {
                                onOrOff = 'off';
                                disabledOrEnabled = 'disabled';
                            }
                            successTlsBtnMsg.text($.mage.__('The Force TLS request setting is successfully turned ' + onOrOff + '.')).show();
                            $('.request_tls_state_span').hide();
                            console.log(forceTls);
                            console.log(disabledOrEnabled);
                            if(disabledOrEnabled == 'enabled') {
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
                    error: function(msg)
                    {
                        // TODO: error handling
                    }
                });
            },

            // Reconfigure backend
            configureBackend: function () {
                var activate_backend = false;

                if($('#fastly_activate_backend').is(':checked')) {
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
                    success: function(response)
                    {
                        if(response.status == true)
                        {
                            $('#fastly-success-backend-button-msg').text($.mage.__('Backend is successfully updated.')).show();
                            active_version = response.active_version;
                            vcl.modal.modal('closeModal');
                            $('#fastly-backends-list').html('');
                            vcl.getBackends(response.active_version, false).done(function (backendsResp) {
                                $('.loading-backends').hide();
                                if(backendsResp.status != false) {
                                    if(backendsResp.backends.length > 0) {
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
                    error: function(msg)
                    {
                        // TODO: error handling
                    }
                });
            },

            showErrorMessage: function (msg) {
                var msgError = $('.fastly-message-error');
                msgError.text($.mage.__(msg));
                msgError.show();
            },

            resetAllMessages: function () {
                var msgWarning = $('.fastly-message-warning');
                var msgError = $('.fastly-message-error');

                // Modal window warning messages
                msgWarning.text();
                msgWarning.hide();

                // Modal windows error messages
                msgError.text();
                msgError.hide();

                // Vcl button messages
                successVclBtnMsg.hide();
                errorVclBtnMsg.hide();
                warningTlsBtnMsg.hide();

                // Tls button messages
                successTlsBtnMsg.hide();
                errorTlsBtnMsg.hide();
                warningTlsBtnMsg.hide();
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
                }
            }
        };
    };
});
