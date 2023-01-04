define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    return function (swatchRenderer) {

        $.widget('mage.SwatchRenderer', swatchRenderer, {

            _init: function () {
                if (_.isUndefined(this.options) || _.isEmpty(this.options))
                        return this._super();

                if (_.isUndefined(this.options.jsonConfig) || _.isEmpty(this.options.jsonConfig))
                    return this._super();

                if (_.isUndefined(this.options.jsonConfig.images) || _.isEmpty(this.options.jsonConfig.images))
                    return this._super();

                let wdpr = window.devicePixelRatio;

                _.each(this.options.jsonConfig.images, function (images) {
                    _.each(images, function (imageObject) {
                        if (_.isUndefined(imageObject.fastly_srcset))
                            return;

                        if (!_.has(imageObject.fastly_srcset, wdpr))
                            return;

                        imageObject.img = imageObject.fastly_srcset[wdpr];
                    });
                });

                return this._super();
            },
            updateBaseImage: function (images, context, isInProductView){
                this._super(images, context, isInProductView);
                //add logic for srcset
                //context.find('.product-image-photo').attr('srcset', justAnImage.img);
            }
        });

        return $.mage.SwatchRenderer;
    };
});
