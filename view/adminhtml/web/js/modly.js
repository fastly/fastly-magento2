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

        let allModuleOptions= {
            title: jQuery.mage.__(' '),
                content: function () {
                return document.getElementById('modly-all-modules-template').textContent;
            },
            actionOk: function () {
                saveActiveModules();
            }
        };

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
                data: {'active_version': active_version}
            });
        }

        // Queries Fastly API to retrieve ACLs
        function listAcls(active_version)
        {
            return $.ajax({
                type: "GET",
                url: config.getAcls,
                showLoader: true,
                data: {'active_version': active_version}
            });
        }

        function getBackends(active_version)
        {
            return $.ajax({
                type: "GET",
                url: config.fetchBackendsUrl,
                showLoader: true,
                data: {'active_version': active_version}
            });
        }

        function getAllConditions(active_version)
        {
            return $.ajax({
                type: "POST",
                url: config.getAllConditionsUrl,
                data: {'active_version': active_version},
                showLoader: true
            });
        },

        function getAllDomains(active_version)
        {
            return $.ajax({
                type: "POST",
                url: config.getAllDomainsUrl,
                data: {'active_version': active_version},
                showLoader: true
            });
        },

        function getCountries(active_version) {
            return $.ajax({
                type: "POST",
                url: config.getCountriesUrl,
                data: {
                    'active_version': active_version
                },
                showLoader: true
            });
        }

        function renderFields(property, value, active_version)
        {
            let html = '<div class="admin__field field';
            if (property.required === true) {
                html+= ' _required';
            }
            html +=   '">';
            html += '<label for="' + property.name + '" class="admin__field-label">';
            html += '<span>' + property.label + '</span>';
            html += '</label>';
            html += '<div class="admin__field-control">';
            let description = '';
            if (property.description) {
                description = property.description;
            }
            let fieldName = property.name;
            let fieldValue = '';
            if (property.default) {
                fieldValue = property.default;
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

            if (property.type === 'string' || property.type === 'rtime' || property.type === 'path' || property.type === 'url' || !property.type) {
                html += '<input type="text" name="' + property.name + '" required="required" id="' + property.name + '" value="' + fieldValue + '" class="admin__control-text modly-field">';
            } else if (property.type === 'integer' || property.type === 'float') {
                html += '<input type="number" name="' + property.name + '" required="required" id="' + property.name + '" value="' + fieldValue + '" class="admin__control-text modly-field">';
            } else if (property.type === 'time') {
                html += '<input type="time" name="' + property.name + '" required="required" id="' + property.name + '" value="' + fieldValue + '" class="admin__control-text modly-field">';
            } else if (property.type === 'longstring') {
                html += '<textarea rows="10" name="' + property.name + '" required="required" id="' + property.name + '" class="admin__control-text modly-field">';
                html += fieldValue + '</textarea>';
            } else if (property.type === 'select') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                html += '<option value="">--Please Select--</option>';
                $.each(property.options, function (key, option) {
                    html += '<option value="' + key + '"';
                    if (key === fieldValue) {
                        html += ' selected';
                    }
                    html += '>' + option + '</option>';
                });
                html += '</select>';
            } else if (property.type === 'boolean') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                html += '<option value="false"';
                if (fieldValue === 'false') {
                    html += 'selected';
                }
                html += '>No</option>';
                html += '<option value="true"';
                if (fieldValue === 'true') {
                    html += 'selected';
                }
                html += '>Yes</option>';
                html += '</select>';
            } else if (property.type === 'acl') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                listAcls(active_version).done(function (response) {
                    if (response.status !== false) {
                        let acls = response.acls;
                        let options = '<option value="">--Please Select--</option>';
                        $.each(acls, function (index, acl) {
                            options += '<option value="' + acl.name + '"';
                            if (acl.name === fieldValue) {
                                options += ' selected';
                            }
                            options += '>' + acl.name + '</option>';
                        });
                        $('#' + property.name).append(options);
                    }
                });
                html += '</select>';
            } else if (property.type === 'dict') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                listDictionaries(active_version).done(function (response) {
                    if (response.status !== false) {
                        let dictionaries = response.dictionaries;
                        let options = '<option value="">--Please Select--</option>';
                        $.each(dictionaries, function (index, dictionary) {
                            options += '<option value="' + dictionary.name + '"';
                            if (dictionary.name === fieldValue) {
                                options += ' selected';
                            }
                            options += '>' + dictionary.name + '</option>';
                        });
                        $('#' + property.name).append(options);
                    }
                });
                html += '</select>';
            } else if (property.type === 'origin') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                getBackends(active_version).done(function (response) {
                    if (response.status !== false) {
                        let backends = response.backends;
                        let options = '<option value="">--Please Select--</option>';
                        $.each(backends, function (index, backend) {
                            options += '<option value="' + backend.name + '"';
                            if (backend.name === fieldValue) {
                                options += ' selected';
                            }
                            options += '>' + backend.name + '</option>';
                        });
                        $('#' + property.name).append(options);
                    }
                });
                html += '</select>';
            } else if (property.type === 'cond-req') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                getAllConditions(active_version).done(function (response) {
                    if (response.status !== false) {
                        let conditions = response.conditions;
                        let options = '<option value="">--Please Select--</option>';
                        $.each(conditions, function (index, condition) {
                            if (condition.type === 'REQUEST') {
                                options += '<option value="' + condition.name + '"';
                                if (condition.name === fieldValue) {
                                    options += ' selected';
                                }
                                options += '>' + condition.name + '</option>';
                            }
                        });
                        $('#'+ property.name).append(options);
                    }
                });
                html += '</select>';
            } else if (property.type === 'cond-resp') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                getAllConditions(active_version).done(function (response) {
                    if (response.status !== false) {
                        let conditions = response.conditions;
                        let options = '<option value="">--Please Select--</option>';
                        $.each(conditions, function (index, condition) {
                            if (condition.type === 'RESPONSE') {
                                options += '<option value="' + condition.name + '"';
                                if (condition.name === fieldValue) {
                                    options += ' selected';
                                }
                                options += '>' + condition.name + '</option>';
                            }
                        });
                        $('#'+ property.name).append(options);
                    }
                });
                html += '</select>';
            } else if (property.type === 'cond-cache') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                getAllConditions(active_version).done(function (response) {
                    if (response.status !== false) {
                        let conditions = response.conditions;
                        let options = '<option value="">--Please Select--</option>';
                        $.each(conditions, function (index, condition) {
                            if (condition.type === 'CACHE') {
                                options += '<option value="' + condition.name + '"';
                                if (condition.name === fieldValue) {
                                    options += ' selected';
                                }
                                options += '>' + condition.name + '</option>';
                            }
                        });
                        $('#'+ property.name).append(options);
                    }
                });
                html += '</select>';
            } else if (property.type === 'domain') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                getAllDomains(active_version).done(function (response) {
                    if (response.status !== false) {
                        let domains = response.domains;
                        let options = '<option value="">--Please Select--</option>';
                        $.each(domains, function (index, domain) {
                            options += '<option value="' + domain.name + '"';
                            if (domain.name === fieldValue) {
                                options += ' selected';
                            }
                            options += '>' + domain.name + '</option>';
                        });
                        $('#' + property.name).append(options);
                    }
                });
                html += '</select>';
            } else if (property.type === 'iso3166-1a2') {
                html += '<select name="' + property.name + '" id="' + property.name + '" class="admin__control-text modly-field">';
                getCountries(active_version).done(function (response) {
                    if (response.status !== false) {
                        let countries = response.countries;
                        let options = '';
                        $.each(countries, function (index, country) {
                            options += '<option value="' + country.value + '"';
                            if (country.value === fieldValue) {
                                options += ' selected';
                            }
                            options += '>' + country.label + '</option>';
                        });
                        $('#'+ property.name).append(options);
                    }
                });
                html += '</select>';
            } else {
                html = ''
            }
            html += '<div class="admin__field-note">' + description + '</div>';
            html += '</div></div>';
            return html;
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
                let updateBtn = '<button class="action-secondary" id="fastly_manifest_btn" type="button" data-role="action"><span>Refresh</span></button>';
                $('.modal-header').find(".page-actions-buttons").append(updateBtn);
                $('.upload-button span').text('Save');

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
                    $('#modly-all-modules-table > tbody').append(html);
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
            let field = '<div class="admin__fieldset form-list modly-group">';
            let message = '';
            let title = '';

            // call getModule data function to retrieve a specific module's data
            getModuleData(module_id).done(function (response) {
                if (response.status !== false) {
                    module = response.module;
                }

                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl
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

                                    field = '';
                                    // for each group object property ie. {"responses": [{...}]}}
                                    $.each(parsedValues, function (moduleIndex, groupData) {
                                        // for each group data property
                                        $.each(groupData, function (groupIndex, groupValues) {
                                            // open the modly-group class element
                                            field += '<div class="admin__fieldset form-list modly-group">';
                                            // for each manifest defined config property, render fields with group values
                                            $.each(property.properties, function (propertyIndex, propertyValues) {
                                                field += renderFields(propertyValues, groupValues, active_version);
                                            });
                                            field += '<div class="admin__field field"><div class="admin__field-label"></div><div class="admin__field-control"><button class="action remove-group-button" type="button" data-role="action"><span>Remove group</span></button></div></div>';
                                            field += '<div class="admin__field field"><div class="admin__field-label"></div><div class="admin__field-control"><hr></div></div>';
                                            field += '</div>';
                                        });

                                    });
                                } else {
                                    field += renderFields(property, parsedValues[0], active_version);
                                }
                            });
                        }
                    }

                    if (module != null && module_id != null) {
                        popup(activeModuleOptions);
                        setServiceLabel(active_version, next_version, service_name);
                        $('#modly-active-module-options > .messages').prepend(message);
                        let question = $('.question');
                        if (isGroup === false) {
                            field += '</div>';
                        }
                        question.append(field);
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