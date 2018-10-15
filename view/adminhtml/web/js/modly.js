define([
    "jquery",
    "handlebars",
    "setServiceLabel",
    "popup",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    'mage/translate'
], function ($, Handlebars, setServiceLabel, popup, resetAllMessages, showErrorMessage, showSuccessMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {
        let successAllModulesBtnMsg = $('#fastly-success-all-modules-button-msg');
        let errorAllModulesBtnMsg = $('#fastly-error-all-modules-button-msg');
        let warningAllModulesBtnMsg = $('#fastly-warning-all-modules-button-msg');

        let active_version = setServiceLabel.active_version;

        let activeModuleOptions = {
            title: jQuery.mage.__(' '),
                content: function () {
                return document.getElementById('modly-active-module-template').textContent;
            },
            actionOk: function () {
                saveModuleConfig();
            }
        };

        let allModuleOptions = {
            title: jQuery.mage.__(' '),
                content: function () {
                return document.getElementById('modly-all-modules-template').textContent;
            },
            actionOk: function () {
                saveActiveModules();
            }
        };

        getActiveModules(false).done(function (response) {
            $('.loading-modules').hide();
            if (response.status !== false) {
                if (response.modules.length > 0) {
                    modules = response.modules;
                    processActiveModules(modules);
                } else {
                    $('#modly-active-modules-list').html('');
                    $('.no-modules').show();
                }
            } else {
                $('#modly-active-modules-list').html('');
                $('.no-modules').show();
            }
        });

        function saveModuleConfig() {
            let fieldData = {};
            let name = '';
            let value = '';
            let data = {};
            let moduleId = $('#module-id').val();
            let groupData = [];
            $('.modly-group').each(function () {
                $($(this).find('.modly-field')).each(function () {
                    name = $(this).attr('name');
                    value = $(this).val();
                    data[name] = value;
                });
                groupData.push(data);
                data = {};
            });
            if (groupName !== '') {
                fieldData[groupName] = groupData;
            } else {
                fieldData = groupData;
            }
            $.ajax({
                type: "POST",
                url: config.saveModuleConfigUrl,
                data: {
                    'module_id': moduleId,
                    'field_data': fieldData,
                    'group_name': groupName
                },
                showLoader: true,
                success: function (data) {
                    if (data.status === true) {
                        let parsedVcl = JSON.stringify(parseVcl(fieldData));
                        uploadModuleConfig(moduleId, parsedVcl, active_version).done(function (response) {
                            if (response.status === true) {
                                modal.modal('closeModal');
                                resetAllMessages();
                                successAllModulesBtnMsg.text($.mage.__('The '+ moduleId +' module has been successfully uploaded to the Fastly service.')).show();
                            } else {
                                resetAllMessages();
                                showErrorMessage(response.msg);
                            }
                        });
                    } else {
                        resetAllMessages();
                        showErrorMessage(data.msg);
                    }
                }
            });
        }

        function uploadModuleConfig(moduleId, parsedVcl, active_version) {
            return $.ajax({
                type: "POST",
                url: config.uploadModuleSnippetUrl,
                data: {
                    'module_id': moduleId,
                    'active_version': active_version,
                    'snippets': parsedVcl
                },
                showLoader: true
            });
        }

        function parseVcl(fieldData) {
            let moduleVcl = JSON.parse(module.manifest_vcl);
            let templates = [];
            let result = '';

            Handlebars.registerHelper('replace', (inp, re, repl) => inp.replace(new RegExp(re, 'g'), repl));
            Handlebars.registerHelper('ifEq', (a, b, options) => options[a == b ? 'fn':'inverse'](this));
            Handlebars.registerHelper('ifMatch', (a, pat, opts) => opts[a.match(new RegExp(pat)) ? 'fn':'inverse'](this));
            Handlebars.registerHelper('extract', (a, pat) => (a.match(new RegExp(pat)) || [])[1]);

            $.each(fieldData, function (key, fields) {
                $.each(moduleVcl, function (index, value) {
                    let vclTemplate = Handlebars.compile(value.template);
                    if (groupName !== '') {
                        result = vclTemplate(fieldData);
                    } else {
                        result = vclTemplate(fields);
                    }
                    templates.push({"type": value.type, "snippet": result})
                });
            });
            return templates;
        }

        function saveActiveModules() {
            let checkedModules = [];
            $('.module:checked').each(function () {
                checkedModules.push($(this).attr('name'));
            });
            $.ajax({
                type: "POST",
                url: config.toggleModulesUrl,
                data: {
                    'checked_modules': checkedModules
                },
                showLoader: true,
                success: function (data) {
                    if (data.status === true) {
                        modal.modal('closeModal');
                        resetAllMessages();
                        getActiveModules(false).done(function (response) {
                            $('.loading-modules').hide();
                            if (response.status !== false) {
                                if (response.modules.length > 0) {
                                    modules = response.modules;
                                    processActiveModules(modules);
                                } else {
                                    $('#modly-active-modules-list').html('');
                                    $('.no-modules').show();
                                }
                            } else {
                                $('#modly-active-modules-list').html('');
                                $('.no-modules').show();
                            }
                        })
                    }
                }
            });
        }

        function getActiveModules(loaderVisibility) {
            return $.ajax({
                type: "GET",
                url: config.getActiveModulesUrl,
                showLoader: loaderVisibility,
                beforeSend: function (xhr) {
                    $('.loading-modules').show();
                }
            });
        }

        function processActiveModules(modules) {
            let html = '';
            $.each(modules, function (index, module) {
                html += "<tr id='fastly_" + index + "'>";
                html += "<td><span data-moduleId='" + index + "' id='module_" + index + "' disabled='disabled' class='active-modules' type='text'><b>" + module.manifest_name + "</b></span>";
                html += "<p class='note'><span>" + module.manifest_description + "</span></p></td>";
                html += "<td class='col-actions'><button class='action-delete fastly-edit-active-modules-icon' data-module-id='" + module.manifest_id + "' id='fastly-edit-active-modules"+ index + "' title='Edit module' type='button'></td></tr>";
            });
            if (html !== '') {
                $('.no-modules').hide();
            }
            $('#modly-active-modules-list').html(html);
        }

        function getModuleData(module_id) {
            return $.ajax({
                type: "GET",
                url: config.getModuleDataUrl,
                data: {
                    'module_id': module_id
                },
                showLoader: true
            });
        }

        // Queries Fastly API to retrieve Dictionaries
        function listDictionaries(active_version)
        {
            return $.ajax({
                type: "GET",
                url: config.getDictionaries,
                showLoader: true,
                data: {'active_version': active_version},
                async: false
            });
        }

        // Queries Fastly API to retrieve ACLs
        function listAcls(active_version)
        {
            return $.ajax({
                type: "GET",
                url: config.getAcls,
                showLoader: true,
                data: {'active_version': active_version},
                async: false
            });
        }

        function getBackends(active_version)
        {
            return $.ajax({
                type: "GET",
                url: config.fetchBackendsUrl,
                showLoader: true,
                data: {'active_version': active_version},
                async: false
            });
        }

        function getAllConditions(active_version)
        {
            return $.ajax({
                type: "POST",
                url: config.getAllConditionsUrl,
                data: {'active_version': active_version},
                showLoader: true,
                async: false
            });
        }

        function getAllDomains(active_version)
        {
            return $.ajax({
                type: "POST",
                url: config.getAllDomainsUrl,
                data: {'active_version': active_version},
                showLoader: true,
                async: false
            });
        }

        function getCountries(active_version) {
            return $.ajax({
                type: "POST",
                url: config.getCountriesUrl,
                data: {
                    'active_version': active_version
                },
                showLoader: true,
                async: false
            });
        }

        function renderFields(property, value, active_version)
        {
            let field = $('<div class="admin__field field"></div>');
            let control = $('<div class="admin__field-control"></div>');
            let label = $('<label class="admin__field-label"></label>');
            let span = $('<span></span>');
            let note = $('<div class="admin__field-note"></div>');

            let textInput = $('<input class="input-text admin__control-text modly-field">');
            let textAreaInput = $('<textarea rows="10" class="admin__control-text modly-field"></textarea>');
            let selectInput = $('<select class="select admin__control-select modly-field"></select>');
            let selectOption  = $('<option></option>');

            let description = '';
            let fieldName = property.name;
            let fieldValue = '';
            let type = property.type;

            if (property.default) {
                fieldValue = property.default;
            }
            if (property.required === true) {
               field.prop(' _required');
            }
            if (property.description) {
                description = property.description;
            }
            if (value) {
                $.each(value, function (index, data) {
                    if (groupName !== '') {
                        if (index === fieldName) {
                            fieldValue = data;
                        }
                    } else {
                        if (index === fieldName) {
                            fieldValue = data;
                        }
                    }
                });
            }

            function aclAjax()
            {
                listAcls(active_version).done(function (response) {
                    if (response.status !== false) {
                        let acls = response.acls;
                        $.each(acls, function (index, acl) {
                            selectOption  = $('<option></option>');
                            selectOption.attr('value', acl.name);
                            if (acl.name === fieldValue) {
                                selectOption.attr('selected', true);
                            }
                            selectOption.html(acl.name);
                            selectInput.append(selectOption);
                        });
                        control.append(selectInput);
                    }
                });
            }

            function dictionaryAjax()
            {
                listDictionaries(active_version).done(function (response) {
                    if (response.status !== false) {
                        let dictionaries = response.dictionaries;
                        $.each(dictionaries, function (index, dictionary) {
                            selectOption  = $('<option></option>');
                            selectOption.attr('value', dictionary.name);
                            if (dictionary.name === fieldValue) {
                                selectOption.attr('selected', true);
                            }
                            selectOption.html(dictionary.name);
                            selectInput.append(selectOption);
                        });
                        control.append(selectInput);
                    }
                });
            }

            function backendsAjax()
            {
                getBackends(active_version).done(function (response) {
                    if (response.status !== false) {
                        let backends = response.backends;
                        selectOption  = $('<option></option>');
                        $.each(backends, function (index, backend) {
                            selectOption  = $('<option></option>');
                            selectOption.attr('value', backend.name);
                            if (backend.name === fieldValue) {
                                selectOption.attr('selected', true);
                            }
                            selectOption.html(backend.name);
                            selectInput.append(selectOption);
                        });
                        control.append(selectInput);
                    }
                });
            }

            function conditionsAjax(type)
            {
                getAllConditions(active_version).done(function (response) {
                    if (response.status !== false) {
                        let conditions = response.conditions;
                        $.each(conditions, function (index, condition) {
                            selectOption  = $('<option></option>');
                            if ((condition.type === 'REQUEST' && type === 'request') ||
                                (condition.type === 'RESPONSE' && type === 'response') ||
                                (condition.type === 'CACHE' && type === 'cache')) {
                                selectOption.attr('value', condition.name);
                                if (condition.name === fieldValue) {
                                    selectOption.attr('selected', true);
                                }
                                selectOption.html(condition.name);
                                selectInput.append(selectOption);
                            }
                        });
                        control.append(selectInput);
                    }
                });
            }

            function domainsAjax()
            {
                getAllDomains(active_version).done(function (response) {
                    if (response.status !== false) {
                        let domains = response.domains;
                        $.each(domains, function (index, domain) {
                            selectOption  = $('<option></option>');
                            selectOption.attr('value', domain.name);
                            if (domain.name === fieldValue) {
                                selectOption.attr('selected', true);
                            }
                            selectOption.html(domain.name);
                            selectInput.append(selectOption);
                        });
                        control.append(selectInput);
                    }
                });
            }

            function countriesAjax()
            {
                getCountries(active_version).done(function (response) {
                    if (response.status !== false) {
                        let countries = response.countries;
                        $.each(countries, function (index, country) {
                            selectOption  = $('<option></option>');
                            selectOption.attr('value', country.value);
                            if (country.value === fieldValue) {
                                selectOption.attr('selected', true);
                            }
                            selectOption.html(country.label);
                            selectInput.append(selectOption);
                        });
                        control.append(selectInput);
                    }
                });
            }

            function buildField()
            {
                span.append(property.label);
                label.attr('for', property.name);
                label.append(span);
                field.append(label);
                note.append(description);
                control.append(note);
                field.append(control);
                return field;
            }

            textInput.attr('name', property.name);
            textInput.attr('id', property.name);
            textInput.attr('required', 'required');
            textInput.val(fieldValue);

            textAreaInput.attr('name', property.name);
            textAreaInput.attr('id', property.name);
            textAreaInput.attr('required', 'required');
            textAreaInput.append(fieldValue);

            selectInput.attr('name', property.name);
            selectInput.attr('id', property.name);
            selectInput.append(selectOption.attr('value', '').append('--Please Select--'));

            switch (property.type) {
                case 'string':
                case 'rtime':
                case 'path':
                case 'url':
                    textInput.attr('type', 'text');
                    control.append(textInput);
                    return buildField();
                case 'integer':
                case 'float':
                    textInput.attr('type', 'number');
                    control.append(textInput);
                    return buildField();
                case 'time':
                    textInput.attr('type', 'time');
                    control.append(textInput);
                    return buildField();
                case 'longstring':
                    control.append(textAreaInput);
                    return buildField();
                case 'select':
                    $.each(property.options, function (key, option) {
                        selectOption = $('<option></option>');
                        selectOption.attr('value', key);
                        if (key === fieldValue) {
                           selectOption.attr('selected', true);
                        }
                        selectOption.append(option);
                        selectInput.append(selectOption);
                    });
                    control.append(selectInput);
                    return buildField();
                case 'boolean':
                    selectInput.html('');

                    selectOption  = $('<option></option>');
                    selectOption.attr('value', 'false');
                    selectOption.html('No');

                    if (fieldValue === 'false') {
                        selectOption.attr('selected', true);
                    }
                    selectInput.append(selectOption);

                    selectOption  = $('<option></option>');
                    selectOption.attr('value', 'true');

                    if (fieldValue === 'true') {
                        selectOption.attr('selected', true);
                    }
                    selectOption.html('Yes');
                    selectInput.append(selectOption);

                    control.append(selectInput);
                    return buildField();
                case 'acl':
                    aclAjax();
                    return buildField();
                case 'dict':
                    dictionaryAjax();
                    return buildField();
                case 'origin':
                    backendsAjax();
                    return buildField();
                case 'cond-req':
                    conditionsAjax('request');
                    return buildField();
                case 'cond-resp':
                    conditionsAjax('response');
                    return buildField();
                case 'cond-cache':
                    conditionsAjax('cache');
                    return buildField();
                case 'domain':
                    domainsAjax();
                    return buildField();
                case 'iso3166-1a2':
                    countriesAjax();
                    return buildField();
                default:
                    textInput.attr('type', 'text');
                    control.append(textInput);
                    return buildField();
            }
        }

        $('#modly_all_modules_btn').on('click', function () {
            $.when(
                $.ajax({
                    type:"GET",
                    url: config.getAllModulesUrl,
                    showLoader: true
                })
            ).done(function (response) {
                popup(allModuleOptions);
                let updateBtn = $('<button class="action-secondary" id="fastly_manifest_btn" type="button" data-role="action"></button>');
                updateBtn.append($('<span>Refresh</span>'));
                $('.modal-header').find(".page-actions-buttons").append(updateBtn);
                $('.upload-button span').text('Save');

                if (response.modules.length > 0) {
                    let allModules = response.modules;
                    let html = '';
                    let nameCell;
                    let descriptionCell;
                    let checkboxCell;
                    let checkbox;
                    let checkboxDiv;
                    let checkboxLabel;
                    $.each(allModules, function (index, module) {
                        html = $('<tr></tr>');
                        nameCell = $('<td></td>');
                        descriptionCell = $('<td></td>');
                        checkboxCell = $('<td></td>');
                        checkbox = $('<input class="admin__control-checkbox module" type="checkbox">');
                        checkboxDiv = $('<div class="admin__field-option"></div>');
                        checkboxLabel = $('<label class="admin__field-label" for="'+module.manifest_id+'"></label>');
                        if (module.manifest_status === '1') {
                            html.addClass('highlighted');
                        }
                        html.append(nameCell);
                        html.append(descriptionCell);
                        html.append(checkboxCell);
                        nameCell.text(module.manifest_name);
                        descriptionCell.append(module.manifest_description);
                        checkboxCell.append(checkboxDiv);
                        checkboxDiv.append(checkbox);
                        checkboxDiv.append(checkboxLabel);
                        checkboxDiv.attr('title', module.manifest_id);
                        checkbox.attr('name', module.manifest_id);
                        checkbox.attr('id', module.manifest_id);
                        if (module.manifest_status === '1') {
                            checkbox.attr('checked', 'checked');
                        }
                        checkboxLabel.attr('for', module.manifest_id);
                        $('#modly-all-modules-table > tbody').append(html);
                    });
                } else {
                    showErrorMessage($.mage.__(response.msg)); // TODO: change this to showWarningMessage
                }
            });
        });

        $('body').on('click', '#fastly_manifest_btn', function () {
            $.when(
                $.ajax({
                    type: "GET",
                    url: config.createManifestsUrl,
                    showLoader: true
                })
            ).done(function (response) {
                if (response.status === false) {
                    showErrorMessage($.mage.__('Could not update the list modules.'));
                    return;
                }

                $.when(
                    $.ajax({
                        type:"GET",
                        url: config.getAllModulesUrl,
                        showLoader: true
                    })
                ).done(function (response) {
                    if (response.status === false) {
                        showErrorMessage($.mage.__(response.msg));
                    }

                    if (response.modules.length > 0) {
                        let allModules = response.modules;
                        let html = '';
                        $.each(allModules, function (index, module) {
                            html += '<tr';
                            if (module.manifest_status === '1') {
                                html += ' class="highlighted"';
                            }
                            html +=  '><td><b>' + module.manifest_name + '</b></td>';
                            html += '<td>' + module.manifest_description + '</td>';
                            html += '<td><div class="admin__field-option" title="'+module.manifest_id+'"><input name="'+module.manifest_id+'" class="admin__control-checkbox module" type="checkbox" id="'+module.manifest_id+'"';
                            if (module.manifest_status === '1') {
                                html += ' checked';
                            }
                            html += '>';
                            html += '<label class="admin__field-label" for="'+module.manifest_id+'"></label></div></td></tr>';
                        });
                        $('#modly-all-modules-table > tbody').html(html);
                    }
                });

                showSuccessMessage('The list of modules has been successfully updated.');

            }).fail(function () {
                showErrorMessage('An error occurred while processing your request. Please try again.');
            });
        });

        $('body').on('click', 'button.fastly-edit-active-modules-icon', function () {
            let module_id = $(this).data('module-id');
            let properties = [];
            let fieldset = $('<div class="admin__fieldset form-list modly-group">');
            let message = '';
            let title = '';

            // call getModule data function to retrieve a specific module's data
            getModuleData(module_id).done(function (response) {
                if (response.status !== false) {
                    module = response.module;
                }

                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                }).done(function (checkService) {
                    active_version = checkService.active_version;
                    let next_version = checkService.next_version;
                    let service_name = checkService.service.name;

                    let isGroup = false;

                    if (module.manifest_id === module_id) {
                        message = '<div class="message">' + module.manifest_description + '</div>';
                        title = module.manifest_name;
                        let moduleValues = module.manifest_values;
                        let parsedValues = '';
                        groupName = '';

                        if (moduleValues) {
                            parsedValues = JSON.parse(moduleValues);
                        }
                        if (module.manifest_properties !== '') {
                            properties = JSON.parse(module.manifest_properties);
                            $.each(properties, function (key, property) {
                                if (property.type === 'group') {
                                    groupName = property.name;
                                    isGroup = true;
                                    if (parsedValues === '') {
                                        parsedValues = [{"":""}];
                                    }

                                    //field = ''; // todo delete or revert
                                    // for each group object property ie. {"responses": [{...}]}}
                                    $.each(parsedValues, function (moduleIndex, groupData) {
                                        // for each group data property
                                        $.each(groupData, function (groupIndex, groupValues) {
                                            // open the modly-group class element
                                            //field += '<div class="admin__fieldset form-list modly-group">';
                                            // for each manifest defined config property, render fields with group values
                                            $.each(property.properties, function (propertyIndex, propertyValues) {
                                                fieldset.append(renderFields(propertyValues, groupValues, active_version));
                                            });
                                            fieldset.append('<div class="admin__field field"><div class="admin__field-label"></div><div class="admin__field-control"><button class="action remove-group-button" type="button" data-role="action"><span>Remove group</span></button></div></div>');
                                            fieldset.append('<div class="admin__field field"><div class="admin__field-label"></div><div class="admin__field-control"><hr></div></div></div>');
                                        });
                                    });
                                } else {
                                    fieldset.append(renderFields(property, parsedValues[0], active_version));
                                    console.log(renderFields(property, parsedValues[0], active_version));
                                }
                            });
                        }
                    }

                    if (module != null && module_id != null) {
                        popup(activeModuleOptions);
                        setServiceLabel(active_version, next_version, service_name);
                        $('#modly-active-module-options > .messages').prepend(message);
                        let question = $('.question');
                        // if (isGroup === false) {
                        //     field += '</div>';
                        // }
                        question.append(fieldset);
                        $('.modal-title').html(title);
                        $('#module-id').val(module_id);
                        let groupBtn = '<button class="action-secondary group-button" type="button" data-role="action"><span>Add group</span></button>';
                        if (isGroup === true) {
                            $('.modal-header').find(".page-actions-buttons").append(groupBtn);
                            question.find('.modly-group:first').find('.remove-group-button').closest('.field').hide();
                            $('.group-button').unbind('click').on('click', function () {
                                question.find('.modly-group:last').clone().appendTo('.question');
                                question.find('.modly-group:last').find('.modly-field').val('');
                                question.find('.modly-group:last').find('.remove-group-button').closest('.field').show();
                                $('.remove-group-button').unbind('click').on('click', function () {
                                    $(this).closest('.modly-group').remove();
                                });
                            });
                            $('.remove-group-button').unbind('click').on('click', function () {
                                $(this).closest('.modly-group').remove();
                            });
                        }
                    }
                });
            });
        });
    }
});