define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    return function (swatchRenderer) {

        $.widget('mage.SwatchRenderer', swatchRenderer, {
            lastImageResponse: null,

            imageSizes: [
                'large',
                'medium',
                'small',
            ],

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

            _ProductMediaCallback: function ($this, response, isInProductView) {
                this.lastImageResponse = response;
                return this._super($this, response, isInProductView);
            },

            updateBaseImage: function (images, context, isInProductView) {
                this._super(images, context, isInProductView);
                if (isInProductView || !this.lastImageResponse) {
                    return;
                }

                var currentSrc = context.find('.product-image-photo').attr('src');
                if (!currentSrc) {
                    return;
                }

                var srcset = this.findEquivalentSrcset(currentSrc);
                if (srcset) {
                    context.find('.product-image-photo').attr('srcset', srcset);
                }
            },

            findEquivalentSrcset: function (src) {
                if (!this.lastImageResponse || !this.lastImageResponse.fastly_srcset) {
                    return null;
                }

                var srcset = this.findSrcsetInImageResponse(src, this.lastImageResponse);
                if (srcset) {
                    return srcset;
                }

                var gallery = this.lastImageResponse.gallery;
                if (!gallery) {
                    return null;
                }

                for (var i in gallery) {
                    if (!gallery.hasOwnProperty(i)) {
                        continue;
                    }

                    srcset = this.findSrcsetInImageResponse(src, gallery[i]);
                    if (srcset) {
                        return srcset;
                    }
                }

                return null;
            },

            findSrcsetInImageResponse: function (src, list) {
                if (!list.fastly_srcset) {
                    return null;
                }

                for (var i = 0; i < this.imageSizes.length; i++) {
                    var size = this.imageSizes[i];
                    if (list[size] && list[size] === src) {
                        return list.fastly_srcset[size];
                    }
                }

                return null;
            }
        });

        return $.mage.SwatchRenderer;
    };
});
