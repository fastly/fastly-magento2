define(
    [
        'uiComponent'
    ],
    function (Component) {
        return Component.extend({
            initialize: function (config) {
                var redirectUrl = config.redirect_url;
                if (redirectUrl.length > 0) {
                    document.location.href = redirectUrl;
                }
            }
        });
    }
);