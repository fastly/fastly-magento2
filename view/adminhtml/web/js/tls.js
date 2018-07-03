define([
    "jquery",
    'mage/template',
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function ($) {
    return function (config, checkService) {

            var requestStateSpan = $('#request_state_span');
            var requestStateMsgSpan = $('#fastly_request_state_message_span');

            $('#system_full_page_cache_fastly_fastly_advanced_configuration-head').unbind('click').on('click', function () {
                if ($(this).attr("class") === "open") {
                    var tls = getTlsSetting(checkService.active_version, false);

                    tls.done(function (checkReqSetting) {
                        requestStateSpan.find('.processing').hide();
                        var tlsStateEnabled = requestStateMsgSpan.find('#force_tls_state_enabled');
                        var tlsStateDisabled = requestStateMsgSpan.find('#force_tls_state_disabled');
                        if (checkReqSetting.status !== false) {
                            if (tlsStateDisabled.is(":hidden")) {
                                tlsStateEnabled.show();
                            }
                        } else {
                            if (tlsStateEnabled.is(":hidden")) {
                                tlsStateDisabled.show();
                            }
                        }
                    }).fail(function () {
                        requestStateSpan.find('.processing').hide();
                        requestStateMsgSpan.find('#force_tls_state_unknown').show();
                    });
                }
            });

            function getTlsSetting (active_version, loaderVisibility) {
                return $.ajax({
                    type: "POST",
                    url: config.checkTlsSettingUrl,
                    showLoader: loaderVisibility,
                    data: {'active_version': active_version}
                });
            }
        }
});