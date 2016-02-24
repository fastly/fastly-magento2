###############################################################################
#
# Fastly CDN for Magento 2
#
# NOTICE OF LICENSE
#
# This source file is subject to the Fastly CDN for Magento 2 End User License
# Agreement that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
#
# @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
# @license     BSD, see LICENSE_FASTLY_CDN.txt
#
###############################################################################

    if (req.url ~ "^/(pub/)?(media|static)/.*") {
        if (req.http.user-agent ~ "(?pattern)?i") {
            set req.hash += "value_for_pattern";
        }
    }