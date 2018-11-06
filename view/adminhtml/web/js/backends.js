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
    }
});
