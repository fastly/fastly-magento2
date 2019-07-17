define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {
        //todo: get admin front name i timeout value
        //todo: get pass.vcl and change ADMIN_PATH_TIMEOUT and ADMIN_PATH
        //todo: update vcl when user save's configuration
        let time = $("#system_full_page_cache_fastly_fastly_advanced_configuration_admin_path_timeout").val();
        let adminPath;

        $.ajax({
           type: 'GET',
            data: {active_version: '123'},
            url: config.adminUrl,
            success: function (response) {
                console.log(response);
            }
        });
    }
});