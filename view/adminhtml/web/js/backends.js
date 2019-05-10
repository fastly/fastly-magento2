define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "Magento_Ui/js/modal/prompt",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, prompt) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let backends;
        let backend_name;
        let active_version = serviceStatus.active_version;

        /**
         * Backend modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let backendOptions = {
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-backend-template').textContent;
            },
            actionOk: function () {
                if ($('#backend-upload-form').valid()) {
                    configureBackend(active_version);
                }
            }
        };

        let createBackendOptions = {
            title: jQuery.mage.__('Create Backend'),
            content: function () {
                return document.getElementById('fastly-create-backend-template').textContent;
            },
            actionOk: function () {
                createBackend(active_version);
            }
        };

        /**
         * Trigger the Backends list call
         */
        getBackends(active_version, false).done(function (response) {
            $('.loading-backends').hide();
            if (response !== false) {
                if (response.backends.length > 0) {
                    backends = response.backends;
                    processBackends(response.backends);
                } else {
                    $('.no-backends').show();
                }
            }
        });

        /**
         * Get the list of Backends
         *
         * @param active_version
         * @param loaderVisibility
         * @returns {*}
         */
        function getBackends(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.fetchBackendsUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        function getAllConditions(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.getAllConditionsUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        /**
         * Process and display the list of Backends
         *
         * @param backends
         */
        function processBackends(backends)
        {
            $('#fastly-backends-list').html('');
            $.each(backends, function (index, backend) {
                let html = "<tr id='fastly_" + index + "'>";
                html += "<td><input data-backendId='"+ index + "' id='backend_" + index + "' value='"+ backend.name +"' disabled='disabled' class='input-text' type='text'></td>";
                html += "<td class='col-actions'><button class='action-delete fastly-edit-backend-icon' data-backend-id='" + index + "' id='fastly-edit-backend_"+ index + "' title='Edit backend' type='button'></td></tr>";
                $('#fastly-backends-list').append(html);
            });
        }

        /**
         * Update the Backend configuration
         */
        function configureBackend()
        {
            let activate_backend = false;

            if ($('#fastly_activate_backend').is(':checked')) {
                activate_backend = true;
            }

            $.ajax({
                type: "POST",
                url: config.configureBackendUrl,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_backend,
                    'name': $('#backend_name').val(),
                    'shield': $('#backend_shield').val(),
                    'connect_timeout': $('#backend_connect_timeout').val(),
                    'between_bytes_timeout': $('#backend_between_bytes_timeout').val(),
                    'first_byte_timeout': $('#backend_first_byte_timeout').val()
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        $('#fastly-success-backend-button-msg').text($.mage.__('Backend "'+backend_name+'" is successfully updated.')).show();
                        active_version = response.active_version;
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                }
            });
        }

        function createBackend(active_version)
        {
            let activate_backend = false;

            if ($('#fastly_activate_backend').is(':checked')) {
                activate_backend = true;
            }

            let condition = $('#conditions').val();
            let backendName = $('#backend_name').val();
            let backendAddress = $('#backend_address').val();
            let backendShield = $('#backend_shield').val();
            let enableTls = $('input:radio[name=tls-radio]:checked').val();
            let tlsYesPort = $('#tls-yes-port').val();
            let tlsNoPort = $('#tls-no-port').val();
            let verifyCertificate = $('input:radio[name=certificate-radio]:checked').val();
            let certificateHostname = $('#certificate-hostname').val();
            let sniHostname = $('#sni-hostname').val();
            let matchSni = $('#match-sni').is(':checked');
            let tlsCaCertificate = $('#tls-ca-certificate').val();
            let minimumTls = $('#minimum-tls').val();
            let maximumTls = $('#maximum-tls').val();
            let ciphersuites = $('#ciphersuites').val();
            let tlsClientCertificate = $('#tls-client-certificate').val();
            let tlsClientKey = $('#tls-client-key').val();
            let maximumConnections = $('#backend_maximum_connections').val();
            let errorThreshold = $('#backend_error_threshold').val();
            let connectionTimeout = $('#backend_connect_timeout').val();
            let firstByteTimeout = $('#backend_first_byte_timeout').val();
            let betweenBytesTimeout = $('#backend_between_bytes_timeout').val();
            let autoLoadBalance = $('#auto_load_balance').val();
            let weight = $('#weight').val();

            $.ajax({
                type: "POST",
                url: config.createBackendUrl,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_backend,
                    'request_condition': condition,
                    'name': backendName,
                    'address': backendAddress,
                    'shield': backendShield,
                    'use_ssl': enableTls,
                    'tls_yes_port': tlsYesPort,
                    'tls_no_port': tlsNoPort,
                    'verify_certificate': verifyCertificate,
                    'ssl_cert_hostname': certificateHostname,
                    'ssl_sni_hostname': sniHostname,
                    'match_sni': matchSni,
                    'ssl_ca_cert': tlsCaCertificate,
                    'max_tls_version': maximumTls,
                    'min_tls_version': minimumTls,
                    'ssl_ciphers': ciphersuites,
                    'ssl_client_cert': tlsClientCertificate,
                    'ssl_client_key': tlsClientKey,
                    'max_conn': maximumConnections,
                    'error_threshold': errorThreshold,
                    'connect_timeout': connectionTimeout,
                    'first_byte_timeout': firstByteTimeout,
                    'between_bytes_timeout': betweenBytesTimeout,
                    'auto_loadbalance': autoLoadBalance,
                    'weight': weight,
                    'form': true
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        $('#fastly-success-backend-button-msg').text($.mage.__('Backend "'+backendName+'" is successfully created.')).show();
                        active_version = response.active_version;
                        modal.modal('closeModal');
                        getBackends(active_version, false).done(function (resp) {
                            $('.loading-backends').hide();
                            if (resp !== false) {
                                if (resp.backends.length > 0) {
                                    backends = resp.backends;
                                    processBackends(resp.backends);
                                } else {
                                    $('.no-backends').show();
                                }
                            }
                        });
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                }
            });
        }

        $('body').on('click', '#fastly_create_backend_button', function () {
            prompt({
                title: 'Create a host',
                content: 'Enter a  hostname or IPv4 address for the backend',
                actions: {
                    confirm: function (input) {
                        if (input !== '') {
                            if (isAlreadyConfigured !== true) {
                                $(this).attr('disabled', true);
                                return alert($.mage.__('Please save config prior to continuing.'));
                            }
                            $.ajax({
                                type: "GET",
                                url: config.serviceInfoUrl,
                                showLoader: true
                            }).done(function (checkService) {
                                $.ajax({
                                    type: "POST",
                                    url: config.createBackendUrl,
                                    showLoader: true,
                                    data: {
                                        address: input,
                                        form: false
                                    }
                                }).done(function (response) {
                                    if (response.status !== false) {
                                        active_version = checkService.active_version;
                                        let next_version = checkService.next_version;
                                        let service_name = checkService.service.name;

                                        overlay(createBackendOptions);
                                        setServiceLabel(active_version, next_version, service_name);
                                        $('#conditions').hide();
                                        $('#detach').hide();
                                        $('#backend_address').val(input);
                                        $('#sni-hostname').val(input);
                                        $('#certificate-hostname').val(input);

                                        if ($('#auto_load_balance').val() === '0') {
                                            $('.weight').hide();
                                        } else {
                                            $('.weight').show();
                                        }
                                        $('#tls-no-port').attr('disabled', true);
                                    } else {
                                        $('#fastly-error-backend-button-msg').text($.mage.__(response.msg)).show();
                                    }
                                });
                            });
                        }
                    },
                    cancel: function () {

                    },
                    always: function () {}
                }
            });
        });

        $('body').on('change', '#auto_load_balance', function () {
            if (this.value === '1') {
                $('.weight').show();
            } else {
                $('.weight').hide();
            }
        });

        $('body').on('click', 'input:radio[name=tls-radio]', function () {
            if (this.value === '0') {
                $('#certificate-yes').attr('disabled', true);
                $('#certificate-no').attr('disabled', true);
                $('#certificate-hostname').attr('disabled', true);
                $('#tls-yes-port').attr('disabled', true);
                $('#tls-no-port').attr('disabled', false);
                $('#sni-hostname').attr('disabled', true);
                $('#match-sni').attr('disabled', true);
                $('#tls-ca-certificate').attr('disabled', true);
                $('#minimum-tls').attr('disabled', true);
                $('#maximum-tls').attr('disabled', true);
                $('#ciphersuites').attr('disabled', true);
                $('#tls-client-certificate').attr('disabled', true);
                $('#tls-client-key').attr('disabled', true);
            } else {
                $('#certificate-yes').attr('disabled', false);
                $('#certificate-no').attr('disabled', false);
                $('#certificate-hostname').attr('disabled', false);
                $('#tls-yes-port').attr('disabled', false);
                $('#tls-no-port').attr('disabled', true);
                $('#sni-hostname').attr('disabled', false);
                $('#match-sni').attr('disabled', false);
                $('#tls-ca-certificate').attr('disabled', false);
                $('#minimum-tls').attr('disabled', false);
                $('#maximum-tls').attr('disabled', false);
                $('#ciphersuites').attr('disabled', false);
                $('#tls-client-certificate').attr('disabled', false);
                $('#tls-client-key').attr('disabled', false);
            }
        });

        $('body').on('click', 'input:radio[name=certificate-radio]', function () {
            if (this.value === '0') {
                $('#certificate-hostname').attr('disabled', true);
                $('#match-sni').attr('disabled', true);
            } else {
                $('#certificate-hostname').attr('disabled', false);
                $('#match-sni').attr('disabled', false);
            }
        });

        /**
         * Edit Backend button on click event
         */
        $('body').on('click', 'button.fastly-edit-backend-icon', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            let backend_id = $(this).data('backend-id');

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (checkService) {
                active_version = checkService.active_version;
                let next_version = checkService.next_version;
                let service_name = checkService.service.name;

                getBackends(active_version, true).done(function (response) {
                    if (response !== false) {
                        if (response.backends.length > 0) {
                            backends = response.backends;
                        }
                    }

                    if (backends != null && backend_id != null) {
                        overlay(backendOptions);
                        setServiceLabel(active_version, next_version, service_name);

                        $('.upload-button span').text('Update');
                        backend_name = backends[backend_id].name;
                        $('.modal-title').text($.mage.__('Backend "'+backend_name+'" configuration'));
                        $('#backend_name').val(backends[backend_id].name);
                        $('#backend_shield option[value=\'' + backends[backend_id].shield +'\']').attr('selected','selected');
                        $('#backend_connect_timeout').val(backends[backend_id].connect_timeout);
                        $('#backend_between_bytes_timeout').val(backends[backend_id].between_bytes_timeout);
                        $('#backend_first_byte_timeout').val(backends[backend_id].first_byte_timeout);
                    }
                });
            });
        });

        $('body').on('click', '#attach', function () {
            getAllConditions(active_version, true).done(function (response) {
                let html = '';
                $('#attach_span').hide();
                if (response !== false) {
                    let conditions = response.conditions;
                    $.each(conditions, function (index, condition) {
                        html += '<option value=""></option>';
                        html += '<option value="'+condition.name+'">'+condition.name+' ('+condition.type+') '+condition.statement+'</option>';
                    });
                }
                $('#conditions').show();
                $('#detach').show();
                $('#conditions').html(html);
            })
        });

        $('body').on('click', '#detach', function () {
            $('#conditions').hide();
            $('#detach').hide();
            $('#attach_span').show();
        });
    }
});
