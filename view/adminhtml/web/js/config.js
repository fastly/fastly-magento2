define([
    "jquery",
    'mage/template',
    "Magento_Ui/js/modal/modal",
    'mage/translate'
], function ($) {
    var uploadVclConfig = {
        'fastly-uploadvcl-options': {
            title: jQuery.mage.__('You are about to upload VCL to Fastly '),
                content: function () {
                return document.getElementById('fastly-uploadvcl-template').textContent;
            },
            actionOk: function () {
                vcl.submitVcl(active_version);
            }
        },
        'fastly-custom-snippet-options': {
            title: jQuery.mage.__('You are about to create a custom snippet '),
                content: function () {
                return document.getElementById('fastly-custom-snippet-template').textContent;
            },
            actionOk: function () {
                vcl.setCustomSnippet();
            }
        },
        'fastly-tls-options': {
            title: jQuery.mage.__(''),
                content: function () {
                return document.getElementById('fastly-tls-template').textContent;
            },
            actionOk: function () {
                vcl.toggleTls(active_version);
            }
        },
        'fastly-image-options': {
            title: jQuery.mage.__('Activate image optimization'),
                content: function () {
                return document.getElementById('fastly-image-template').textContent;
            },
            actionOk: function () {
                vcl.pushImageConfig(active_version);
            }
        },
        'fastly-blocking-options': {
            title: jQuery.mage.__(''),
                content: function () {
                return document.getElementById('fastly-blocking-template').textContent;
            },
            actionOk: function () {
                vcl.toggleBlocking(active_version);
            }
        },
        'fastly-auth-options': {
            title: jQuery.mage.__(''),
                content: function () {
                return document.getElementById('fastly-auth-template').textContent;
            },
            actionOk: function () {
                vcl.toggleAuth(active_version);
            }
        },
        'fastly-backend-options': {
            title: jQuery.mage.__(''),
                content: function () {
                return document.getElementById('fastly-backend-template').textContent;
            },
            actionOk: function () {
                if ($('#backend-upload-form').valid()) {
                    vcl.configureBackend(active_version);
                }
            }
        },
        'fastly-error-page-options': {
            title: jQuery.mage.__('Update Error Page Content'),
                content: function () {
                return document.getElementById('fastly-error-page-template').textContent;
            },
            actionOk: function () {
                vcl.saveErrorHtml(active_version);
            }
        },
        'fastly-waf-page-options': {
            title: jQuery.mage.__('Update WAF Page Content'),
                content: function () {
                return document.getElementById('fastly-waf-page-template').textContent;
            },
            actionOk: function () {
                vcl.saveWafHtml(active_version);
            }
        },
        'fastly-dictionary-container-options': {
            title: jQuery.mage.__('Create dictionary container'),
                content: function () {
                return document.getElementById('fastly-dictionary-container-template').textContent;
            },
            actionOk: function () {
                vcl.createDictionary(active_version);
            }
        },
        'fastly-delete-dictionary-container-options': {
            title: jQuery.mage.__('Delete dictionary containers'),
                content: function () {
                return document.getElementById('fastly-delete-dictionary-container-template').textContent;
            },
            actionOk: function () {
                vcl.deleteDictionary(active_version);
            }
        },
        'fastly-delete-acl-container-options': {
            title: jQuery.mage.__('Delete ACL container'),
                content: function () {
                return document.getElementById('fastly-delete-acl-container-template').textContent;
            },
            actionOk: function () {
                vcl.deleteAcl(active_version);
            }
        },
        'fastly-acl-container-options': {
            title: jQuery.mage.__('Create ACL container'),
                content: function () {
                return document.getElementById('fastly-acl-container-template').textContent;
            },
            actionOk: function () {
                vcl.createAcl(active_version);
            }
        },
        'fastly-auth-container-options': {
            title: jQuery.mage.__('Create container for authenticated users'),
                content: function () {
                return document.getElementById('fastly-auth-container-template').textContent;
            },
            actionOk: function () {
                vcl.createAuth(active_version);
            }
        },
        'fastly-auth-container-delete': {
            title: jQuery.mage.__('Delete all authenticated users'),
                content: function () {
                return document.getElementById('fastly-auth-delete-template').textContent;
            },
            actionOk: function () {
                vcl.deleteMainAuth(active_version);
            }
        },
        'fastly-edge-items': {
            title: jQuery.mage.__('Dictionary items'),
                content: function () {
                return document.getElementById('fastly-edge-items-template').textContent;
            },
            actionOk: function () {
            }
        },
        'fastly-acl-items': {
            title: jQuery.mage.__('Acl items'),
                content: function () {
                return document.getElementById('fastly-acl-items-template').textContent;
            },
            actionOk: function () {
            }
        },
        'fastly-auth-items': {
            title: jQuery.mage.__('Basic Auth users'),
                content: function () {
                return document.getElementById('fastly-auth-items-template').textContent;
            },
            actionOk: function () {
            }
        },
        'fastly-io-default-config-options': {
            title: jQuery.mage.__('Image optimization default config options'),
                content: function () {
                return document.getElementById('fastly-io-default-config-options-template').textContent;
            },
            actionOk: function () {
                vcl.configureIo(active_version);
            }
        }
    }
});