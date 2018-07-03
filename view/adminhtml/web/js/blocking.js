define([
    "jquery",
    'mage/template',
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function ($) {
    return function (config, checkService) {

        var blockingStateSpan = $('#blocking_state_span');
        var blockingStateMsgSpan = $('#fastly_blocking_state_message_span');

        //
        $('#system_full_page_cache_fastly_fastly_blocking-head').unbind('click').on('click', function () {
            if ($(this).attr("class") === "open") {
                var blocking = getBlockingSetting(checkService.active_version, false);

                blocking.done(function (checkReqSetting) {
                    blockingStateSpan.find('.processing').hide();
                    var blockingStateEnabled = blockingStateMsgSpan.find('#blocking_state_enabled');
                    var blockingStateDisabled = blockingStateMsgSpan.find('#blocking_state_disabled');
                    if (checkReqSetting.status !== false) {
                        if (blockingStateDisabled.is(":hidden")) {
                            blockingStateEnabled.show();
                        }
                    } else {
                        if (blockingStateEnabled.is(":hidden")) {
                            blockingStateDisabled.show();
                        }
                    }
                }).fail(function () {
                    blockingStateSpan.find('.processing').hide();
                    blockingStateMsgSpan.find('#blocking_state_unknown').show();
                });
            }
        });

        // Queries Fastly API to retrieve blocking setting
        function getBlockingSetting (active_version, loaderVisibility) {
            return $.ajax({
                type: "POST",
                url: config.checkBlockingSettingUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }
    }
});