define([
    "jquery",
    'mage/template',
    'mage/translate'
], function ($) {
    return function (config) {

        $(document).ready(function () {
            let allOpen = '';
            let allActive = '';
            let active_version = '';
            let next_version = '';
            let fastlyFieldset = $('#system_full_page_cache_fastly');
            let isAlreadyConfigured = true;
            let serviceStatus = false;

            /**
             * Fastly Configuration head on click event
             */
            $('#system_full_page_cache_fastly-head').one('click', function () {
                if ($(this).attr("class") === "open") {
                    init();
                    if (allOpen !== '') {
                        allOpen.trigger('click');
                    }
                } else {
                    allOpen = fastlyFieldset.find(".open");
                    allActive = fastlyFieldset.find(".active");
                    allOpen.removeClass("open").removeClass("open");
                    allActive.find(".active").removeClass("active");
                }
            });

            $('#system_full_page_cache_fastly_edge_modules-head').one('click', function () {
                modlyInit();
            });

            /**
             * Initializes the Fastly Configuration
             */
            function init()
            {

                $('body').loader('show');
                $.ajax({
                    type: "GET",
                    url: config.isAlreadyConfiguredUrl,
                }).done(function (response) {
                    if (response.status === true) {
                        isAlreadyConfigured = response.flag;
                    }
                });

                let advancedConfigurationHead = $('#system_full_page_cache_fastly_fastly_advanced_configuration-head');
                let blockingConfigurationHead = $('#system_full_page_cache_fastly_fastly_blocking-head');
                let imageOptimizationConfigurationHead = $('#system_full_page_cache_fastly_fastly_image_optimization_configuration-head');
                let basicAuthenticationHead = $('#system_full_page_cache_fastly_fastly_basic_auth-head');
                let edgeDictionariesHead = $('#system_full_page_cache_fastly_fastly_edge_dictionaries-head');
                let edgeAclHead = $('#system_full_page_cache_fastly_fastly_edge_acl-head');
                let customSyntheticPagesHead = $('#system_full_page_cache_fastly_fastly_error_maintenance_page-head');
                let backendsHead = $('#system_full_page_cache_fastly_fastly_backend_settings-head');
                let customSnippetsHead = $('#system_full_page_cache_fastly_fastly_custom_snippets-head');
                let webApplicationFirewallHead = $('#system_full_page_cache_fastly_fastly_web_application_firewall-head');
                let maintenanceSupportHead = $('#system_full_page_cache_fastly_fastly_maintenance_support-head');
                let domainsHead = $('#system_full_page_cache_fastly_fastly_domains-head');
                let rateLimitingHead = $('#system_full_page_cache_fastly_fastly_rate_limiting_settings-head');
                let importExportHead = $('#system_full_page_cache_fastly_fastly_import_export-head');
                let versionHistoryHead = $('#system_full_page_cache_fastly_fastly_tools-head');
                $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_owasp_restricted_extensions').hide();
                $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_owasp_allowed_methods').hide();
                $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_waf_bypass').hide();
                $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_update_waf_bypass').hide();
                $('#row_system_full_page_cache_fastly_fastly_web_application_firewall_waf_allow_by_acl').hide();

                requirejs(['fastlyTestConnection'], function (fastlyTestConnection) {
                    fastlyTestConnection(config);
                });

                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                }).done(function (checkService) {

                    if (checkService.status !== false) {
                        $('body').loader('hide');
                        active_version = checkService.active_version;
                        next_version = checkService.next_version;
                        serviceStatus = checkService;

                        requirejs(['uploadVcl'], function (uploadVcl) {
                            uploadVcl(config, serviceStatus, isAlreadyConfigured);
                        });

                        advancedConfigurationHead.one('click', function () {
                            requirejs(['tls'], function (tls) {
                                tls(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        blockingConfigurationHead.one('click', function () {
                            requirejs(['blocking'], function (blocking) {
                                blocking(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        imageOptimizationConfigurationHead.one('click', function () {
                            requirejs(['imageOptimization'], function (imageOptimization) {
                                imageOptimization(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        basicAuthenticationHead.one('click', function () {
                            requirejs(['basicAuthentication'], function (basicAuthentication) {
                                basicAuthentication(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        edgeDictionariesHead.one('click', function () {
                            requirejs(['dictionaries'], function (dictionaries) {
                                dictionaries(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        edgeAclHead.one('click', function () {
                            requirejs(['acl'], function (acl) {
                                acl(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        customSyntheticPagesHead.one('click', function () {
                            requirejs(['customSyntheticPages'], function (customSyntheticPages) {
                                customSyntheticPages(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        backendsHead.one('click', function () {
                            requirejs(['backends'], function (backends) {
                                backends(config, serviceStatus, isAlreadyConfigured);
                            })
                        });

                        customSnippetsHead.one('click', function () {
                            requirejs(['customSnippets'], function (customSnippets) {
                                customSnippets(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        webApplicationFirewallHead.one('click', function () {
                            requirejs(['waf'], function (waf) {
                                waf(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        domainsHead.one('click', function () {
                            requirejs(['domains'], function (domains) {
                                domains(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        rateLimitingHead.one('click', function () {
                            requirejs(['rateLimiting'], function (rateLimiting) {
                                rateLimiting(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        maintenanceSupportHead.one('click', function () {
                            requirejs(['maintenance'], function (maintenance) {
                                maintenance(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        importExportHead.one('click', function () {
                             requirejs(['fastlyImport'], function (fastlyImport) {
                                 fastlyImport(config, serviceStatus, isAlreadyConfigured);
                             });
                            requirejs(['fastlyExport'], function (fastlyExport) {
                                fastlyExport(config, serviceStatus, isAlreadyConfigured);
                            });
                        });

                        versionHistoryHead.one('click', function () {

                            requirejs(['versionHistory'], function (versionHistory) {
                                versionHistory(config, serviceStatus, isAlreadyConfigured);
                            });
                        });
                    } else {
                        $(".processing").hide();
                        $(".state_unknown").show();
                        $(".list-loading").hide();
                    }
                })
            }

            function modlyInit()
            {
                requirejs(['modly'], function (uploadVcl) {
                    uploadVcl(config, serviceStatus, isAlreadyConfigured);
                });
            }
        });
    }
});