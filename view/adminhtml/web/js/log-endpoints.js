define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    'mage/translate',
    'mage/validation'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let active_version = serviceStatus.active_version;
        let logEndpointModal;
        let conditionModal;
        let conditions;

        let createLogEndpointOptions = {
            title: jQuery.mage.__('Create Endpoint'),
            endpointType: '',
            content: function () {
                return document.getElementById('fastly-create-log-endpoint-template-' + this.endpointType).textContent;
            },
            actionOk: function () {
                createLogEndpoint(active_version);
            }
        };

        let updateLogEndpointOptions = {
            title: jQuery.mage.__('Update Endpoint'),
            endpointType: '',
            content: function () {
                return document.getElementById('fastly-create-log-endpoint-template-' + this.endpointType).textContent;
            },
            actionOk: function () {
                updateLogEndpoint(active_version);
            }
        };

        let createResponseConditionOptions = {
            title: jQuery.mage.__('Create a new response condition'),
            content: function () {
                return document.getElementById('fastly-create-condition-template').textContent;
            },
            actionOk: function () {
                createCondition();
            },
        };

        // initialize log endpoint table
        getLogEndpoints(active_version, false).done(function (response) {
            $('.loading-log-endpoints').hide();
            if (response !== false) {
                if (response.endpoints.length > 0) {
                    processLogEndpoints(response.endpoints);
                } else {
                    $('.no-log-endpoints').show();
                }
            }
        });

        function getLogEndpoint(active_version, type, name, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.fetchLogEndpointUrl,
                showLoader: loaderVisibility,
                data: {active_version, type, name}
            });
        }

        function getLogEndpoints(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.fetchAllLogEndpointsUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        function getResponseConditions(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.getResponseConditionsUrl,
                showLoader: loaderVisibility,
                data: {'active_version': active_version}
            });
        }

        function renderResponseConditions(active_version, loaderVisibility)
        {
            $('#condition_name').val('');
            $('#apply_if').val('');
            $('#condition_priority').val('');
            return getResponseConditions(active_version, loaderVisibility)
                .done(function (response) {
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
                    $('#conditions').prop('disabled', false);
                    $('#detach').show();
                    $('#create-response-condition').show();
                    $('#sep').show();
                    $('#conditions').html(html);
                })
        }

        function processLogEndpoints(endpoints)
        {
            $('#fastly-log-endpoints-list').html('');
            $.each(endpoints, function (index, endpoint) {
                let html = '<tr>' +
                    '<td>' +
                        '<input value="' + endpoint.label + '" disabled="disabled" class="input-text" type="text"/>' +
                    '</td>' +
                    '<td class="col-actions">' +
                        '<button class="action-delete fastly-edit-log-endpoint" type="button" title="Edit Log Endpoint" data-endpoint-type="' + endpoint.type + '" data-endpoint-name="' + endpoint.name + '"></button>' +
                    '</td>';
                $('#fastly-log-endpoints-list').append(html);
            });
        }

        function updateLogEndpoint(active_version)
        {
            let form = $('#create-log-endpoint-form');
            form.validate({})
            if (!form.valid()) {
                return;
            }

            let activateFlag = $('#fastly_activate_log_endpoint').is(':checked');
            let data = form.serialize();
            data += "&active_version=" + encodeURIComponent(active_version);
            data += "&activate_flag=" + encodeURIComponent(activateFlag);
            data = data.replace(/%0D/g, '')

            $.ajax({
                type: "POST",
                url: config.updateLogEndpointUrl,
                data: data,
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        $('#fastly-success-log-endpoint-button-msg').text($.mage.__('Endpoint successfully updated.')).show();
                        active_version = response.active_version;
                        logEndpointModal.modal('closeModal');
                        getLogEndpoints(active_version, false).done(function (resp) {
                            $('.loading-log-endpoints').hide();
                            if (resp !== false) {
                                if (resp.endpoints.length > 0) {
                                    logEndpoints = resp.endpoints;
                                    processLogEndpoints(resp.endpoints);
                                    $('.no-log-endpoints').hide();
                                } else {
                                    $('.no-log-endpoints').show();
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

        function createLogEndpoint(active_version)
        {
            let form = $('#create-log-endpoint-form');
            form.validate({})
            if (!form.valid()) {
                return;
            }

            let activateFlag = $('#fastly_activate_log_endpoint').is(':checked');
            let data = form.serialize();
            data += "&active_version=" + encodeURIComponent(active_version);
            data += "&activate_flag=" + encodeURIComponent(activateFlag);
            data = data.replace(/%0D/g, '')

            $.ajax({
                type: "POST",
                url: config.createLogEndpointUrl,
                data: data,
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        $('#fastly-success-log-endpoint-button-msg').text($.mage.__('Endpoint successfully created.')).show();
                        active_version = response.active_version;
                        logEndpointModal.modal('closeModal');
                        $('#fastly_add_log_endpoint_button').remove();
                        $('#fastly_cancel_log_endpoint_button').remove();
                        $('#fastly_add_log_endpoint_type').remove();
                        $('#fastly_add_log_endpoint_note').remove();
                        $('#fastly_create_log_endpoint_button').show();
                        getLogEndpoints(active_version, false).done(function (resp) {
                            $('.loading-log-endpoints').hide();
                            if (resp !== false) {
                                if (resp.endpoints.length > 0) {
                                    logEndpoints = resp.endpoints;
                                    processLogEndpoints(resp.endpoints);
                                    $('.no-log-endpoints').hide();
                                } else {
                                    $('.no-log-endpoints').show();
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
            let conditionName = $('#condition_name_modal').val();
            let applyIf = $('#apply_if_modal').val();
            let conditionPriority = $('#condition_priority_modal').val();
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

            $('#conditions').prop('disabled', true);
            $('#conditions').html('<option value="'+conditionName+'" selected="selected">'+conditionName+' (RESPONSE) '+applyIf+'</option>');
            $('#condition_name').val(conditionName);
            $('#apply_if').val(applyIf);
            $('#condition_priority').val(conditionPriority);
            $('#detach').hide();
            $('#create-response-condition').show();
            $('#sep').hide();
            conditionModal.modal('closeModal');
            $('.fastly-message-error').hide();
        }

        $('body').on('click', '#fastly_create_log_endpoint_button', function () {
            let endpointType = $('<select id="fastly_add_log_endpoint_type">');
            for (const type in config.availableLogProviders) {
                endpointType.append(new Option(config.availableLogProviders[type], type));
            }
            let addBtn = $('<button id="fastly_add_log_endpoint_button" title="Add" type="button" class="action-default scalable" style="margin-right: 10px"><span>Add</span></button>');
            let cancelBtn = $('<button id="fastly_cancel_log_endpoint_button" title="Cancel" type="button" class="action-default scalable" style=""><span>Cancel</span></button>');
            $(this).after(endpointType);
            endpointType.after(addBtn);
            addBtn.after(cancelBtn);
            endpointType.after('<p class="note" id="fastly_add_log_endpoint_note">For more information, read our <a href="https://docs.fastly.com/en/guides/diagnostics#_streaming-logs" target="_blank" rel="noreferrer noopener">logging documentation</a>.</p>');
            $(this).hide();
            endpointType.focus();
        });

        $('body').on('click', '#fastly_add_log_endpoint_button', function () {
            let endpointType = $('#fastly_add_log_endpoint_type').val();
            if (endpointType !== '') {
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
                        url: config.createLogEndpointUrl,
                        showLoader: true,
                        data: {
                            endpoint_type: endpointType,
                            form: false
                        }
                    }).done(function (response) {
                        if (response.status !== false) {
                            active_version = checkService.active_version;
                            let next_version = checkService.next_version;
                            let service_name = checkService.service.name;

                            createLogEndpointOptions.endpointType = response.endpointType

                            overlay(createLogEndpointOptions);
                            $('[name="endpoint_type"]').val(endpointType);
                            $('[name="old_name"]').val('');
                            $('#conditions').hide();
                            $('#detach').hide();
                            $('#create-response-condition').hide();
                            $('#sep').hide();

                            logEndpointModal = modal;
                            setServiceLabel(active_version, next_version, service_name);
                            $('.upload-button span').text('Create');
                        } else {
                            $('#fastly-error-create-backend-button-msg').text($.mage.__(response.msg)).show();
                        }
                    });
                });
            }
        });

        $('body').on('click', '#fastly_cancel_log_endpoint_button', function () {
            $('#fastly_add_log_endpoint_button').remove();
            $('#fastly_cancel_log_endpoint_button').remove();
            $('#fastly_add_log_endpoint_type').remove();
            $('#fastly_add_log_endpoint_note').remove();
            $('#fastly_create_log_endpoint_button').show();
        });

        function initValues(endpoint)
        {
            if (endpoint.response_condition) {
                renderResponseConditions(active_version, true).done(function () {
                    $('#conditions').val(endpoint.response_condition);
                })
            } else {
                $('#conditions').hide();
                $('#detach').hide();
                $('#create-response-condition').hide();
                $('#sep').hide();
            }

            const formElements = document.forms['create-log-endpoint-form'].elements;
            for (const prop in endpoint) {
                let element = formElements.namedItem(`log_endpoint[${prop}]`);
                if (element) {
                    $(element).val(endpoint[prop]);
                }
            }
        }

        $('body').on('click', 'button.fastly-edit-log-endpoint', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            let endpointType = $(this).data('endpoint-type');
            let endpointName = $(this).data('endpoint-name');

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (checkService) {
                active_version = checkService.active_version;
                let next_version = checkService.next_version;
                let service_name = checkService.service.name;

                getLogEndpoint(active_version, endpointType, endpointName, true).done(function (response) {
                    if (response !== false && response.endpoint) {
                        updateLogEndpointOptions.endpointType = endpointType;

                        overlay(updateLogEndpointOptions);
                        $('[name="endpoint_type"]').val(endpointType);
                        $('[name="old_name"]').val(endpointName);
                        initValues(response.endpoint);
                        logEndpointModal = modal;
                        setServiceLabel(active_version, next_version, service_name);
                        $('.upload-button span').text('Update');
                    }
                });
            });
        });

        $('body').on('click', '#attach', function () {
            renderResponseConditions(active_version, true)
        });

        $('body').on('click', '#detach', function () {
            $('#conditions').html('');
            $('#conditions').hide();
            $('#detach').hide();
            $('#sep').hide();
            $('#create-response-condition').hide();
            $('#attach_span').show();
        });

        $('body').on('click', '#create-response-condition', function () {
            overlay(createResponseConditionOptions);
            conditionModal = modal;
            $('.upload-button span').text('Create');
        });
    }
});

