define([
    'underscore'
], function (_) {
    'use strict';

    return function (gallery) {
        return gallery.extend({
            initialize: function (config, element) {
                if (_.isUndefined(config) || _.isEmpty(config))
                    return this._super(config, element);

                if (_.isUndefined(config.data) || _.isEmpty(config.data))
                    return this._super(config, element);

                let wdpr = window.devicePixelRatio;

                _.each(config.data, function (imageObject) {

                    if (_.isUndefined(imageObject.fastly_srcset))
                        return;

                    if (!_.has(imageObject.fastly_srcset, wdpr))
                        return;

                    imageObject.img = imageObject.fastly_srcset[wdpr];
                });

                this._super(config, element);
            }
        });
    };
});
