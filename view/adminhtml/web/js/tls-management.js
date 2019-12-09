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

        //catch all secured domains when https and networking is selected
        getTlsDomains(true).done(function (response) {
            if (response.status !== true || response.flag !== true) {
                $('#secure-another-domain').attr('disabled', true);
                $('#secure-certificate').attr('disabled', true);
                return notAuthorisedMsg.text($.mage.__(response.msg)).show();
            }

            let tlsDomains = response.domains;
            let html = '';
            $('.loading-tls-domains').hide();
            if (tlsDomains !== false && tlsDomains.length !== 0) {
                $.each(tlsDomains, function (index, domain) {
                    html += generateSecuredDomainsTableFields(domain.id);
                });
                $('#tls-domains-item-container').append(html);
            } else {
                $('.no-tls-domains').text($.mage.__('No TLS domains')).show();
            }

            getTlsCertificates(true).done(function (response) {
                let html = '';
                $('.loading-tls-certificates').hide();
                if (response.data.length !== 0) {
                    $.each(response.data, function (index, certificate) {
                        html += generateCertificateTableBody(certificate.attributes.name, certificate.attributes.issuer, certificate.attributes.issued_to, certificate.id)
                    });
                    $('#tls-certificates-item-container').append(html);
                    return;
                }

                $('.no-tls-certificates').text($.mage.__('No TLS certificates.')).show();
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
                    handleDomainModal(); //open modal for adding new domain
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

        /**
         * Modal for securing domain with fastly certificate
         */
        function handleDomainModal()
        {
            $('#save_item').on('click', function () {

                let name = $('.domain-name').val();
                let config = $('.tls-configurations').val();

                saveDomain(name, config, true).done(function (response) {

                    resetAllMessages();
                    modal.modal('closeModal');
                    if (response.status !== true || response.flag !== true) {
                        return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                    }

                    //append newly created domain on the list
                    let html = generateSecuredDomainsTableFields(response.domain);
                    $('#tls-domains-item-container').append(html);

                    return domainSuccessButtonMsg.text($.mage.__(response.msg)).show();
                });
            });
        }

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

        function generateSecuredDomainsTableFields(domain)
        {
            let html = '';
            html += '<tr>';
            html += '<td>' + domain + '</td>';
            html += '</tr id="' + domain + '">';
            return html;
        }

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
    }
});
