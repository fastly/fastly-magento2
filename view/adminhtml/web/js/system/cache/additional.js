define(
    [
        'uiComponent',
        'jquery',
        'mage/mage'
    ],
    function (Component, $) {
        return Component.extend({
            initialize: function () {
                $('#quick_purge_form').mage('form').mage('validation');
            }
        });
    }
);