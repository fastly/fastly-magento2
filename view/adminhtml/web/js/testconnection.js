define([
    "jquery",
    'mage/template',
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function($){

    return function (config) {

        successBtnMsg = $('#fastly-test-success-button-msg');
        errorBtnMsg = $('#fastly-test-error-button-msg');
        warningBtnMsg = $('#fastly-test-warning-button-msg');

        $('#fastly_test_connection_button').on('click', function () {
            resetAllMessages();
            $.ajax({
                type: "POST",
                url: config.testServiceUrl,
                showLoader: true,
                data: {
                    'service_id': $('#system_full_page_cache_fastly_fastly_service_id').val(),
                    'api_key': $('#system_full_page_cache_fastly_fastly_api_key').val()
                },
                cache: false,
                success: function (response) {
                    if (response.status == false) {
                        return errorBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                    } else {
                        return successBtnMsg.text($.mage.__('Connection to service name ' + response.service_name + ' has been succesfully established. Please, save configuration and clear cache.')).show();
                    }
                },
                error: function (msg) {
                    return errorBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        });
    };

    function resetAllMessages() {
        successBtnMsg.text();
        successBtnMsg.hide();
        errorBtnMsg.text();
        errorBtnMsg.hide();
        warningBtnMsg.text();
        warningBtnMsg.hide();
    }
});
