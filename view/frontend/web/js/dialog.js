define(
    [
        'uiComponent',
        'jquery',
        'Magento_Ui/js/modal/confirm'
    ],
    function (Component, $, modal) {
        return Component.extend({
            initialize: function (config) {
                var redirectUrl = config.redirect_url;
                var message = config.message;
                if (redirectUrl.length > 0 && message.length > 0) {
                    var options = {
                        responsive: true,
                        content: message,
                        actions: {
                            confirm: function () {
                                location.href = redirectUrl;
                            }
                        }
                    };
                    modal(options);
                }
            }
        });
    }
);