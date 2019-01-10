define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "Magento_Ui/js/modal/confirm",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, confirm) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let domains;
        let current_domains;
        let active_version = serviceStatus.active_version;
        let errorDomainsBtnMsg = $('#fastly-error-domains-button-msg');
        let successDomainsBtnMsg = $('#fastly-success-domains-button-msg');

        let domainsListOptions = {
            title: jQuery.mage.__('Domains'),
            content: function () {
                return document.getElementById('fastly-domains-list-template').textContent;
            },
            actionOk: function () {
                pushDomains(active_version);
            }
        };

        /**
         * Trigger the Domains list call
         */
        getDomains(active_version, false).done(function (response) {
            $('.loading-domains').hide();
            if (response !== false) {
                if (response.domains.length > 0) {
                    domains = response.domains;
                    processDomains(response.domains);
                } else {
                    $('.no-domains').show();
                }
            }
        });

        /**
         * Get the list of Domains
         *
         * @param active_version
         * @param loaderVisibility
         * @returns {*}
         */
        function getDomains(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.getDomainsUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        /**
         * Process and display the list of Domains
         *
         * @param domains
         */
        function processDomains(domains)
        {
            $('#fastly-domains-list').html('');
            $.each(domains, function (index, domain) {
                let html = "<tr id='fastly_" + index + "'>";
                html += "<td><span><b>"+domain.name+"</b></span><p class='note'>"+domain.comment+"</p></td></tr>";
                $('#fastly-domains-list').append(html);
            });
        }

        /**
         * Process and display the list of domains in the manage Domains modal
         *
         * @param domains
         * @param store
         */
        function processDomainsList(domains, store)
        {
            let html = '';
            $.each(domains, function (index, domain) {
                html += '<tr><td>' +
                    '<input name="domain[]" value="'+ domain.name +'" class="input-text admin__control-text domain-name-field" type="text" disabled></td>' +
                    '<td><input name="comment[]" value="'+ domain.comment +'" class="input-text admin__control-text comment-field" type="text" disabled></td>' +
                    '<td class="col-actions">';

                if (store.indexOf(domain.name) < 0) {
                    html += '<button class="action-delete remove_domain"  title="Delete" type="button"><span>Delete</span></button>';
                }
                html += '</td></tr>';
            });
            overlay(domainsListOptions);
            $('.upload-button span').text('Activate');
            if (html !== '') {
                $('#domains-list-table > tbody').html(html);
            }
        }

        function pushDomains(active_version)
        {
            let domains = [];
            $('input[name="domain[]"').each(function () {
                let new_domain = $(this).val();
                let new_comment = $(this).closest('tr').find("input[name='comment[]']").val();
                domains.push({
                    name: new_domain,
                    comment: new_comment
                });
            });
            let activate_vcl = false;

            if ($('#fastly_activate_vcl').is(':checked')) {
                activate_vcl = true;
            }

            $.ajax({
                type: "POST",
                url: config.pushDomainsUrl,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_vcl,
                    'domains': domains,
                    'current_domains': current_domains
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        successDomainsBtnMsg.text($.mage.__('Domains successfully updated.')).show();
                        active_version = response.active_version;
                        getDomains(active_version, false).done(function (response) {
                            $('.loading-domains').hide();
                            if (response !== false) {
                                if (response.domains.length > 0) {
                                    domains = response.domains;
                                    processDomains(response.domains);
                                } else {
                                    $('.no-domains').show();
                                }
                            }
                        });
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return errorDomainsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        /**
         * Manage domains button on click event
         */
        $('#manage-domains-button').on('click', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            resetAllMessages();

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true,
                success: function (service) {
                    if (service.status === false) {
                        return errorDomainsBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                    }

                    active_version = service.active_version;
                    let next_version = service.next_version;
                    let service_name = service.service.name;

                    getDomains(active_version, true).done(function (response) {
                        if (response.status === true) {
                            processDomainsList(response.domains, response.store);
                            current_domains = response.domains;
                        } else {
                            processDomainsList([]);
                        }
                        setServiceLabel(active_version, next_version, service_name);
                    }).fail(function () {
                        return errorDomainsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                    });
                },
                fail: function () {
                    return errorDomainsBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        });

        $('body').on('click', '#add-domain', function () {
            $('#domains-list-table > tbody').append('<tr><td>' +
                '<input name="domain[]" value="" class="input-text admin__control-text domain-name-field" type="text"></td>' +
                '<td><input name="comment[]" value="" class="input-text admin__control-text comment-field" type="text"></td>' +
                '<td class="col-actions">' +
                '<button class="action-delete remove_domain"  title="Delete" type="button"><span>Delete</span></button>' +
                '</td></tr>');
        });

        $('body').on('click', '.remove_domain', function () {
            let closestTr = $(this).closest('tr');
            let domainName = closestTr.find('.domain-name-field').val();
            if (!domainName) {
                closestTr.remove();
            } else {
                confirm({
                    title: 'Remove Domain',
                    content: 'Are you sure you want to remove the <b>'+domainName+'</b> domain?',
                    actions: {
                        confirm: function () {
                            closestTr.remove();
                        },
                        cancel: function () {}
                    }
                });
            }
        });
    }
});
