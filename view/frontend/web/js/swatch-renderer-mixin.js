define([
    'mage/utils/wrapper',
    'underscore'
], function (wrapper, _) {
    'use strict';

    return function (swatchRenderer) {

        return wrapper.wrap(swatchRenderer, function (create, config, element) {
            if (_.isUndefined(config))
                return create(config, element);

            if (_.isUndefined(config.jsonConfig))
                return create(config, element);

            if (_.isUndefined(config.jsonConfig.images))
                return create(config, element);

            let wdpr = window.devicePixelRatio;

            _.each(config.jsonConfig.images, function (images) {
                _.each(images, function (imageObject) {
                    if (_.isUndefined(imageObject.fastly_srcset))
                        return;

                    if (!_.has(imageObject.fastly_srcset, wdpr))
                        return;

                    imageObject.img = imageObject.fastly_srcset[wdpr];
                });
            });

            create(config, element);
        });
    };
});
