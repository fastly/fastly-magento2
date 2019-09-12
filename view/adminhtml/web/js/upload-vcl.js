define(
    [
        "jquery",
        "setServiceLabel",
        "overlay",
        "resetAllMessages",
        "showErrorMessage",
        "Magento_Ui/js/modal/confirm",
        'mage/translate'
    ],
    function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, confirm) {
        return function (config, serviceStatus, isAlreadyConfigured) {
            /* VCL button messages */
            let successVclBtnMsg = $('#fastly-success-vcl-button-msg');
            let errorVclBtnMsg = $('#fastly-error-vcl-button-msg');
            let outdatedErrorMsg = $("#fastly-warning-outdated-vcl-button-msg");
            let active_version = serviceStatus.active_version;

            $(document).ready(
                function () {
                    outdatedErrorMsg.removeClass('fastly-button-messages');
                    outdatedErrorMsg.addClass('changed-vcl-snippet-warning');
                    outdatedErrorMsg.css({
                        'font-size': '1.2rem',
                        'margin-top': '5px',
                        'padding': '1.4rem 4rem 1.4rem 5.5rem'
                    });
                    checkUpdateFlag();
                    let uploadOptions = {
                        title: jQuery.mage.__('You are about to upload VCL to Fastly '),
                        content: function () {
                            return document.getElementById('fastly-uploadvcl-template').textContent;
                        },
                        actionOk: function () {
                            uploadVcl(active_version);
                        }
                    };

                    function isWarningDismissed(activeVersion)
                    {
                        $.ajax(
                            {
                                type: 'GET',
                                url: config.isWarningDismissed,
                                data: {active_version: activeVersion},
                                showLoader: true,
                                success: function (response) {
                                    if (response.status !== false) {
                                        resetAllMessages();
                                        if (response.dismissed !== true) {
                                            compareVclVersions();
                                            return;
                                        }

                                        return;
                                    }

                                    return errorVclBtnMsg.text($.mage.__(response.msg)).show();
                                }
                            }
                        )
                    }

                    function checkUpdateFlag()
                    {
                        $.ajax({
                            type: 'GET',
                            url: config.getUpdateFlag,
                            showLoader: false,
                            success: function (response) {
                                if (response.flag !== true) {    //if VCL is not Uploaded
                                    $(".changed-vcl-snippet-warning").text($.mage.__(response.msg)).show().off('click');
                                    return;
                                }

                                isWarningDismissed(active_version);
                            }
                        })
                    }

                    function compareVclVersions()
                    {
                        $.ajax(
                            {
                                type: 'GET',
                                url: config.vclComparison,
                                showLoader: false,
                                data: {'active_version': active_version},
                                success: function (response) {
                                    if (response.status !== true) {
                                        let span = document.createElement('span');
                                        span.setAttribute('class', 'fastly-dismiss-warning-action');
                                        span.setAttribute('title', 'Dismiss Warning');
                                        outdatedErrorMsg.text($.mage.__(response.msg)).show();
                                        outdatedErrorMsg.append(span);
                                        openDismissModalOnClick();
                                    }
                                }
                            }
                        );
                    }

                    /**
                     * VCL Upload button on click event
                     */
                    $('#fastly_vcl_upload_button').on(
                        'click',
                        function () {
                            if (isAlreadyConfigured !== true) {
                                $(this).attr('disabled', true);
                                return alert($.mage.__('Please save config prior to continuing.'));
                            }

                            resetAllMessages();

                            $.when(
                                $.ajax(
                                    {
                                        type: "GET",
                                        url: config.serviceInfoUrl,
                                        showLoader: true
                                    }
                                )
                            ).done(
                                function (service) {
                                    if (service.status === false) {
                                        return errorVclBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                                    }

                                    active_version = service.active_version;
                                    let next_version = service.next_version;
                                    let service_name = service.service.name;
                                    overlay(uploadOptions);
                                    setServiceLabel(active_version, next_version, service_name);

                                }
                            ).fail(
                                function () {
                                    return errorVclBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                                }
                            );
                        }
                    );

                    /**
                     * Upload VCL snippets to the Fastly service
                     */
                    function uploadVcl()
                    {
                        let activate_vcl_flag = false;

                        if ($('#fastly_activate_vcl').is(':checked')) {
                            activate_vcl_flag = true;
                        }

                        $.ajax(
                            {
                                type: "POST",
                                url: config.vclUploadUrl,
                                data: {
                                    'activate_flag': activate_vcl_flag,
                                    'active_version': active_version
                                },
                                showLoader: true
                            }
                        ).done(
                            function (response) {
                                if (response.status === true) {
                                    modal.modal('closeModal');
                                    $(".changed-vcl-snippet-warning").text();
                                    $(".changed-vcl-snippet-warning").hide();
                                    successVclBtnMsg.text($.mage.__('VCL file is successfully uploaded to the Fastly service.')).show();
                                } else {
                                    resetAllMessages();
                                    showErrorMessage(response.msg);
                                }
                            }
                        );
                    }

                    function openDismissModalOnClick()
                    {
                        outdatedErrorMsg.on(
                            'hover',
                            function () {
                                $(this).css('cursor', 'pointer');
                            }
                        );

                        outdatedErrorMsg.on(
                            'click',
                            function () {
                                confirm(
                                    {
                                        title: 'Dismiss outdated VCL warning',
                                        content: 'Are you sure you want to dismiss warning for the current version #<b>' + active_version + '</b> ?',
                                        actions: {
                                            confirm: function () {
                                                dismissWarning(active_version);
                                            },
                                            cancel: function () {
                                            }
                                        }
                                    }
                                );
                            }
                        );

                        function dismissWarning(version)
                        {
                            $.ajax(
                                {
                                    type: 'GET',
                                    url: config.dismissWarning,
                                    showLoader: true,
                                    data: {active_version: version},
                                    success: function (response) {
                                        if (response.status !== false) {
                                            resetAllMessages();
                                            return successVclBtnMsg.text($.mage.__(response.msg)).show();
                                        }

                                        return errorVclBtnMsg.text($.mage.__(response.msg)).show();
                                    }
                                }
                            );
                        }
                    }
                }
            );
        }
    }
);