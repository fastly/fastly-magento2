define([
    "jquery",
    "Magento_Ui/js/modal/modal"
], function ($) {
    return function showOverlay(options)
    {
        let self = this;
        let divId = options.id;

        /**
         * Modal overlay options
         *
         * @type {*|jQuery}
         */
        this.modal = $('<div/>').attr({id: divId}).html(options.content()).modal({
            modalClass: 'magento',
            title: options.title,
            type: 'slide',
            closed: function (e, modal) {
                modal.modal.remove();
            },
            opened: function () {
                if (options.opened) {
                    options.opened.call(self);
                }
            },
            buttons: [{
                text: $.mage.__('Cancel'),
                'class': 'action cancel',
                click: function () {
                    this.closeModal();
                }
            }, {
                text: $.mage.__('Upload'),
                'class': 'action primary upload-button',
                click: function () {
                    options.actionOk.call(self);
                }
            }]
        });
        this.modal.modal('openModal');
    }
});