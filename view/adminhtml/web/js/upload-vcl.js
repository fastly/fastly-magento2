define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {
        /* VCL button messages */
        let successVclBtnMsg = $('#fastly-success-vcl-button-msg');
        let errorVclBtnMsg = $('#fastly-error-vcl-button-msg');
        let active_version = serviceStatus.active_version;

        $(document).ready(function () {
            let uploadOptions = {
                title: jQuery.mage.__('You are about to upload VCL to Fastly '),
                content: function () {
                    return document.getElementById('fastly-uploadvcl-template').textContent;
                },
                actionOk: function () {
                    uploadVcl(active_version);
                }
            };

            /**
             * VCL Upload button on click event
             */
            $('#fastly_vcl_upload_button').on('click', function () {
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
                        return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                    }

                    active_version = service.active_version;
                    let next_version = service.next_version;
                    let service_name = service.service.name;
                    overlay(uploadOptions);
                    setServiceLabel(active_version, next_version, service_name);

                }).fail(function () {
                    return errorVclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                });
            });

            /**
             * Upload VCL snippets to the Fastly service
             */
            function uploadVcl()
            {
                let activate_vcl_flag = false;

                if ($('#fastly_activate_vcl').is(':checked')) {
                    activate_vcl_flag = true;
                }

                $.ajax({
                    type: "POST",
                    url: config.vclUploadUrl,
                    data: {
                        'activate_flag': activate_vcl_flag,
                        'active_version': active_version
                    },
                    showLoader: true
                }).done(function (response) {
                    if (response.status === true) {
                        modal.modal('closeModal');
                        successVclBtnMsg.text($.mage.__('VCL file is successfully uploaded to the Fastly service.')).show();
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                });
            }
        });
    }
});