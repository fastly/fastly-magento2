define([
    "jquery",
    "handlebars",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    "showWarningMessage",
    'mage/translate'
], function ($, Handlebars, setServiceLabel, overlay, resetAllMessages, showErrorMessage, showSuccessMessage, showWarningMessage) {
    return function (config, serviceStatus, isAlreadyConfigured) {
        let successAllModulesBtnMsg = $('#fastly-success-all-modules-button-msg');
        let errorAllModulesBtnMsg = $('#fastly-error-all-modules-button-msg');
        let warningAllModulesBtnMsg = $('#fastly-warning-all-modules-button-msg');
        let module_field;
        let moduleModal;
        let aclModal;
        let dictionaryModal;
        let aclNewButton = false;
        let dictNewButton = false;
        let aclSelectId;
        let dictSelectId;

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

        let aclModalProperties = {
            title: jQuery.mage.__('Create ACL container'),
            content: function () {
                return document.getElementById('fastly-acl-container-template').textContent;
            },
            actionOk: function () {
                createAcl();
            }
        };

        let dictionaryModalProperties = {
            title: jQuery.mage.__('Create Dictionary'),
            content: function () {
                return document.getElementById('fastly-dictionary-container-template').textContent;
            },
            actionOk: function () {
                createDictionary();
            }
        };

        let aclAddNewButtonProperties = {
            text: 'New ACL',
            class: 'action-secondary add-new-button',
            dataRole: 'action',
            clickButton: function () {
                overlay(aclModalProperties);
                aclModal = modal;
            }
        };

        let dictionaryAddNewButtonProperties = {
            text: 'New Dictionary',
            class: 'action-secondary add-new-button',
            dataRole: 'action',
            clickButton: function () {
                overlay(dictionaryModalProperties);
                dictionaryModal = modal;
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

        function createButton(properties)
        {
            let addNewButton = document.createElement('button');
            let text = document.createTextNode(properties.text);
            addNewButton.setAttribute('class', properties.class);
            addNewButton.setAttribute('type', 'button');
            addNewButton.setAttribute('data-role', properties.dataRole);
            addNewButton.appendChild(text);
            addNewButton.on('click', function () {
                return properties.clickButton();
            });
            $('.modal-header').find(".page-actions-buttons").append(addNewButton)
        }

        function saveModuleConfig()
        {
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
                    if (value === "true" || value === "false") {
                        value = (value === "true");
                    }
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
                                moduleModal.modal('closeModal');
                                resetAllMessages();
                                successAllModulesBtnMsg.text($.mage.__('The '+ moduleId +' module has been successfully uploaded to the Fastly service.')).show();
                                module_field.closest('tr').find('.col-date').text(response.last_uploaded);
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

        function uploadModuleConfig(moduleId, parsedVcl, active_version)
        {
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

        function parseVcl(fieldData)
        {
            let moduleVcl = JSON.parse(module.manifest_vcl);
            let templates = [];
            let result = '';

            Handlebars.registerHelper('replace', (inp, re, repl) => inp.replace(new RegExp(re, 'g'), repl));
            Handlebars.registerHelper('ifEq', function (a, b, options) {
                if (a === b) {
                    return options.fn(this);
                } else {
                    return options.inverse(this);
                }
            });
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
                    let priority = 45;
                    if (value.priority) {
                        priority = value.priority;
                    }
                    templates.push({
                        "type": value.type,
                        "priority": priority,
                        "snippet": result
                    });
                });
            });
            return templates;
        }

        function saveActiveModules()
        {
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

        function getActiveModules(loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.getActiveModulesUrl,
                showLoader: loaderVisibility,
                beforeSend: function (xhr) {
                    $('.loading-modules').show();
                }
            });
        }

        function processActiveModules(modules)
        {
            if (modules.length > 0) {
                $('#modly-active-modules-list').html('');
                $('.no-modules').hide();
                $.each(modules, function (index, module) {
                    let moduleRow = $('<tr></tr>');
                    let moduleCell = $('<td></td>');
                    let moduleSpan = $('<span class="active-modules" type="text"></span>');
                    let moduleNote = $('<p class="note"></p>');
                    let moduleNoteSpan = $('<span></span>');
                    let moduleActionCell = $('<td class="col-actions"></td>');
                    let moduleDateCell = $('<td class="col-date"></td>');
                    let moduleActionButton = $('<button title="Edit module" type="button">');

                    moduleRow.attr('id', 'fastly_' + index);
                    moduleRow.append(moduleCell);
                    moduleRow.append(moduleDateCell);
                    moduleRow.append(moduleActionCell);
                    moduleCell.append(moduleSpan);
                    moduleCell.append(moduleNote);
                    moduleSpan.attr('data-moduleId', index);
                    moduleSpan.attr('id', 'module_' + index);
                    moduleSpan.attr('disabled', 'disabled');
                    moduleSpan.text(module.manifest_name);
                    moduleSpan.wrapInner('<b></b>');
                    moduleNote.append(moduleNoteSpan);
                    moduleNoteSpan.text(module.manifest_description);
                    if (module.last_uploaded !== null) {
                        moduleDateCell.text(module.last_uploaded);
                    } else {
                        moduleDateCell.text('Not uploaded');
                    }
                    moduleActionCell.append(moduleActionButton);
                    moduleActionButton.attr('data-module-id', module.manifest_id);
                    moduleActionButton.attr('id', 'fastly-edit-active-modules' + index);
                    moduleActionButton.addClass("action-delete fastly-edit-active-modules-icon");

                    $('#modly-active-modules-list').append(moduleRow);
                });
            } else {
                $('.no-modules').show();
                $('#modly-active-modules-list').html('');
            }
        }

        function getModuleData(module_id)
        {
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

        function getCountries(active_version)
        {
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
                        aclSelectId = property.name;
                        if (!aclNewButton) {
                            aclNewButton = true;
                        }
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
                        dictSelectId = property.name;
                        if (!dictNewButton) {
                            dictNewButton = true;
                        }
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

        function listModules(modules)
        {
            let allModules = modules;
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
                nameCell.wrapInner('<b></b>');
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
        }

        function createAcl()
        {
            let activate_flag = (($("#fastly_activate_vcl").val() === 'on') ? 'true' : false);
            let acl_name = $('#acl_name').val();
            let select = document.getElementById(aclSelectId);

            $.ajax({
                type: "POST",
                url: config.createAcl,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_flag,
                    'acl_name': acl_name
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        active_version = response['active_version'];
                        let option = document.createElement('option');
                        let text = document.createTextNode(acl_name);
                        option.setAttribute('value', acl_name);
                        option.setAttribute('selected', 'selected');
                        option.appendChild(text);
                        select.append(option);
                        aclModal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return showErrorMessage('An error occurred while processing your request. Please try again.');
                }
            });
        }

        function createDictionary()
        {
            let activate_flag = (($("#fastly_activate_vcl").val() === 'on') ? 'true' : false);
            let dictionary_name = $('#dictionary_name').val();
            let select = document.getElementById(dictSelectId);

            $.ajax({
                type: "POST",
                url: config.createDictionary,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_flag,
                    'dictionary_name': dictionary_name
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        active_version = response['active_version'];
                        let option = document.createElement('option');
                        let text = document.createTextNode(dictionary_name);
                        option.setAttribute('value', dictionary_name);
                        option.setAttribute('selected', 'selected');
                        option.appendChild(text);
                        select.append(option);
                        dictionaryModal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return showErrorMessage('An error occurred while processing your request. Please try again.');
                }
            });
        }

        $('#modly_all_modules_btn').on('click', function () {
            $.when(
                $.ajax({
                    type:"GET",
                    url: config.getAllModulesUrl,
                    showLoader: true
                })
            ).done(function (response) {
                overlay(allModuleOptions);
                let updateBtn = $('<button class="action-secondary" id="fastly_manifest_btn" type="button" data-role="action"></button>');
                updateBtn.append($('<span>Refresh</span>'));
                $('.modal-header').find(".page-actions-buttons").append(updateBtn);
                $('.upload-button span').text('Save');

                if (response.modules.length > 0) {
                    listModules(response.modules);
                } else {
                    showWarningMessage($.mage.__(response.msg));
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
                        $('#modly-all-modules-table > tbody').html('');
                        listModules(response.modules);
                    }
                    showSuccessMessage('The list of modules has been successfully updated.');
                });
            }).fail(function () {
                showErrorMessage('An error occurred while processing your request. Please try again.');
            });
        });

        $('body').on('click', 'button.fastly-edit-active-modules-icon', function () {
            let module_id = $(this).data('module-id');
            module_field = $(this);
            let properties = [];
            let message = $('<div class="message"></div>');
            let title = '';
            let fieldset = $('<div class="admin__fieldset form-list modly-group"></div>');
            let groups = [];

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
                        message.text(module.manifest_description);
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
                                    // for each group object property ie. {"responses": [{...}]}}
                                    $.each(parsedValues, function (moduleIndex, groupData) {
                                        // for each group data property
                                        $.each(groupData, function (groupIndex, groupValues) {
                                            // for each manifest defined config property, render fields with group values
                                            let fieldset = $('<div class="admin__fieldset form-list modly-group"></div>');
                                            $.each(property.properties, function (propertyIndex, propertyValues) {
                                                fieldset.append(renderFields(propertyValues, groupValues, active_version));
                                            });
                                            fieldset.append('<div class="admin__field field"><div class="admin__field-label"></div><div class="admin__field-control"><button class="action remove-group-button" type="button" data-role="action"><span>Remove group</span></button></div></div>');
                                            fieldset.append('<div class="admin__field field"><div class="admin__field-label"></div><div class="admin__field-control"><hr></div></div></div>');
                                            groups.push(fieldset);
                                        });
                                    });
                                } else {
                                    fieldset.append(renderFields(property, parsedValues[0], active_version));
                                }
                            });
                        }
                    }

                    if (module != null && module_id != null) {
                        overlay(activeModuleOptions);
                        moduleModal = modal;
                        setServiceLabel(active_version, next_version, service_name);
                        $('.module-messages').prepend(message);
                        let question = $('.question');
                        $('.modal-title').html(title);
                        $('#module-id').val(module_id);

                        if (aclNewButton === true) {
                            createButton(aclAddNewButtonProperties);
                        }

                        if (dictNewButton === true) {
                            createButton(dictionaryAddNewButtonProperties);
                        }

                        if (isGroup === true) {
                            let groupBtn = '<button class="action-secondary group-button" type="button" data-role="action"><span>Add group</span></button>';
                            $.each(groups, function (grIndex, grData) {
                                question.append(grData);
                            });
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
                        } else {
                            question.append(fieldset)
                        }
                    }
                });
            });
        });
    }
});