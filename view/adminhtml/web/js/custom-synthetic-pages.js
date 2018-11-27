define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* Error page HTML button */
        let successHtmlBtnMsg = $('#fastly-success-html-page-button-msg');
        let errorHtmlBtnMsg = $('#fastly-error-html-page-button-msg');
        /* WAF page HTML button */
        let successWafBtnMsg = $('#fastly-success-waf-page-button-msg');
        let errorWafBtnMsg = $('#fastly-error-waf-page-button-msg');

        let active_version = serviceStatus.active_version;

        let maxChars = 65535;
        let msgWarning = $('.fastly-message-error');

        let wafPageRow = $('#row_system_full_page_cache_fastly_fastly_error_maintenance_page_waf_page');
        let wafPage = getWafPageRespObj(active_version);

        wafPage.done(function (checkWafResponse) {
            if (checkWafResponse.status === false) {
                wafPageRow.hide();
            }
        });

        let errorPageOptions = {
            title: jQuery.mage.__('Update Error Page Content'),
                content: function () {
                return document.getElementById('fastly-error-page-template').textContent;
            },
            actionOk: function () {
                saveErrorHtml(active_version);
            }
        };

        let wafPageOptions = {
            title: jQuery.mage.__('Update WAF Page Content'),
                content: function () {
                return document.getElementById('fastly-waf-page-template').textContent;
            },
            actionOk: function () {
                saveWafHtml(active_version);
            }
        };

        getWafPageRespObj(active_version, false).done(function (checkWafResponse) {
            if (checkWafResponse.status !== false) {
                wafPageRow.show();
            }
        });

        // Queries Fastly API to retrieve error page response object
        function getErrorPageRespObj(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.getErrorPageRespObj,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        // Queries Fastly API to retrieve WAF page response object
        function getWafPageRespObj(active_version)
        {
            return $.ajax({
                type: "GET",
                url: config.getWafPageRespObj,
                showLoader: true,
                data: {'active_version': active_version}
            });
        }

        // Save Error Page Html
        function saveErrorHtml()
        {
            let activate_vcl = false;

            if ($('#fastly_activate_vcl').is(':checked')) {
                activate_vcl = true;
            }
            let errorHtmlChars = $('#error_page_html').val().length;
            if (errorHtmlChars >= maxChars) {
                msgWarning.text($.mage.__('The HTML must contain less than ' + maxChars + ' characters. Current number of characters: ' + errorHtmlChars));
                msgWarning.show();
                return;
            }
            $.ajax({
                type: "POST",
                url: config.saveErrorPageHtmlUrl,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_vcl,
                    'html': $('#error_page_html').val()
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        successHtmlBtnMsg.text($.mage.__('Error page HTML is successfully updated.')).show();
                        active_version = response.active_version;
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return errorHtmlBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        // Save WAF Page Html
        function saveWafHtml()
        {
            let activate_vcl = false;

            if ($('#fastly_activate_vcl').is(':checked')) {
                activate_vcl = true;
            }
            let wafHtmlChars = $('#waf_page_content').val().length;
            if (wafHtmlChars >= maxChars) {
                msgWarning.text($.mage.__('The content must contain less than ' + maxChars + ' characters. Current number of characters: ' + wafHtmlChars));
                msgWarning.show();
                return;
            }
            $.ajax({
                type: "POST",
                url: config.saveWafPageUrl,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_vcl,
                    'content': $('#waf_page_content').val(),
                    'status': $('#waf_page_status').val(),
                    'content_type': $('#waf_page_type').val()
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        successWafBtnMsg.text($.mage.__('WAF page is successfully updated.')).show();
                        active_version = response.active_version;
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return errorWafBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        /**
         * Set Error Page HTML button
         */
        $('#fastly_error_page_button').on('click', function () {
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
                    return errorHtmlBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                getErrorPageRespObj(active_version, true).done(function (response) {
                    overlay(errorPageOptions);
                    setServiceLabel(active_version, next_version, service_name);

                    $('.upload-button span').text('Update');
                    if (response.status === true) {
                        $('#error_page_html').text(response.errorPageResp.content).html();
                    }
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });

            }).fail(function () {
                return errorHtmlBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Set WAF Page HTML button
         */
        $('#fastly_waf_page_button').on('click', function () {
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
                    return errorWafBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                getWafPageRespObj(active_version, true).done(function (response) {
                    overlay(wafPageOptions);
                    setServiceLabel(active_version, next_version, service_name);

                    $('.upload-button span').text('Update');
                    if (response.status === true) {
                        $('#waf_page_content').text(response.wafPageResp.content).html();
                        $('#waf_page_status').val(response.wafPageResp.status);
                        $('#waf_page_type').val(response.wafPageResp.content_type);
                    }
                }).fail(function () {
                    showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                });

            }).fail(function () {
                return errorWafBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });
    }
});