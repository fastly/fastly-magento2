define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    "showWarningMessage",
    "Magento_Ui/js/modal/confirm",
    "mage/translate"
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, showSuccessMessage, showWarningMessage, confirm) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let domains;
        let certificates;
        let fastlyDomainsTimer;
        let fastlyCertificatesTimer;
        let tableRow;

        /** Domains messages */
        let domainErrorButtonMsg = $('#fastly-error-tls-domains-button');
        let domainSuccessButtonMsg = $('#fastly-success-tls-domains-button');
        let domainWarningButtonMsg = $('#fastly-warning-tls-domains-button');

        /** Certificates messages */
        let certErrorButtonMsg = $('#fastly-error-tls-certifications-button');
        let certSuccessButtonMsg = $('#fastly-success-tls-certifications-button');
        let certWarningButtonMsg =$('#fastly-warning-tls-certifications-button');

        /** Messages */
        let notAuthorisedMsg = $('#fastly-warning-not-authorized-button-msg');

        /** Modal windows properties */
        let anotherDomainModalSettings = {
            title: $.mage.__('Enter the domain you want Fastly to secure'),
            content: function () {
                return document.getElementById('fastly-tls-new-domains-template').textContent
            }
        };

        let certificateModalSettings = {
            title: $.mage.__('Upload the matching certificate.'),
            content: function () {
                return document.getElementById('fastly-tls-certificate-template').textContent
            }
        };

        let domainLoader = true;
        let fastlyGetMeDomains = function () {
           return getTlsDomains(domainLoader).done(function (response) {
               domainLoader = false;
                let html = '';
                if (response.status !== true || response.flag !== true) {
                    $('#secure-another-domain').attr('disabled', true);
                    return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                domains = response.domains;
                $('.loading-tls-domains').hide();
                if (domains.length !== 0) {
                    $.each(domains, function (i, domain) {
                        html += generateSecuredDomainsTableFields(domain);
                        domains[domain.id] = domain;
                    });
                    $('#tls-domains-item-container').empty();
                    $('#tls-domains-item-container').append(html);
                    return;
                }

                $('.no-tls-domains').show();
            });
        };

        let certificatesLoader = true;
        let fastlyGetMeCertificates = function () {
            return getTlsCertificates(certificatesLoader).done(function (response) {
                certificatesLoader = false;
                let html = '';
                if (response.status !== true || response.flag !== true) {
                    $('#secure-certificate').attr('disabled', true);
                    return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                $('.loading-tls-certificates').hide();
                certificates = response.certificates;
                if (certificates.length !== 0) {
                    $.each(certificates, function (i, certificate) {
                        html += generateCertificateTableBody(certificate.attributes.name, certificate.attributes.issuer, certificate.attributes.issued_to, certificate.id);
                    });

                    $('#tls-certificates-item-container').empty();
                    $('#tls-certificates-item-container').append(html);
                    return;
                }

                $('.no-tls-certificates').show();
            });
        };

        fastlyDomainsTimer = setInterval(function () {
            return fastlyGetMeDomains();
        }, 5000);

        fastlyCertificatesTimer = setInterval(function () {
            return fastlyGetMeCertificates();
        }, 5000);

        $('body').on('click', '.show-domain-info', function (event) {
            tableRow = $(event.target).parent().parent();
            let domainName = $(event.target).attr('id');
            let domainModalSettings = {
                title: $.mage.__(domainName),
                content: function () {
                    return document.getElementById('fastly-tls-show-domain-info-template').textContent
                }
            };

            let html = generateShowDomainInfoFields(domains[domainName]);
            overlay(domainModalSettings);
            resetAllMessages();
            $('.show-domain-info-item-container').append(html);
            $('.upload-button').remove();

            if (domains[domainName].tls_subscriptions.state === 'pending') {
                showWarningMessage(proveDomainOwnershipMsg(domains[domainName]));
            } else if (domains[domainName].tls_subscriptions.state === 'issued' || domains[domainName].tls_activations !== false) {
                getSpecificConfiguration(domains[domainName].tls_configurations.id, true).done(function (response) {
                    let html = generateDetailsTable(response.configuration, domains[domainName].tls_certificates.name);
                    $('.tls-subscription-notice').append(html).show();
                });
            }
        });

        //remove subscription
        $('body').on('click', '.delete-tls-subscription', function (event) {
            let subscriptionId = $(event.target).attr('id');
            deleteSubscription(subscriptionId, true).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    return showErrorMessage(response.msg);
                }

                clearInterval(fastlyDomainsTimer);
                let domainName = $(event.target).attr('data-domain');
                modal.modal('closeModal');
                domains.splice(domainName, 1);
                $(tableRow).remove();
                domainSuccessButtonMsg.text($.mage.__(response.msg)).show();
                fastlyDomainsTimer = setInterval(function () {
                     return fastlyGetMeDomains();
                 }, 5000);
            });
        });

        //disable activation
        $('body').on('click', '.disable-tls-activation', function (event) {
            let activation = $(event.target).attr('data-activation');
            resetAllMessages();
            disableTlsActivation(activation, true).done(function (response) {
                modal.modal('closeModal');
                if (response.status !== true || response.flag !== true) {
                    return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                domainSuccessButtonMsg.text($.mage.__(response.msg)).show();
            });
        });

        //enable activation
        $('body').on('click', '.enable-tls-activation', function (event) {
            resetAllMessages();
            getTlsConfigurations(false).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    modal.modal('closeModal');
                    return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                let configuration = response.configurations[0].id;
                let certificate = $(event.target).attr('data-certificate');
                let domain = $(event.target).attr('id');
                enableTlsActivation(domain, certificate, configuration, true).done(function (response) {
                    modal.modal('closeModal');
                    if (response.status !== true || response.flag !== true) {
                        return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                    }

                    domainSuccessButtonMsg.text($.mage.__(response.msg)).show();
                });
            });
        });

        /** When client wants to secure new domain with Fastly certificate */
        $('body').on('click', '#secure-another-domain', function () {
            resetAllMessages();
            getTlsConfigurations(true).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                let configurations = response.configurations.length !== 0 ? response.configurations : [];
                overlay(anotherDomainModalSettings);
                if (configurations.length !== 0) {
                    $('.upload-button').remove();
                    let html = generateDomainsTableFields(configurations);
                    $('.new-domain-item-container').append(html);
                    $('#save_item').on('click', function () {

                        let name = $('.domain-name').val();
                        let config = $('.tls-configurations').val();

                        saveDomain(name, config, true).done(function (response) {
                            resetAllMessages();
                            modal.modal('closeModal');
                            if (response.status !== true || response.flag !== true) {
                                return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                            }

                            domains[response.domain.id] = response.domain;
                            let html = generateSecuredDomainsTableFields(response.domain);
                            $('#tls-domains-item-container').append(html);

                            return domainSuccessButtonMsg.text($.mage.__(response.msg)).show();
                        });
                    });
                    return;
                }

                showWarningMessage('In order to add a domain to a managed certificate please upgrade your account.');
            });
        });

        /** When client wants to add new certificate */
        $('body').on('click', '#secure-certificate', function () {
            let privateKey = '';
            let certificate = '';
            resetAllMessages();
            let html = generateCertificateFormFields();
            overlay(certificateModalSettings);
            $('.upload-button').remove();
            $('.new-tls-certificate-item-container').append(html);

            $('#private-key-file').change(function () {
                let file = $('#private-key-file')[0].files;
                let reader = new FileReader();

                reader.onload = function (event) {
                    privateKey = event.target.result;
                };
                reader.readAsText(file[0]);
            });

            $('#certificate-key-file').change(function () {
                let file = $('#certificate-key-file')[0].files;
                let reader = new FileReader();

                reader.onload = function (event) {
                    certificate = event.target.result;
                };
                reader.readAsText(file[0]);
            });

            //when clients wants to save certificate
            $('.save_certificate').on('click', function () {
                let certificateId;
                let msg;
                let domainName;
                let attributes;
                let formKey = $('#form-key').val();
                let privateKeyName = $('#private-key-name').val();
                let certificateName = $('#certificate-key-name').val();

                createTlsPrivateKey(true, privateKey, privateKeyName, formKey).done(function (response) {
                    if (response.status !== true || response.flag !== true) {
                        modal.modal('closeModal');
                        return certErrorButtonMsg.text($.mage.__(response.msg)).show();
                    }

                    showSuccessMessage(response.msg);
                    createTlsCertificate(true, certificate, certificateName, formKey).done(function (response) {
                        modal.modal('closeModal');
                        $('.no-tls-certificates').hide();
                        $('.no-tls-domains').hide();
                        $('.loading-tls-certificates').show();
                        resetAllMessages();
                        if (response.status !== true || response.flag !== true) {
                            certErrorButtonMsg.text($.mage.__(response.msg)).show();
                            return deletePrivateKey(privateKey, false);
                        }

                        certificateId = response.data.id;
                        msg = response.msg;
                        attributes = response.data.attributes;
                        getSpecificCertificate(certificateId, formKey, false).done(function (response) {
                            if (response.status !== true || response.flag !== true) {
                                domainName = '';
                                return;
                            }

                            domainName = response.data.relationships.tls_domains.data[0].id;
                            $('.loading-tls-certificates').hide();
                            let html = generateCertificateTableBody(attributes.name, attributes.issuer, attributes.issued_to, response.data.id);
                            $('#tls-certificates-item-container').append(html);
                            html = generateSecuredDomainsTableFields(domainName);
                            $('no-tls-domains').hide();
                            $('#tls-domains-item-container').append(html);
                            return certSuccessButtonMsg.text($.mage.__(msg)).show();
                        });
                    });
                });
            });
        });

        $('body').on('click', '.show-certificate', function () {
            let id = $('.show-certificate').attr('data-certificate-number');
            let formKey = $('#form-key').val();

            resetAllMessages();
            getSpecificCertificate(id, formKey, true).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    return certErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                let specificCertificateModalSettings = {
                    title: $.mage.__('Certificate ' + response.data.attributes.name),
                    content: function () {
                        return document.getElementById('fastly-show-tls-certificate-template').textContent
                    }
                };

                let attributes = response.data.attributes;
                let html = generateShowCertificateFields(
                    response.data.id,
                    new Date(attributes.created_at),
                    attributes.issued_to,
                    attributes.issuer,
                    new Date(attributes.not_after),
                    attributes.signature_algorithm
                );
                overlay(specificCertificateModalSettings);
                $('.upload-button').remove();
                $('.specific-certificate-container').append(html);
            });
        });

        /** ----- Generate html ----- */
        function generateCertificateTableBody(name, issuer, issuedTo, id)
        {
            let html = '';
            html += '<tr>';
            html += '<td>' + name + '</td>';
            html += '<td>' + issuer + '</td>';
            html += '<td>' + issuedTo + '</td>';
            html += '<td>' +
                '<button class="fastly-view-vcl-action show-certificate" data-certificate-number="'+id+'"  style="margin-left:0.5rem;"  title="Show Certificate" type="button"></button>' +
                '</td>';
            html += '</tr>';
            return html;
        }

        /**
         * @param domain
         * @returns {string}
         */
        function generateTlsCertificateMessage(domain)
        {
            if (!domain.tls_certificates) {
                return '<span class="tls-certificate-message">Domain validation in progress…</span>';
            }

            return '<span class="tls-certificate-message">' + domain.tls_certificates.name + '</span>';
        }

        /**
         *
         * @param domain
         * @returns {string}
         */
        function proveDomainOwnershipMsg(domain)
        {
            return 'Create a ' + domain.tls_authorizations.challenges[0].record_type
                    + ' for ' + domain.tls_authorizations.challenges[0].record_name
                    + ' and point it to ' + domain.tls_authorizations.challenges[0].values[0];
        }

        /**
         *
         * @param configuration
         * @returns {string}
         */
        function generateDnsDetailsTableBody(configuration)
        {
            let html = '';
            html += '<tr>';
            html += '<td>'+configuration.data.CNAME.toString()+'</td>';
            html += '<td>'+configuration.data.AAAA.toString()+'</td>';
            html += '<td>'+configuration.data.A.toString()+'</td>';
            html += '</tr>';
            return html;
        }

        /**
         *
         * @param configuration
         * @param certificate
         * @returns {string}
         */
        function generateDetailsTable(configuration, certificate)
        {
            let html = '';
            html += '<div class="dns-detals-div">';
            html += '<div class="dns-details-message">';
            html += '<b>In order to complete the setup and to start serving TLS traffic, you must:</b>';
            html += '<ul style="list-style-type: none">';
            html += '<li><b>1</b> Ensure that the domain has been added to a properly configured Fastly service.</li>';
            html += '<li><b>2</b> Make sure you have one of the following records set up with your DNS provider:</li>';
            html += '</div>';
            html += '<table class="admin__control-table" style="margin-bottom: 3rem;">';
            html += '<thead>';
            html += '<tr><th>TLS Configuration</th><th>TLS Version</th><th>HTTP Protocols</th><th>Certificate Being Used</th>';
            html += '</thead>';
            html += '<tbody>';
            html += '<tr>';
            html += '<td>'+configuration.data.attributes.name+'</td>';
            html += '<td>'+configuration.data.attributes.tls_protocols[0]+'</td>';
            html += '<td>' + configuration.data.attributes.http_protocols[0] +' and ' + configuration.data.attributes.http_protocols[1] + '</td>';
            html += '<td>'+certificate+'</td>';
            html += '</tr>';
            html += '</tbody>';
            html += '<tfoot>';
            html += '<td colspan="4" class="col-actions-add"></td>';
            html += '</tfoot>';
            html += '</table>';
            html += '<h2>DNS details</h2>';
            html += '<ul style="list-style-type: none">';
            html += '</li>Global DNS lands traffic across Fastly’s worldwide network. This has the best international performance with regional pricing applied to all traffic.</li>';
            html += '</li>';
            html += '<table class="admin__control-table" style="margin-top: 1rem;">';
            html += '<thead>';
            html += '<tr><th>CNAME Records</th><th>A Records</th><th>AAAA Records (IPV6)</th>';
            html += '</thead>';
            html += '<tbody>';
            html += generateDnsDetailsTableBody(configuration);
            html += '</tbody>';
            html += '<tfoot>';
            html += '<td colspan="3" class="col-actions-add"></td>';
            html += '</tfoot>';
            html += '</table>';
            html += '</div>';
            return html;
        }

        /**
         *
         * @param domain
         * @returns {string}
         */
        function generateTlsStatusMessage(domain)
        {
            if (!domain.tls_activations) {
                if (domain.tls_subscriptions.state !== 'pending') {
                    if (domain.tls_subscriptions.state !== 'processing') {
                        if (domain.tls_subscriptions.state !== 'issued') {
                            if (!domain.tls_subscriptions && domain.tls_certificates) {
                                return '<span class="tls-status-message">Ready to enable</span>';
                            }
                            return '<span class="tls-status-message"></span>';
                        }

                        return '<span class="tls-status-message">Ready to enable</span>';
                    }

                    return '<span class="tls-status-message">The certificate has been requested. Fastly is waiting for the Certificate Authority’s response.</span><br><span class="tls-status-step">Step 2 of 3</span>';
                }

                return '<span class="tls-status-message">Fastly is verifying domain ownership.</span><br><span class="tls-status-step">Step 1 of 3</span>';
            }
            return '<span class="tls-status-message">Enabled - Certificate issued. Deploying across Fastly’s global network.</span>';
        }

        /**
         *
         * @param domain
         * @returns {string}
         */
        function generateTlsActionField(domain)
        {
            if (!domain.tls_activations && domain.tls_subscriptions) {
                if (domain.tls_subscriptions.state !== 'issued') {
                    return '<span class="action-delete delete-tls-subscription"  id="'+domain.tls_subscriptions.id+'" title="Delete '+domain.tls_subscriptions.id+'" type="button">';
                }

                return '<span class="action-delete delete-tls-subscription" data-domain="'+domain.id+'"  id="'+domain.tls_subscriptions.id+'" title="Delete '+domain.tls_subscriptions.id+'" type="button"></span>'
                        + '<span class="change-tls-state disable-tls-subscription">Enable TLS</span>'
            }

            if (!domain.tls_activations) {
                return '<span class="change-tls-state enable-tls-activation tls-button" id="'+domain.id+'" data-certificate="'+domain.tls_certificates.id+'">Enable TLS</span>';
            }

            return '<span class="change-tls-state disable-tls-activation tls-button"  data-activation="'+domain.tls_activations.id+'" id="'+domain.id+'">Disable TLS</span>';
        }

        /**
         *
         * @param domain
         * @returns {string}
         */
        function generateShowDomainInfoFields(domain)
        {
            let html = '';
            html += '<tr>';
            html += '<td>'+domain.id+'</td>';
            html += '<td>' + generateTlsStatusMessage(domain) + '</td>';
            html += '<td>' + generateTlsCertificateMessage(domain) + '</td>';
            html += '<td>' + generateTlsActionField(domain) + '</td>';
            html += '</tr>';
            return html;
        }

        /**
         *
         * @returns {string}
         */
        function generateCertificateFormFields()
        {
            let html = '';

            html += '<tr>';
            html += '<td><input class="admin__control-text" type="text" id="private-key-name" name="private-key-name"></td>';
            html += '<td><input id="private-key-file" name="private-key-file" type="file" class="admin__control-text"></td>';
            html += '<td><input class="admin__control-text" type="text" id="certificate-key-name" name="certificate-key-name"></td>';
            html += '<td><input id="certificate-key-file" name="certificate-key-file" type="file" class="admin__control-text"></td>';
            html += '<td><span class="action-delete fastly-save-action save_certificate" title="Save Certificate" type="button"></span></td>';
            html += '</tr>';
            return html;
        }

        /**
         *
         * @param domain
         * @returns {string}
         */
        function generateSecuredDomainsTableFields(domain)
        {
            let html = '';
            html += '<tr id="'+domain.id+'">';
            html += '<td>' + domain.id + '</td>';
            if (!domain.tls_subscriptions) {
                if (!domain.tls_activations) {
                    html += '<td>' + 'Ready to enable' + '</td>';
                } else {
                    html += '<td>Enabled</td>';
                }
            } else {
                html += '<td>' + domain.tls_subscriptions.state + '</td>';
            }
            html += '<td><button class="fastly-view-vcl-action show-domain-info" id="' + domain.id + '" style="margin-left:0.5rem;"  title="Show Domain '+domain.id+'" type="button"></button></td>';
            html += '</tr>';
            return html;
        }

        /**
         *
         * @param configurations
         * @returns {string}
         */
        function generateDomainsTableFields(configurations)
        {
            let html = '';
            html += '<tr>';
            html += '<td><input name="domain-name" class="input-text admin__control-text domain-name" type="text"></td>';
            html += '<td><select name="tls-configurations" class="admin__control-text tls-configurations">';

            $.each(configurations, function (index, conf) {
                html += '<option value="' + conf.id + '">' + conf.attributes.name + '</option>';
            });

            html += '</select></td>';
            html += '<td><span id="save_item" class="action-delete fastly-save-action save_item" title="Save" type="button"></span></td>';
            return html;
        }

        /**
         *
         * @param id
         * @param createdAt
         * @param issuedTo
         * @param issuer
         * @param expired
         * @param algorithm
         * @returns {string}
         */
        function generateShowCertificateFields(id, createdAt, issuedTo, issuer, expired, algorithm)
        {
            let html = '';
            html += '<tr>';
            html += '<td>'+id+'</td>';
            html += '<td>'+createdAt+'</td>';
            html += '<td>'+issuedTo+'</td>';
            html += '<td>'+issuer+'</td>';
            html += '<td>'+expired+'</td>';
            html += '<td>'+algorithm+'</td>';
            html += '</tr>';
            return html;
        }

        /** Ajax calls */

        /**
         * https://docs.fastly.com/api/tls#tls_configurations_309cdce31802712ca4b043e9b2ef674a
         * @param loader
         * @returns {jQuery}
         */
        function getTlsConfigurations(loader)
        {
            return $.ajax({
                type: 'get',
                url: config.getTlsConfigurations,
                showLoader: loader
            });
        }

        /**
         * https://docs.fastly.com/api/tls#tls_activations_a177a86818ad0cbbd592a709e763a032
         * @param activation
         * @param loader
         * @returns {jQuery}
         */
        function disableTlsActivation(activation, loader)
        {
            return $.ajax({
                type: 'get',
                url: config.disableTlsActivation,
                showLoader: loader,
                data: {'activation':activation}
            });
        }

        /**
         * https://docs.fastly.com/api/tls#tls_activations_a5aadc3a427d3b19fbf0bcedfac39f04
         * @param domain
         * @param certificate
         * @param configuration
         * @param loader
         * @returns {jQuery}
         */
        function enableTlsActivation(domain, certificate, configuration, loader)
        {
            return $.ajax({
                type: 'get',
                url: config.enableTlsActivation,
                showLoader: loader,
                data: {'domain':domain, 'certificate':certificate, 'configuration':configuration}
            });
        }

        /**
         * https://docs.fastly.com/api/tls-subscriptions#tls_subscriptions_cdd1217479c84ef986bc63eca6473403
         * @param name
         * @param conf
         * @param loader
         * @returns {jQuery}
         */
        function saveDomain(name, conf, loader)
        {
            return $.ajax({
                type: 'get',
                url: config.secureAnotherDomain,
                showLoader: loader,
                data: {'tls_domains':name, 'tls_configuration': conf}
            });
        }

        /**
         * https://docs.fastly.com/api/tls-subscriptions#tls_subscriptions_92add1384ab77a70d8c236c275116ecd
         * @param loader
         * @returns {jQuery}
         */
        function getTlsSubscriptions(loader)
        {
            return $.ajax({
                type: 'get',
                url: config.getTlsSubscriptions,
                showLoader: loader
            });
        }

        /**
         *
         * @param loader
         * @returns {jQuery}
         */
        function getTlsDomains(loader)
        {
            return $.ajax({
                type: 'get',
                url: config.getTlsDomains,
                showLoader: loader
            });
        }

        /**
         * https://docs.fastly.com/api/tls#tls_certificates_8f7f856e0dfc70a5855b0e58a65a7041
         * @param loader
         * @returns {jQuery}
         */
        function getTlsCertificates(loader)
        {
            return $.ajax({
               type: 'get',
               url: config.getTlsCertificates,
               showLoader: loader
            });
        }

        /**
         * https://docs.fastly.com/api/tls#tls_configurations_309cdce31802712ca4b043e9b2ef674a
         * @param loader
         * @param certificate
         * @param cert_name
         * @param form_key
         * @returns {jQuery}
         */
        function createTlsCertificate(loader, certificate, cert_name, form_key)
        {
            return $.ajax({
                type: 'post',
                url: config.createTlsCertificate,
                data: {'form_key': form_key, 'certificate': certificate, 'name': cert_name},
                showLoader: loader
            });
        }

        /**
         * https://docs.fastly.com/api/tls#tls_private_keys_8df12494b971ab303bb6228cb6bc5f2c
         * @param loader
         * @param private_key
         * @param key_name
         * @param form_key
         * @returns {jQuery}
         */
        function createTlsPrivateKey(loader, private_key, key_name, form_key)
        {
            return $.ajax({
                type: 'post',
                url: config.createTlsPrivateKey,
                data: {'form_key': form_key, 'private_key': private_key, 'name': key_name},
                showLoader: loader
            });
        }

        /**
         * https://docs.fastly.com/api/tls#tls_certificates_2eb9c3d908a20261e17e4b42955a69a1
         * @param id
         * @param formKey
         * @param loader
         */
        function getSpecificCertificate(id, formKey, loader)
        {
            return $.ajax({
                type: 'post',
                url: config.getCertificateWithId,
                data: {'form_key':formKey, 'id':id},
                showLoader: loader
            });
        }

        /**
         *
         * @param id
         * @param loader
         * @returns {jQuery}
         */
        function getSpecificConfiguration(id, loader)
        {
            return $.ajax({
                type: 'get',
                url: config.getConfigurationWithId,
                data: {'id':id},
                showLoader: loader
            });
        }

        /**
         * https://docs.fastly.com/api/tls#tls_private_keys_a6b56e5e17abd12831a716b4fec33277
         * @param privateKey
         * @param loader
         * @returns {jQuery}
         */
        function deletePrivateKey(privateKey, loader)
        {
            return $.ajax({
                type: 'get',
                url: config.deletePrivateKey,
                data: {formKey, 'privateKey':privateKey},
                showLoader: loader
            });
        }

        /**
         * https://docs.fastly.com/api/tls-subscriptions#tls_subscriptions_6b2bf54a3a416f8105050532361bcc0b
         * @param id
         * @param loader
         */
        function deleteSubscription(id, loader)
        {
            return $.ajax({
                type: 'get',
                url: config.deleteSubscription,
                data: {'id':id},
                showLoader: loader
            });
        }
    }
});
