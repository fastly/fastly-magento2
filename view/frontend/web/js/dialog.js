define(
    [
        'uiComponent',
        'jquery',
        'Magento_Ui/js/modal/confirm'
    ],
    function (Component, $, modal) {
        return Component.extend({
            initialize: function (config) {
                let redirectUrl = config.redirect_url;
                let message = config.message;
                if (redirectUrl.length > 0 && message.length > 0) {
                    let options = {
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