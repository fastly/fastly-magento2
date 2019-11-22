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
        let anotherDomainModal;

        /** Versions */
        let activeVersion = serviceStatus.active_version;
        let nextVersion = serviceStatus.next_version;

        /** Buttons */
        let domainsButton = $('#tls-domains-button');
        let certificateButton = $('#tls-certifications-button');
        let configurationsButton = $('#tls-configurations-button');

        /** Domains buttons */
        let domainErrorButtonMsg = $('#fastly-error-tls-domains-button');
        let domainSuccessButtonMsg = $('#fastly-success-tls-domains-button');
        let domainWarningButtonMsg = $('#fastly-warning-tls-domains-button');

        /** Messages */
        let notAuthorisedMsg = $('#fastly-warning-not-authorized-button-msg');

        getServiceInfoUrl(true).done(function (response) {
            if (response.status !== true) {
                switchButtons(true, [
                    domainsButton,
                    certificateButton,
                    configurationsButton
                ]);
                return notAuthorisedMsg.text($.mage.__(response.msg)).show();
            }

            activeVersion = response.active_version;
            nextVersion = response.next_version;
            getTlsConfigurations(true).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    switchButtons(true, [
                        domainsButton,
                        certificateButton,
                        configurationsButton
                    ]);
                    return notAuthorisedMsg.text($.mage.__(response.msg)).show();
                }

                configurations = response.configurations.length !== 0 ? response.configurations : [];
            });
        });

        function switchButtons(turnOff, buttons = [])
        {
            buttons.forEach(function (button) {
                button.attr('disabled', turnOff);
            });
        }


        /** Domains */
        $('#tls-domains-button').on('click', function () {

            let tlsDomainsModalOptions = {
                title: $.mage.__('TLS Domains'),
                content: function () {
                    return document.getElementById('fastly-tls-domains-template').textContent
                }
            };

            resetAllMessages();

            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            overlay(tlsDomainsModalOptions);
            getTlsSubscriptions(true).done(function (response) {
                if (response.status !== true || response.flag !== true) {
                    return domainErrorButtonMsg.text($.mage.__(response.msg)).show();
                }

                $.each(response.data, function (index, subscription) {

                    let domain = subscription.relationships.tls_domains.data[0].id;
                    let tlsStatus = subscription.attributes.state;
                    let attributes = response.included[index].attributes;
                    let cname = attributes.challenges[1] !== undefined ? attributes.challenges[1].record_name : attributes.challenges[0].record_name;
                    let aRecord = attributes.challenges[2] !== undefined ? attributes.challenges[2].record_name : attributes.challenges[0].record_name;
                    let tdDomain = document.createElement('td');
                    tdDomain.append(document.createTextNode(domain));
                    let tdStatus = document.createElement('td');
                    tdStatus.append(document.createTextNode(tlsStatus));
                    let tdCname = document.createElement('td');
                    tdCname.append(document.createTextNode(cname));
                    let tdARecord = document.createElement('td');
                    tdARecord.append(document.createTextNode(aRecord));
                    let tr = document.createElement('tr');
                    tr.append(tdDomain);
                    tr.append(tdStatus);
                    tr.append(tdCname);
                    tr.append(tdARecord);
                    $('.tls-domains-item-container').append(tr);
                });
            });
            $('.upload-button').remove();

        });

        $('body').on('click', '#secure-another-domain', function () {
            let anotherDomainModalOptions = {
                title: $.mage.__('Secure Another Domain'),
                content: function () {
                    return document.getElementById('fastly-tls-new-domains-template').textContent
                }
            };

            overlay(anotherDomainModalOptions);
            anotherDomainModal = modal;
            $('.upload-button').remove();
            createRowElements();
            $('body').on('click', 'button#add-domain-item', function () {
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
                    if (response.status !== true || response.flag !== true) {
                        anotherDomainModal.modal('closeModal');
                        return showErrorMessage(response.msg);
                    }
                });
            });
        }

        /** Ajax calls */

        function getTlsConfigurations(loader)
        {
            return $.ajax({
                type: 'get',
                url: config.getTlsConfigurations,
                showLoader: loader
            });
        }

        function getServiceInfoUrl(loader)
        {
            return $.ajax({
                type: 'get',
                url: config.serviceInfoUrl,
                showLoader: loader
            });
        }

        function saveDomain(name, conf, loader)
        {
            return $.ajax({
                type: 'get',
                url: config.secureAnotherDomain,
                showLoader: loader,
                data: {'tls_domains':name, 'tls_configuration': conf}
            });
        }

        function getTlsSubscriptions(loader)
        {
            console.log(config.getTlsSubscriptions);
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

        /** Load configurations modal */
        $('#tls-configurations-button').on('click', function () {
            console.log("tls-configurations-button");
        });
    }
});
