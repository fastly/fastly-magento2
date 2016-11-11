define([
    "jquery",
    'mage/template',
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function($){

    return function (config) {

        $('#fastly_vcl_upload_button').on('click', function () {
            vcl.resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true,
                }),

                $.ajax({
                    type: "GET",
                    url: config.customerInfoUrl,
                    showLoader: true,
                })

            ).done(function (service, customer) {

                if(service[0].status == false || customer[0].status == false) {
                    return errorBtnMsg.text($.mage.__('Please check your Service ID and API key and try again.')).show();
                }

                if(customer[0].customer.can_upload_vcl == false)
                {
                    return warningBtnMsg.text($.mage.__('You don\'t have a permission to upload VCL. Please, send us an email.')).show();
                }

                active_version = service[0].active_version;
                next_version = service[0].next_version;
                vcl.showPopup('fastly-uploadvcl-options');
                vcl.setActiveServiceLabel(active_version, next_version);

            }).fail(function (msg) {
                alert('Fail.');
            })
        });

        var active_version = '';
        var next_version = '';
        var successBtnMsg = $('#fastly-success-button-msg');
        var errorBtnMsg = $('#fastly-error-button-msg');
        var warningBtnMsg = $('#fastly-warning-button-msg');

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

            setActiveServiceLabel: function (active_version, next_version) {
                var msgWarning = $('.fastly-message-warning');
                msgWarning.text($.mage.__('You are about to clone version') + ' ' + active_version + '. '
                    + $.mage.__('We\'ll upload your VCL to version ') + ' ' + next_version + '.');
                msgWarning.show();
            },

            submitVcl: function (active_version) {
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
                            vcl.modal.modal('closeModal');
                            successBtnMsg.text($.mage.__('VCL file is successfully uploaded to the Fastly service.')).show();
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
                msgWarning.text();
                msgWarning.hide();
                msgError.text();
                msgError.hide();
                successBtnMsg.hide();
                errorBtnMsg.hide();
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
                }
            }
        };
    };
});