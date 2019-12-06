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

        //todo: kada potvrdi novi private_key, otvara se modal sa "sada dodaj novi certifikat" i succes porukom za private_key
        //todo: kada klijent stisne na "dodaj novu konfiguraciju, dolje se očitavaju prijašnji privatni keys koji nemaju certifikat i svaki omogučuje edit button za dodavanje certifikacije
        //todo: optimizirat i očistit kod
        //todo. pretvorit button u browse for certificate file
        //todo: delete certificate (ask are you sure), delete private key, replace certificate
        //todo: modal pa reset messages

        let configurations;
        let numberOfCertificates;
        let lastInsertedPrivateKey = false;

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

        let privateKeyModalSettings = {
            title: $.mage.__('Upload your key file securely.'),
            content: function () {
                return document.getElementById('fastly-tls-private-key-template').textContent
            }
        };

        //catch all secured domains when https and networking is selected
        getTlsSubscriptions(true).done(function (response) {
            if (response.status !== true || response.flag !== true) {
                $('#secure-another-domain').attr('disabled', true);
                $('#secure-another-certificate').attr('disabled', true);
                return notAuthorisedMsg.text($.mage.__(response.msg)).show();
            }

            if (response.isPrivateKeyCreatedWithoutCertificate) {
                lastInsertedPrivateKey = response.privateKey;
                $('#secure-private-key').hide();
                $('#secure-certificate').show();
            }

            $('.loading-tls-domains').hide();
            if (response.data.length !== 0) {
                $.each(response.data, function (index, subscription) {
                    let domain = subscription.relationships.tls_domains.data[0].id;
                    let tlsStatus = subscription.attributes.state;
                    let html = generateSecuredDomainsTableFields(domain, tlsStatus);
                    $('#tls-domains-item-container').append(html);
                });
                return;
            }

            return $('.no-tls-domains').text($.mage.__('There are no TLS domains configured yet.')).show();
        });

        //catch all tls certificates
        getTlsCertificates(true).done(function (response) {
            if (response.status !== true || response.flag !== true) {
                return certErrorButtonMsg.text($.mage.__(response.msg)).show();
            }

            $('.loading-tls-certificates').hide();
            numberOfCertificates = response.data.length;
            if (numberOfCertificates !== 0) {
                $.each(response.data, function (index, certificate) {
                    let name = certificate.attributes.name;
                    let issuer = certificate.attributes.issuer;
                    let issuedTo = certificate.attributes.issued_to;
                    let html = generateCertificateTableBody(name, issuer, issuedTo, certificate.id);
                    $('#tls-certificates-item-container').append(html);
                });
                return;
            }

            $('.no-tls-certificates').text($.mage.__('There are no TLS certificates configured yet.')).show();
        });

        /** When client wants to secure new domain with Fastly certificate */
        $('body').on('click', '#secure-another-domain', function () {
            getTlsConfigurations(true).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                configurations = response.configurations.length !== 0 ? response.configurations : [];
                overlay(anotherDomainModalSettings);
                $('.upload-button').remove();
                let html = generateDomainsTableFields();
                $('.new-domain-item-container').append(html);
                handleDomainModal(); //open modal for adding new domain
            });
        });

        /** When client wants to add new certificate */
        $('body').on('click', '.secure', function () {
            resetAllMessages();
            getPrivateKeyFlag(true).done(function (response) {

                if (response.flag !== true) {
                    overlay(privateKeyModalSettings);
                    $('.upload-button').remove();
                    let html = generatePrivateKeyFormFields();
                    $('.new-tls-private-key-item-container').append(html);
                    handleModalForNewPrivateKey();
                    return;
                }

                overlay(certificateModalSettings);
                $('.upload-button').remove();
                let html = generateCertificateFormFields();
                $('.new-tls-certificate-item-container').append(html);
                $('#keys-with-no-certificate').show();
                html = generateTableBodyForNewlyCreatedPrivateKey(response.privateKey.name, response.privateKey.key);
                $('.no-certificate-container').append(html);
                handleModalForNewCertificate(modal);
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
                let html = generateShowCertificateFields(response.data.id,
                                                         new Date(attributes.created_at),
                                                         attributes.issued_to,
                                                         attributes.issuer,
                                                         new Date(attributes.not_after),
                                                         attributes.signature_algorithm);
                overlay(specificCertificateModalSettings);
                $('.specific-certificate-container').append(html);
            });
        });

        /** ----- Modal window handle -----*/

        /**
         * adding new domain
         */
        function handleDomainModal()
        {
            //when client wants to save domain
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
                    let html = generateSecuredDomainsTableFields(response.domain, response.state);
                    $('#tls-domains-item-container').append(html);

                    return domainSuccessButtonMsg.text($.mage.__(response.msg)).show();
                });
            });
        }

        /**
         * displaying form for adding new private key
         */
        function handleModalForNewPrivateKey()
        {
            let privateKey = '';

            //handle file input
            $('#private-key-file').on('change', function (event) {
                let reader = new FileReader();
                let files = event.target.files;

                reader.onload = function (event) {
                    privateKey =  event.target.result;
                };

                reader.readAsText(files[0]);
            });

            //when client wants to save the private key
            $('.save_private_key').on('click', function () {

                let name = $('#private-key-name').val();
                let form_key = $('#form-key').val();

                createTlsPrivateKey(true, privateKey, name, form_key).done(function (response) {

                    resetAllMessages();
                    modal.modal('closeModal');
                    if (response.status !== true || response.flag !== true) {
                        return certErrorButtonMsg.text($.mage.__(response.msg)).show();
                    }

                    $('#secure-another-certificate').val('Browse for certificate file');
                    $('#secure-certificate').show();
                    $('#secure-private-key').hide();

                    return certSuccessButtonMsg.text($.mage.__(response.msg)).show();
                });
            });
        }

        /**
         * creating new certificate
         * @param certModal
         */
        function handleModalForNewCertificate(certModal)
        {
            let certificate = '';

            //handle certificate file input
            $('#certificate-key-file').on('change', function (event) {
                let reader = new FileReader();
                let files = event.target.files;

                reader.onload = function (event) {
                    certificate =  event.target.result;
                };

                reader.readAsText(files[0]);
            });

            //handle certificate click on save button
            $('.save_certificate_key').on('click', function () {
                let name = $('#certificate-key-name').val();
                let form_key = $('#form-key').val();

                createTlsCertificate(true, certificate, name, form_key).done(function (response) {
                    resetAllMessages();
                    certModal.modal('closeModal');

                    if (response.status !== true || response.flag !== true) {
                        return certErrorButtonMsg.text($.mage.__(response.msg)).show();
                    }

                    $('.no-tls-certificates').hide();

                    let attributes = response.data.attributes;
                    let html = generateCertificateTableBody(attributes.name, attributes.issuer, attributes.issued_to, response.data.id);
                    $('#tls-certificates-item-container').append(html);
                    $('#secure-certificate').hide();
                    $('#secure-private-key').show();

                    return certSuccessButtonMsg.text($.mage.__(response.msg)).show();
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

        function generateTableBodyForNewlyCreatedPrivateKey(key, id)
        {
            let html = '';
            html += '<tr>';
            html += '<td>' + key + '</td>';
            html += '<td>' + id + '</td>';
            html += '</tr>';
            return html;
        }

        function generatePrivateKeyFormFields()
        {
            let html = '';
            html += '<tr>';
            html += '<td><input class="admin__control-text" type="text" id="private-key-name" name="private-key-name"></td>';
            html += '<td><input class="admin__control-text" type="file" id="private-key-file" name="private-key-file"></td>';
            html += '<td><span class="action-delete fastly-save-action save_private_key" title="Save Private Key" type="button"></span></td>';
            html += '</tr>';
            return html;
        }

        function generateCertificateFormFields()
        {
            let html = '';

            html += '<tr>';
            html += '<td><input class="admin__control-text" type="text" id="certificate-key-name" name="certificate-key-name"></td>';
            html += '<td><input id="certificate-key-file" name="certificate-key-file" type="file" class="admin__control-text"></td>';
            html += '<td><span class="action-delete fastly-save-action save_certificate_key" title="Save Private Key" type="button"></span></td>';
            html += '</tr>';
            return html;
        }

        function generateSecuredDomainsTableFields(domain, tlsStatus)
        {
            let html = '';
            html += '<tr>';
            html += '<td>' + domain + '</td>';
            html += '<td>' + tlsStatus + '</td>';
            html += '</tr id="' + domain + '">';
            return html;
        }

        function generateDomainsTableFields()
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
         * return "Is private key created without certificate" flag from core_config
         * @param loader
         * @returns {jQuery}
         */
        function getPrivateKeyFlag(loader)
        {
            return $.ajax({
                type: 'get',
                url: config.getPrivateKeyFlag,
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
    }
});
