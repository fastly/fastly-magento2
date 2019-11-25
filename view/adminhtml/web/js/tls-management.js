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

        /** Domains buttons */
        let domainErrorButtonMsg = $('#fastly-error-tls-domains-button');
        let domainSuccessButtonMsg = $('#fastly-success-tls-domains-button');
        let domainWarningButtonMsg = $('#fastly-warning-tls-domains-button');

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
                        tr.append(tdDomain);
                        tr.append(tdStatus);
                        $('#tls-domains-item-container').append(tr);
                    });
                    return;
                }

                $('.no-tls-domains').text($.mage.__('There are no TLS domains configured yet.')).show();
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
            createRowElements();
            $('#add-domain-item').on('click', function () {
                createRowElements();
            });
        });

        function createRowElements()
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

        /** Ajax calls */

        /**
         * https://docs.fastly.com/api/tls#tls_configurations
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
         * https://docs.fastly.com/api/tls-subscriptions#tls_subscriptions
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
         * https://docs.fastly.com/api/tls-subscriptions#tls_subscriptions
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

        /** Load certifications modal */
        $('#tls-certifications-button').on('click', function () {
            console.log("tls-certifications-button");
        });
    }
});
