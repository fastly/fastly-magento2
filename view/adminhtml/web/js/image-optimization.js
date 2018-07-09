define([
    "jquery",
    "setServiceLabel",
    "resetAllMessages",
    "showErrorMessage",
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function ($, setServiceLabel, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* Image optimization state elements*/
        var ioStateSpan = $('#io_state_span');
        var ioStateMsgSpan = $('#fastly_io_state_message_span');
        var io = true;
        /* Image optimization button messages */
        var successIoBtnMsg = $('#fastly-success-io-button-msg');
        var errorIoBtnMsg = $('#fastly-error-io-button-msg');
        var warningIoBtnMsg = $('#fastly-warning-io-button-msg');

        var successIoMsg = $('#fastly-success-io-msg');
        var errorIoMsg = $('#fastly-error-io-msg');
        var warningIoMsg = $('#fastly-warning-io-msg');

        var ioBtn = $('#fastly_push_image_config');
        var ioConfigBtn = $('#fastly_io_default_config');
        var ioToggle = $('#system_full_page_cache_fastly_fastly_image_optimization_configuration_image_optimizations');

        ioStateSpan.find('.processing').show();

        var ioOptions = {
            id: 'fastly-image-options',
            title: jQuery.mage.__('Activate image optimization'),
                content: function () {
                return document.getElementById('fastly-image-template').textContent;
            },
            actionOk: function () {
                toggleIo(active_version);
            }
        };

        var ioDefaultOptions = {
            id: 'fastly-io-default-config-options',
            title: jQuery.mage.__('Image optimization default config options'),
                content: function () {
                return document.getElementById('fastly-io-default-config-options-template').textContent;
            },
            actionOk: function () {
                configureIo(active_version);
            }
        };

        getIoServiceStatus(false).done(function (response) {
            if (response.status === false) {
                if (config.isIoEnabled) {
                    ioToggle.removeAttrs('disabled');
                    ioConfigBtn.addClass('disabled');
                } else {
                    ioToggle.attr('disabled', 'disabled');
                    ioConfigBtn.removeClass('disabled');
                }
            }
        });

        getIoSetting(serviceStatus.active_version, false).done(function (response) {
            ioStateSpan.find('.processing').hide();
            var ioStateEnabled = ioStateMsgSpan.find('#io_state_enabled');
            var ioStateDisabled = ioStateMsgSpan.find('#io_state_disabled');

            if (response.status === true) {
                if (ioStateDisabled.is(":hidden")) {
                    ioStateEnabled.show();
                }

                getIoServiceStatus(false).done(function (ioService) {
                    if (ioService.status === true) {
                        ioBtn.removeClass('disabled');
                        warningIoBtnMsg.hide();
                    } else {
                        warningIoBtnMsg.text(
                            $.mage.__(
                                'Please contact your sales rep or send an email to support@fastly.com to request image optimization activation for your Fastly service.'
                            )
                        ).show();
                    }
                });
            } else if (response.status === false) {
                if (ioStateEnabled.is(":hidden")) {
                    ioStateDisabled.show();
                }
                getIoServiceStatus(false).done(function (response) {
                    if (response.status === true) {
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
                });
            } else {
                ioStateMsgSpan.find('#io_state_unknown').show();
            }
        }).fail(function () {
            ioStateSpan.find('.processing').hide();
            ioStateMsgSpan.find('#io_state_unknown').show();
        });

        // Queries Fastly API to retrieve image optimization snippet setting
        function getIoSetting(active_version, loaderVisibility) {
            return $.ajax({
                type: "POST",
                url: config.checkImageSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        // Queries Fastly Api to check if image optimization is enabled for the service
        function getIoServiceStatus() {
            return $.ajax({
                type: "GET",
                url: config.checkFastlyIoSettingUrl
            });
        }

        function getIoDefaultConfig(active_version) {
            $.ajax({
                type: "POST",
                url: config.listIoDefaultConfigOptions,
                showLoader: true,
                data: {'active_version': active_version}
            });
        }

        ioConfigBtn.on('click', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            successIoMsg.hide();
            errorIoMsg.hide();
            warningIoMsg.hide();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (service) {

                if (service.status === false) {
                    return errorIoMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                var active_version = service.active_version;
                var next_version = service.next_version;
                var service_name = service.service.name;

                getIoDefaultConfig(active_version).done(function (response) {
                    if (response.status === true) {
                        var ioDefaultConfig = response.io_options;

                        requirejs(['popup'], function (popup) {
                            popup(ioDefaultOptions);
                        });
                        setServiceLabel(active_version, next_version, service_name);

                        if (ioDefaultConfig.webp === false) {
                            $('#webp-no').prop('checked', true);
                        } else {
                            $('#webp-yes').prop('checked', true);
                        }

                        $('#webp_quality').val(ioOptions.webp_quality);

                        if (ioDefaultConfig.jpeg_type === 'auto') {
                            $('#jpeg-format-auto').prop('checked', true);
                        } else if (ioOptions.jpeg_type === 'baseline') {
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

                var active_version = service.active_version;
                var next_version = service.next_version;
                var service_name = service.service.name;
                getIoSetting(active_version, true).done(function (response) {
                    if (response.status === false) {
                        $('.modal-title').text($.mage.__('We are about to upload the Fastly image optimization snippet'));
                    } else {
                        $('.modal-title').text($.mage.__('We are about to remove the Fastly image optimization snippet'));
                    }
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'))
                });
                requirejs(['popup'], function (popup) {
                    popup(ioOptions);
                });
                setServiceLabel(active_version, next_version, service_name);

            }).fail(function () {
                return errorIoBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });
    }
});