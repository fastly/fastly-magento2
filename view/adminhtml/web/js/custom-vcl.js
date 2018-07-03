define([
    "jquery",
    'mage/template',
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function ($) {
    return function (config) {
        $(document).ready(function () {

            $('#fastly_custom_vcl_button').on('click', function () {

                if (isAlreadyConfigured != true) {
                    $(this).attr('disabled', true);
                    return alert($.mage.__('Please save config prior to continuing.'));
                }

                // vcl.resetAllMessages();

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

                }).fail(function () {
                    return errorCustomSnippetBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                });
            });
        });
    }
});