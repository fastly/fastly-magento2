define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* Image optimization state elements */
        let ioStateSpan = $('#io_state_span');
        let ioStateMsgSpan = $('#fastly_io_state_message_span');
        let ioSnippetStatus = true;
        let ioServiceStatus;
        let ioStateEnabled = ioStateMsgSpan.find('#io_state_enabled');
        let ioStateDisabled = ioStateMsgSpan.find('#io_state_disabled');

        /* Image optimization button messages */
        let successIoBtnMsg = $('#fastly-success-io-button-msg');
        let errorIoBtnMsg = $('#fastly-error-io-button-msg');
        let warningIoBtnMsg = $('#fastly-warning-io-button-msg');

        let successIoMsg = $('#fastly-success-io-msg');
        let errorIoMsg = $('#fastly-error-io-msg');
        let warningIoMsg = $('#fastly-warning-io-msg');

        let ioBtn = $('#fastly_push_image_config');
        let ioConfigBtn = $('#fastly_io_default_config');
        let deepIoToggle = $('#system_full_page_cache_fastly_fastly_image_optimization_configuration_image_optimizations');

        let active_version = serviceStatus.active_version;

        ioStateSpan.find('.processing').show();

        /**
         * Image Optimization VCL snippet upload overlay options
         *
         * @description returns the template for the Image Optimization VCL snippet upload form
         * @type {{id: string, title: *, content: (function(): string), actionOk: actionOk}}
         */
        let ioOptions = {
            id: 'fastly-image-options',
            title: jQuery.mage.__('Activate image optimization'),
                content: function () {
                return document.getElementById('fastly-image-template').textContent;
            },
            actionOk: function () {
                toggleIo(active_version);
            }
        };

        /**
         * Image Optimization default configuration overlay options
         *
         * @description returns the template for the Image Optimization default configuration form
         * @type {{id: string, title: *, content: (function(): string), actionOk: actionOk}}
         */
        let ioDefaultOptions = {
            id: 'fastly-io-default-config-options',
            title: jQuery.mage.__('Image optimization default config options'),
                content: function () {
                return document.getElementById('fastly-io-default-config-options-template').textContent;
            },
            actionOk: function () {
                configureIo(serviceStatus.active_version);
            }
        };

        triggerIoServiceStatusCall();

        /**
         * Enable/disable the Image Optimization buttons and display messages
         *
         * @description enables/disables the Image Optimization buttons depending on the Image Optimization service
         *              status
         */
        function triggerIoServiceStatusCall()
        {
            getIoServiceStatus().done(function (response) {
                if (response.status === false) {
                    if (config.isIoEnabled) {
                        deepIoToggle.removeAttrs('disabled');
                    } else {
                        deepIoToggle.attr('disabled', 'disabled');
                    }
                    ioConfigBtn.addClass('disabled');
                } else {
                    ioConfigBtn.removeClass('disabled');
                }

                ioServiceStatus = response.status;
                triggerIoSnippetStatusCall();
            });
        }

        /**
         * Trigger the Image Optimization VCL snippet status call
         *
         * @description sets and displays the status of the Image Optimization VCL snippet
         */
        function triggerIoSnippetStatusCall()
        {
            getIoSnippetStatus(serviceStatus.active_version, false).done(function (response) {
                ioStateSpan.find('.processing').hide();
                ioSnippetStatus = response.status;

                if (ioSnippetStatus === true) {
                    if (ioStateDisabled.is(":hidden")) {
                        ioStateEnabled.show();
                    }

                    if (ioServiceStatus === true) {
                        ioBtn.removeClass('disabled');
                        warningIoBtnMsg.hide();
                    } else {
                        warningIoBtnMsg.text(
                            $.mage.__(
                                'Please contact your sales rep or send an email to support@fastly.com to request image optimization activation for your Fastly service.'
                            )
                        ).show();
                    }
                } else if (ioSnippetStatus === false) {
                    if (ioStateEnabled.is(":hidden")) {
                        ioStateDisabled.show();
                    }

                    if (ioServiceStatus === true) {
                        ioBtn.removeClass('disabled');
                        warningIoBtnMsg.hide();
                    } else {
                        ioBtn.addClass('disabled');
                        warningIoBtnMsg.text(
                            $.mage.__(
                                'Please contact your sales rep or send an email to support@fastly.com to request image optimization activation for your Fastly service.'
                            )
                        ).show();
                    }
                } else {
                    ioStateMsgSpan.find('#io_state_unknown').show();
                }
            }).fail(function () {
                ioStateSpan.find('.processing').hide();
                ioStateMsgSpan.find('#io_state_unknown').show();
            });
        }

        /**
         * Get the Image Optimization snippet status
         *
         * @description queries the Fastly API to retrieve Image Optimization VCL snippet status
         * @param active_version
         * @param loaderVisibility
         * @returns {*}
         */
        function getIoSnippetStatus(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "POST",
                url: config.checkImageSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        /**
         * Get the Image Optimization service status
         *
         * @description queries the Fastly API to check if Image Optimization is enabled for the service
         * @returns {*}
         */
        function getIoServiceStatus()
        {
            return $.ajax({
                type: "GET",
                url: config.checkFastlyIoSettingUrl
            });
        }

        /**
         * Get the Image Optimization default configuration
         *
         * @description queries the Fastly API to retrieve the Image Optimization default configuration
         * @param active_version
         */
        function getIoDefaultConfig(active_version)
        {
            return $.ajax({
                type: "POST",
                url: config.listIoDefaultConfigOptions,
                showLoader: true,
                data: {'active_version': active_version}
            });
        }

        /**
         * Upload/remove the Image Optimization VCL snippet
         *
         * @description uploads/removes the Image Optimization snippet and shows the new status and messages
         * @param active_version
         */
        function toggleIo(active_version)
        {
            let activate_image_flag = false;
            let image_quality_flag = false;

            if ($('#fastly_activate_image_vcl').is(':checked')) {
                activate_image_flag = true;
            }

            if ($('#fastly_image_quality_flag').is(':checked')) {
                image_quality_flag = true;
            }

            $.ajax({
                type: "POST",
                url: config.toggleImageSettingUrl,
                data: {
                    'activate_flag': activate_image_flag,
                    'image_quality_flag': image_quality_flag,
                    'active_version': active_version
                },
                showLoader: true
            }).done(function (response) {
                if (response.status === true) {
                    let toggled;
                    modal.modal('closeModal');

                    if (ioSnippetStatus === false) {
                        toggled = 'enabled';
                        ioSnippetStatus = true;
                    } else {
                        toggled = 'disabled';
                        ioSnippetStatus = false;
                    }

                    successIoBtnMsg.text($.mage.__('The Image Optimization snippet has been successfully ' + toggled + '.')).show();
                    $('.request_imgopt_state_span').hide();

                    if (ioSnippetStatus === true) {
                        ioStateMsgSpan.find('#imgopt_state_disabled').hide();
                        ioStateMsgSpan.find('#imgopt_state_enabled').show();
                        ioStateEnabled.show();
                        ioStateDisabled.hide();
                        if (ioServiceStatus === true) {
                            ioBtn.removeClass('disabled');
                        }
                    } else {
                        ioStateMsgSpan.find('#imgopt_state_enabled').hide();
                        ioStateMsgSpan.find('#imgopt_state_disabled').show();
                        ioStateEnabled.hide();
                        ioStateDisabled.show();
                        if (ioServiceStatus !== true) {
                            ioBtn.addClass('disabled');
                        }
                    }
                } else {
                    resetAllMessages();
                    showErrorMessage(response.msg);
                }
            });
        }

        /**
         * Update Image Optimization Default Configuration
         *
         * @description updates the Image Optimization configuration default values
         */
        function configureIo()
        {
            let activate_vcl = false;

            if ($('#fastly_activate_io_vcl').is(':checked')) {
                activate_vcl = true;
            }
            let webp = $('input[name=webp-radio]:checked').val();
            let webp_quality = $('#webp_quality').val();
            let jpeg_type = $('input[name=jpeg-format]:checked').val();
            let jpeg_quality = $('#jpeg_quality').val();
            let upscale = $('input[name=upscaling-radio]:checked').val();
            let resize_filter = $('input[name=resize-filter-radio]:checked').val();

            $.ajax({
                type: "POST",
                url: config.configureIoUrl,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_vcl,
                    'webp': webp,
                    'webp_quality': webp_quality,
                    'jpeg_type': jpeg_type,
                    'jpeg_quality': jpeg_quality,
                    'upscale': upscale,
                    'resize_filter': resize_filter
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        $('#fastly-success-io-default-config-btn-msg').text($.mage.__(
                            'Image optimization default configuration is successfully updated.'
                        )).show();
                        active_version = response.active_version;
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function (msg) {
                    // error handling
                }
            });
        }

        /**
         * Image Optimization configuration button click event
         *
         * @description checks the Fastly service status and populates the Image Optimization configuration fields
         */
        ioConfigBtn.on('click', function () {
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
                    return errorIoMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                overlay(ioDefaultOptions);
                setServiceLabel(active_version, next_version, service_name);
                $('.upload-button span').text('Update');

                getIoDefaultConfig(active_version).done(function (response) {
                    if (response.status === true) {
                        let ioDefaultConfig = response.io_options;

                        setServiceLabel(active_version, next_version, service_name);

                        if (ioDefaultConfig.webp === false) {
                            $('#webp-no').prop('checked', true);
                        } else {
                            $('#webp-yes').prop('checked', true);
                        }

                        $('#webp_quality').val(ioDefaultConfig.webp_quality);

                        if (ioDefaultConfig.jpeg_type === 'auto') {
                            $('#jpeg-format-auto').prop('checked', true);
                        } else if (ioDefaultConfig.jpeg_type === 'baseline') {
                            $('#jpeg-format-baseline').prop('checked', true);
                        } else {
                            $('#jpeg-format-progressive').prop('checked', true);
                        }

                        $('#jpeg_quality').val(ioDefaultConfig.jpeg_quality);

                        if (ioDefaultConfig.upscale === false) {
                            $('#upscaling-no').prop('checked', true);
                        } else {
                            $('#upscaling-yes').prop('checked', true);
                        }

                        if (ioDefaultConfig.resize_filter === 'lanczos3') {
                            $('#resize-filter-lancsoz3').prop('checked', true);
                        } else if (ioDefaultConfig.resize_filter === 'lanczos2') {
                            $('#resize-filter-lancsoz2').prop('checked', true);
                        } else if (ioDefaultConfig.resize_filter === 'bicubic') {
                            $('#resize-filter-bicubic').prop('checked', true);
                        } else if (ioDefaultConfig.resize_filter === 'bilinear') {
                            $('#resize-filter-bilinear').prop('checked', true);
                        } else {
                            $('#resize-filter-nearest').prop('checked', true);
                        }
                    }
                });
            });
        });

        /**
         * Image Optimization VCL snippet upload button click event
         *
         * @description checks the Fastly service status and displays the Image Optimization VCL snippet upload overlay
         */
        ioBtn.on('click', function () {
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
                    return errorIoBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                getIoSnippetStatus(active_version, true).done(function (response) {
                    overlay(ioOptions);
                    setServiceLabel(active_version, next_version, service_name);

                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('You are about to enable the Fastly Image Optimization snippet'));
                    } else {
                        $('.modal-title').text($.mage.__('You are about to remove the Fastly Image Optimization snippet'));
                    }
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });

            }).fail(function () {
                return errorIoBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });
    }
});