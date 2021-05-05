define([
    'mage/utils/wrapper',
    'underscore'
], function (wrapper, _) {
    'use strict';

    return function (gallery) {

        return wrapper.wrap(gallery, function (initialize, config, element) {

            if (_.isUndefined(config.data))
                return initialize(config, element);

            let wdpr = window.devicePixelRatio;

            _.each(config.data, function (imageObject) {

                if (_.isUndefined(imageObject.fastly_srcset))
                    return;

                if (!_.has(imageObject.fastly_srcset, wdpr))
                    return;

                imageObject.img = imageObject.fastly_srcset[wdpr];
            });

            initialize(config, element);
        });
    };
});
