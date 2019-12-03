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

        let configurations;
        let certificateModal;

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

        //check if client has permissions
        getTlsConfigurations(true).done(function (response) {
            if (response.status !== true || response.flag !== true) {
                $('#secure-another-domain').attr('disabled', true);
                return notAuthorisedMsg.text($.mage.__(response.msg)).show();
            }

            configurations = response.configurations.length !== 0 ? response.configurations : [];

            //catch all secured domains
            getTlsSubscriptions(true).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                $('.loading-tls-domains').hide();
                if (response.data.length !== 0) {
                    $.each(response.data, function (index, subscription) {
                        let domain = subscription.relationships.tls_domains.data[0].id;
                        let tlsStatus = subscription.attributes.state;
                        let tdDomain = document.createElement('td');
                        tdDomain.append(document.createTextNode(domain));
                        let tdStatus = document.createElement('td');
                        tdStatus.append(document.createTextNode(tlsStatus));
                        let tr = document.createElement('tr');
                        tr.setAttribute('id', domain);
                        tr.append(tdDomain);
                        tr.append(tdStatus);
                        $('#tls-domains-item-container').append(tr);
                    });
                    return;
                }

                $('.no-tls-domains').text($.mage.__('There are no TLS domains configured yet.')).show();
            });

            //catch all tls certificates
            getTlsCertificates(true).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    return certErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                $('.loading-tls-certificates').hide();
                if (response.data.length !== 0) {
                    $.each(response.data, function (index, certificate) {
                        let name = certificate.attributes.name;
                        let issuer = certificate.attributes.issuer;
                        let issuedTo = certificate.attributes.issued_to;

                        let tdName = document.createElement('td');
                        let tdIssuer = document.createElement('td');
                        let tdIssuedTo = document.createElement('td');

                        let tr = document.createElement('tr');

                        tdName.append(document.createTextNode(name));
                        tdIssuer.append(document.createTextNode(issuer));
                        tdIssuedTo.append(document.createTextNode(issuedTo));

                        tr.append(tdName);
                        tr.append(tdIssuer);
                        tr.append(tdIssuedTo);

                        $('#tls-certificates-item-container').append(tr);
                    });
                    return;
                }

                $('.no-tls-certificates').text($.mage.__('There are no TLS certificates configured yet.')).show();
            });
        });

        //when client clicks "secure another domain"
        $('body').on('click', '#secure-another-domain', function () {
            let anotherDomainModalOptions = {
                title: $.mage.__('Enter the domain you want Fastly to secure'),
                content: function () {
                    return document.getElementById('fastly-tls-new-domains-template').textContent
                }
            };

            overlay(anotherDomainModalOptions);
            $('.upload-button').remove();
            createNewDomainRowElements();
            $('#add-domain-item').on('click', function () {
                createNewDomainRowElements();
            });
        });

        function createNewDomainRowElements()
        {
            let tr = document.createElement('tr');
            let tdInput = document.createElement('td');
            let tdSelect = document.createElement('td');
            let tdAction = document.createElement('td');
            let input = document.createElement('input');
            //create input
            input.setAttribute('name', 'domain-name');
            input.setAttribute('class', 'input-text admin__control-text domain-items-field');
            input.setAttribute('type', 'text');
            tdInput.append(input);

            //create select
            let select = document.createElement('select');
            select.setAttribute('name', 'tls-configurations');
            select.setAttribute('class', 'admin__control-text');
            $.each(configurations, function (index, conf) {
                let option = document.createElement('option');
                option.setAttribute('value', conf.id);
                option.append(document.createTextNode(conf.attributes.name));
                select.append(option);
            });
            tdSelect.append(select);

            //create save action
            let saveAction = document.createElement('span');
            saveAction.setAttribute('class', 'action-delete fastly-save-action save_item');
            saveAction.setAttribute('title', 'Save');
            saveAction.setAttribute('type', 'button');

            //appending to table body
            tdAction.append(saveAction);
            tr.append(tdInput);
            tr.append(tdSelect);
            tr.append(tdAction);
            onClickInvokeSavingDomain(saveAction, input, select);
            $('.new-domain-item-container').append(tr);
        }

        function onClickSaveTlsPrivateKey(button, nameInput, keyInput)
        {
            let private_key = '';

            $(keyInput).on('change', function (event) {
                let reader = new FileReader();
                let files = event.target.files;

                reader.onload = function (event) {
                    private_key = event.target.result;
                };

                reader.readAsText(files[0]);
            });

            $(button).on('click', function () {
                let name = $(nameInput).val();
                let form_key = $('#form-key').val();

                return createTlsPrivateKey(true, private_key, name, form_key).done(function (response) {
                    if (response.status !== true || response.flag !== true) {
                        modal.modal('closeModal');
                        return certErrorButtonMsg.text($.mage.__(response.msg)).show();
                    }

                    let anotherDomainModalOptions = {
                        title: $.mage.__('Upload the matching certificate.'),
                        content: function () {
                            return document.getElementById('fastly-tls-certificate-template').textContent
                        }
                    };

                    overlay(anotherDomainModalOptions);
                    certificateModal = modal;
                    createNewCertificateElements();
                    $('#add-certificate-item').on('click', function () {
                        createNewCertificateElements();
                    });
                });

                /*
                return createTlsCertificate(true, private_key, name, form_key).done(function (response) {
                    modal.modal('closeModal');
                    if (response.status !== true || response.flag !== true) {
                        return certErrorButtonMsg.text($.mage.__(response.msg)).show();
                    }

                    return certSuccessButtonMsg.text($.mage.__(response.msg)).show();
                });
                */
            });
        }

        function onClickInvokeSavingDomain(button, domain, conf)
        {
            $(button).on('click', function () {
                let domainInput = $(domain).val();
                let confInput = $(conf).val();
                saveDomain(domainInput, confInput, true).done(function (response) {
                    resetAllMessages();
                    modal.modal('closeModal');
                    if (response.status !== true || response.flag !== true) {
                        return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                    }

                    let tr = document.createElement('tr');
                    let tdDomain = document.createElement('td');
                    let tdState = document.createElement('td');
                    tdDomain.append(document.createTextNode(response.domain));
                    tdState.append(document.createTextNode(response.state));
                    tr.append(tdDomain);
                    tr.append(tdState);
                    $('#tls-domains-item-container').append(tr);
                    return domainSuccessButtonMsg.text($.mage.__(response.msg)).show();
                });
            });
        }

        function createNewPrivateKeyElements()
        {
            let tr = document.createElement('tr');
            let tdName = document.createElement('td');
            let tdFile = document.createElement('td');
            let tdAction = document.createElement('td');

            let inputName = document.createElement('input');
            inputName.setAttribute('class', 'admin__control-text');
            inputName.setAttribute('type', 'text');
            inputName.setAttribute('name', 'private-key-name');
            let inputKey = document.createElement('input');
            inputKey.setAttribute('name', 'private-key-file');
            inputKey.setAttribute('id', 'private-key-file');
            inputKey.setAttribute('type', 'file');
            inputKey.setAttribute('class', 'admin__control-text');
            let inputAction = document.createElement('span');
            inputAction.setAttribute('class', 'action-delete fastly-save-action save_private_key');
            inputAction.setAttribute('title', 'Save Private Key');
            inputAction.setAttribute('type', 'button');
            tdName.append(inputName);
            tdFile.append(inputKey);
            tdAction.append(inputAction);
            tr.append(tdName);
            tr.append(tdFile);
            tr.append(tdAction);
            onClickSaveTlsPrivateKey(inputAction, inputName, inputKey);
            $('.new-tls-private-key-item-container').append(tr);
        }

        function createNewCertificateElements()
        {
            let tr = document.createElement('tr');
            let tdName = document.createElement('td');
            let tdFile = document.createElement('td');
            let tdAction = document.createElement('td');

            let inputName = document.createElement('input');
            inputName.setAttribute('class', 'admin__control-text');
            inputName.setAttribute('type', 'text');
            inputName.setAttribute('name', 'certificate-key-name');
            let inputKey = document.createElement('input');
            inputKey.setAttribute('name', 'certificate-key-file');
            inputKey.setAttribute('id', 'certificate-key-file');
            inputKey.setAttribute('type', 'file');
            inputKey.setAttribute('class', 'admin__control-text');
            let inputAction = document.createElement('span');
            inputAction.setAttribute('class', 'action-delete fastly-save-action save_private_key');
            inputAction.setAttribute('title', 'Save Private Key');
            inputAction.setAttribute('type', 'button');
            tdName.append(inputName);
            tdFile.append(inputKey);
            tdAction.append(inputAction);
            tr.append(tdName);
            tr.append(tdFile);
            tr.append(tdAction);
            onClickSaveTlsCertificate(inputAction, inputName, inputKey);
            $('.new-tls-private-key-item-container').append(tr);
        }


        /** Load certifications modal */
        $('#secure-another-certificate').on('click', function () {
            let createCertificateModalOptions = {
                title: $.mage.__('Upload your key file securely.'),
                content: function () {
                    return document.getElementById('fastly-tls-private-key-template').textContent
                }
            };

            overlay(createCertificateModalOptions);
            $('.upload-button').remove();
            createNewPrivateKeyElements();
            $('#add-certificate-item').on('click', function () {
                createNewPrivateKeyElements();
            });
        });

        function onClickSaveTlsCertificate(button, nameInput, certInput)
        {
            let certificate = '';

            $(certInput).on('change', function (event) {
                let reader = new FileReader();
                let files = event.target.files;

                reader.onload = function (event) {
                    certificate = event.target.result;
                };

                reader.readAsText(files[0]);
            });

            $(button).on('click', function () {
                let name = $(nameInput).val();
                let form_key = $('#form-key').val();

                return createTlsCertificate(true, certificate, name, form_key).done(function (response) {
                    if (response.status !== true || response.flag !== true) {
                        modal.modal('closeModal');
                        return showErrorMessage(response.msg);
                    }

                    certificateModal.modal('closeModal');
                    modal.modal('closeModal');
                    certSuccessButtonMsg($.mage.__(response.msg));
                });
            });
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
         * @param private_key
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
    }
});
