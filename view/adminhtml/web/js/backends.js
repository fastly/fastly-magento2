define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let backends;
        let backend_name;
        let active_version = serviceStatus.active_version;
        let conditionName = null;
        let applyIf = null;
        let conditionPriority = null;
        let backendModal;
        let conditionModal;
        let conditions;
        let oldName;

        /**
         * Backend modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let backendOptions = {
            title: jQuery.mage.__(' '),
            content: function () {
                return document.getElementById('fastly-create-backend-template').textContent;
            },
            actionOk: function () {
                configureBackend(active_version);
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

        let createConditionOptions = {
            title: jQuery.mage.__('Create a new request condition'),
            content: function () {
                return document.getElementById('fastly-create-condition-template').textContent;
            },
            actionOk: function () {
                createCondition();
            },
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
                    'old_name': oldName,
                    'name': $('#backend_name').val(),
                    'backend_address': $('#backend_address').val(),
                    'auto_loadbalance': $('#auto_loadbalance').val(),
                    'weight': $('#weight').val(),
                    'shield': $('#backend_shield').val(),
                    'use_ssl': $('input:radio[name=tls-radio]:checked').val(),
                    'tls_yes_port': $('#tls-yes-port').val(),
                    'tls_no_port': $('#tls-no-port').val(),
                    'ssl_check_cert': $('input:radio[name=certificate-radio]:checked').val(),
                    'ssl_cert_hostname': $('#certificate-hostname').val(),
                    'ssl_sni_hostname': $('#sni-hostname').val(),
                    'ssl_ca_cert': $('#tls-ca-certificate').val(),
                    'min_tls_version': $('#minimum-tls').val(),
                    'max_tls_version': $('#maximum-tls').val(),
                    'ssl_ciphers': $('#ciphersuites').val(),
                    'max_conn': $('#backend_maximum_connections').val(),
                    'error_threshold': $('#backend_error_threshold').val(),
                    'connect_timeout': $('#backend_connect_timeout').val(),
                    'first_byte_timeout': $('#backend_first_byte_timeout').val(),
                    'between_bytes_timeout': $('#backend_between_bytes_timeout').val(),
                    'override_host': $('#override_host').val(),
                    'condition_name': $('#condition_name').val(),
                    'apply_if': $('#apply_if').val(),
                    'condition_priority': $('#condition_priority').val(),
                    'request_condition': $('#conditions').val()
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        $('#fastly-success-backend-button-msg').text($.mage.__('Backend "'+backend_name+'" is successfully updated.')).show();
                        active_version = response.active_version;
                        backendModal.modal('closeModal');
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
                        })
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
            let autoLoadBalance = $('#auto_loadbalance').val();
            let weight = $('#weight').val();
            let overrideHost = $('#override_host').val();

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
                    'ssl_check_cert': verifyCertificate,
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
                    'form': true,
                    'condition_name': conditionName,
                    'condition_priority': conditionPriority,
                    'apply_if': applyIf,
                    'override_host': overrideHost
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        $('#fastly-success-backend-button-msg').text($.mage.__('Backend "'+backendName+'" is successfully created.')).show();
                        active_version = response.active_version;
                        backendModal.modal('closeModal');
                        $('#fastly_add_backend_button').remove();
                        $('#fastly_cancel_backend_button').remove();
                        $('.hostname').remove();
                        $('.backend-note').remove();
                        $('#fastly_create_backend_button').show();
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

        function createCondition()
        {
            conditionName = $('#condition_name').val();
            applyIf = $('#apply_if').val();
            conditionPriority = $('#condition_priority').val();
            if (applyIf.length > 512) {
                showErrorMessage('The expression cannot contain more than 512 characters.');
                return;
            } else if (applyIf.length < 1 || conditionName.length < 1) {
                showErrorMessage('Please fill in the required fields.');
                return;
            } else if (isNaN(parseInt(conditionPriority))) {
                showErrorMessage('Priority value must be an integer.');
                return;
            }
            let html = '';
            html += '<option value="">no condition</option>';
            $.each(conditions, function (index, condition) {
                if (condition.type === "REQUEST") {
                    html += '<option value="'+condition.name+'">'+condition.name+' ('+condition.type+') '+condition.statement+'</option>';
                }
            });
            $('#conditions').html(html);
            $('#conditions').append('<option value="'+conditionName+'" selected="selected">'+conditionName+' (REQUEST) '+applyIf+'</option>');
            conditionModal.modal('closeModal');
            $('.fastly-message-error').hide();
        }

        $('body').on('click', '#fastly_create_backend_button', function () {
            let hostname = $('<input type="text" class="hostname">');
            let addBtn = $('<button id="fastly_add_backend_button" title="Add" type="button" class="action-default scalable" style="margin-right: 10px"><span>Add</span></button>');
            let cancelBtn = $('<button id="fastly_cancel_backend_button" title="Cancel" type="button" class="action-default scalable" style=""><span>Cancel</span></button>');
            $(this).after(hostname);
            hostname.after(addBtn);
            addBtn.after(cancelBtn);
            hostname.after('<p class="note backend-note">Enter a hostname or IPv4 address for the backend</p>');
            $(this).hide();
            hostname.focus();
        });

        $('body').on('click', '#fastly_add_backend_button', function () {
            let hostnameVal = $('.hostname').val();
            if (hostnameVal !== '') {
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
                            address: hostnameVal,
                            form: false
                        }
                    }).done(function (response) {
                        if (response.status !== false) {
                            active_version = checkService.active_version;
                            let next_version = checkService.next_version;
                            let service_name = checkService.service.name;

                            overlay(createBackendOptions);
                            backendModal = modal;
                            setServiceLabel(active_version, next_version, service_name);
                            $('.upload-button span').text('Create');
                            $('#conditions').hide();
                            $('#detach').hide();
                            $('#create-condition').hide();
                            $('#sep').hide();
                            $('#backend_address').val(hostnameVal);
                            $('#sni-hostname').val(hostnameVal);
                            $('#certificate-hostname').val(hostnameVal);
                            verifyCertificateNotification();

                            if ($('#auto_loadbalance').val() === '0') {
                                $('.weight').hide();
                            } else {
                                $('.weight').show();
                            }
                            $('#tls-no-port').attr('disabled', true);
                        } else {
                            $('#fastly-error-create-backend-button-msg').text($.mage.__(response.msg)).show();
                        }
                    });
                });
            }
        });

        $('body').on('click', '#fastly_cancel_backend_button', function () {
            $('#fastly_add_backend_button').remove();
            $('#fastly_cancel_backend_button').remove();
            $('.hostname').remove();
            $('.backend-note').remove();
            $('#fastly_create_backend_button').show();
        });

        $('body').on('change', '#auto_loadbalance', function () {
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

        function initValues(backend)
        {
            $('#backend_name').val(backend.name);
            $('#backend_address').val(backend.address);
            $('#backend_shield option[value=\'' + backend.shield +'\']').attr('selected','selected');
            let loadBalance = backend.auto_loadbalance === true ? 1 : 0;
            $('#auto_loadbalance option[value=\'' + loadBalance +'\']').attr('selected', 'selected');
            $('#weight').val(backend.weight);
            if (!loadBalance) {
                $('#weight').parent().parent().hide();
            }

            if (backend.ssl_check_cert !== false) {
                $('#certificate-yes').prop('checked', true);
                $('#certificate-hostname').val(backend.ssl_cert_hostname);
            } else {
                $('#certificate-no').prop('checked', true);
                $('#certificate-hostname').prop('disabled', true);
                $('#match-sni').prop('disabled', true);
            }

            if (backend.use_ssl) {
                $('#tls-yes').prop('checked', true);
                $('#tls-no').prop('checked', false);
                $('#tls-no-port').prop('disabled', true);
            } else {
                $('#tls-yes').prop('checked', false);
                $('#tls-no').prop('checked', true);
                $('#tls-no-port').val(backend.port);
                $('#certificate-hostname').prop('disabled', true);
                $('#tls-ca-certificate').prop('disabled', true);
                $('#certificate-yes').prop('disabled', true);
                $('#certificate-no').prop('disabled', true);
                $('#sni-hostname').prop('disabled', true);
                $('#minimum-tls').prop('disabled', true);
                $('#match-sni').prop('disabled', true);
                $('#maximum-tls').prop('disabled', true);
                $('#ciphersuites').prop('disabled', true);
                $('#tls-client-key').prop('disabled', true);
                $('#tls-yes-port').prop('disabled', true);
                $('#tls-client-certificate').prop('disabled', true);
            }

            if (backend.auto_loadbalance !== true) {
                $('#weight').parent().parent().hide();
            }

            $('#certificate-hostname').val(backend.ssl_cert_hostname);
            $('#sni-hostname').val(backend.ssl_sni_hostname);
            $('#tls-ca-certificate').val(backend.ssl_ca_cert);
            $('#minimum-tls').val(backend.min_tls_version);
            $('#maximum-tls').val(backend.max_tls_version);
            $('#ciphersuites').val(backend.ssl_ciphers);
            $('#tls-client-certificate').val(backend.ssl_client_cert);
            $('#tls-client-key').val(backend.ssl_client_key);
            $('#backend_maximum_connections').val(backend.max_conn);
            $('#backend_error_threshold').val(backend.error_threshold);
            $('#backend_connect_timeout').val(backend.connect_timeout);
            $('#backend_first_byte_timeout').val(backend.first_byte_timeout);
            $('#backend_between_bytes_timeout').val(backend.between_bytes_timeout);
            $('#override_host').val(backend.override_host);
        }

        function verifyCertificateNotification()
        {
            $('#certificate-no').on('change', function () {
                $('#certificate-no-msg').empty();
                let msg = document.createTextNode('Not verifying the certificate has serious security implications,' +
                    ' including vulnerability to');

                let a = document.createElement('a');
                let text = document.createTextNode(' man-in-the-middle attacks.');
                a.appendChild(text);
                a.title = 'man-in-the-middle attacks';
                a.href = 'https://en.wikipedia.org/wiki/Man-in-the-middle_attack';
                a.target = '_blank';
                $('#certificate-no-msg').append(msg);
                $('#certificate-no-msg').append(a);
            });
            $('#certificate-yes').on('change', function () {
                $('#certificate-no-msg').empty();
            });
        }
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
                        backendModal = modal;
                        setServiceLabel(active_version, next_version, service_name);
                        $('#conditions').hide();
                        $('#detach').hide();
                        $('#create-condition').hide();
                        $('#sep').hide();
                        initValues(backends[backend_id]);
                        verifyCertificateNotification();
                        oldName = $('#backend_name').val();
                        $('.upload-button span').text('Update');
                        backend_name = backends[backend_id].name;
                        $('.modal-title').text($.mage.__('Backend "'+backend_name+'" configuration'));
                    }
                });
            });
        });

        $('body').on('click', '#attach', function () {
            getAllConditions(active_version, true).done(function (response) {
                let html = '';
                $('#attach_span').hide();
                if (response !== false) {
                    conditions = response.conditions;
                    html += '<option value="">no condition</option>';
                    $.each(conditions, function (index, condition) {
                        if (condition.type === "REQUEST") {
                            html += '<option value="'+condition.name+'">'+condition.name+' ('+condition.type+') '+condition.statement+'</option>';
                        }
                    });
                }
                $('#conditions').show();
                $('#detach').show();
                $('#create-condition').show();
                $('#sep').show();
                $('#conditions').html(html);
            })
        });

        $('body').on('click', '#detach', function () {
            $('#conditions').html('');
            $('#conditions').hide();
            $('#detach').hide();
            $('#sep').hide();
            $('#create-condition').hide();
            $('#attach_span').show();
        });

        $('body').on('click', '#create-condition', function () {
            overlay(createConditionOptions);
            conditionModal = modal;
            $('.upload-button span').text('Create');
        });
    }
});

