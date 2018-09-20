define([
    "jquery",
    "resetAllMessages",
    'mage/template',
    'mage/translate'
], function ($, resetAllMessages) {
    return function (config) {
        let testSuccessBtnMsg = $('#fastly-test-success-button-msg');
        let testErrorBtnMsg = $('#fastly-test-error-button-msg');

        /**
         * Test connection button on click event
         */
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
                    if (response.status === false) {
                        return testErrorBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                    } else {
                        return testSuccessBtnMsg.text($.mage.__('Connection to service name ' + response.service_name + ' has been successfully established. Please, save configuration and clear cache.')).show();
                    }
                },
                error: function () {
                    return testErrorBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        });
    };
});
