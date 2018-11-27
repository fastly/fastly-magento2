define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    "Magento_Ui/js/modal/confirm",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, showSuccessMessage, confirm) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        /* Dictionary button messages */
        let successDictionaryBtnMsg = $('#fastly-success-dictionary-button-msg');
        let errorDictionaryBtnMsg = $('#fastly-error-dictionary-button-msg');

        let itemsHtml;
        let dictionaryItems;
        let dictionary_id;
        let dictionaries;

        let active_version = serviceStatus.active_version;

        /**
         * Dictionary container modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let dictionaryContainerOptions = {
            title: jQuery.mage.__('Create dictionary container'),
                content: function () {
                return document.getElementById('fastly-dictionary-container-template').textContent;
            },
            actionOk: function () {
                createDictionary(active_version);
            }
        };

        /**
         * Dictionary container items modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let dictionaryItemOptions = {
            title: jQuery.mage.__('Dictionary items'),
                content: function () {
                return document.getElementById('fastly-dictionary-item-template').textContent;
            },
            actionOk: function () {
            }
        };

        /**
         * Dictionary container delete modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let dictionaryDeleteContainerOptions = {
            title: jQuery.mage.__('Delete dictionary containers'),
                content: function () {
                return document.getElementById('fastly-delete-dictionary-container-template').textContent;
            },
            actionOk: function () {
                deleteDictionary(active_version);
            }
        };

        /**
         * Trigger Dictionary container list
         */
        listDictionaries(active_version, false).done(function (response) {
            $('.loading-dictionaries').hide();
            if (response.status !== false) {
                if (response.status !== false) {
                    if (response.dictionaries.length > 0) {
                        dictionaries = response.dictionaries;
                        processDictionaries(response.dictionaries);
                    } else {
                        $('.no-dictionaries').show();
                    }
                }
            }
        }).fail(function () {
            return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
        });

        /**
         * Create Dictionary container
         */
        function createDictionary()
        {
            let activate_vcl = false;

            if ($('#fastly_activate_vcl').is(':checked')) {
                activate_vcl = true;
            }

            $.ajax({
                type: "POST",
                url: config.createDictionary,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_vcl,
                    'dictionary_name': $('#dictionary_name').val()
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        successDictionaryBtnMsg.text($.mage.__('Dictionary is successfully created.')).show();
                        active_version = response.active_version;
                        // Fetch dictionaries
                        listDictionaries(active_version, false).done(function (list) {
                            $('.loading-dictionaries').hide();
                            if (list.status !== false) {
                                if (list.status !== false) {
                                    if (list.dictionaries.length > 0) {
                                        dictionaries = list.dictionaries;
                                        processDictionaries(list.dictionaries);
                                        $('.no-dictionaries').hide();
                                    } else {
                                        $('.no-dictionaries').show();
                                    }
                                }
                            }
                        }).fail(function () {
                            return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                        });
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        /**
         * Process and display Dictionary container
         *
         * @param dictionaries
         */
        function processDictionaries(dictionaries)
        {
            let html = '';
            $.each(dictionaries, function (index, dictionary) {
                html += "<tr id='fastly_dict_" + index + "'>";
                html += "<td><input data-dictionaryId='"+ dictionary.id + "' id='dict_" + index + "' value='"+ dictionary.name +"' disabled='disabled' class='input-text' type='text'></td>";
                html += "<td class='col-actions'>" +
                    "<button class='action-delete fastly-edit-dictionary-icon' data-dictionary-id='" + dictionary.id + "' data-dictionary-name='" + dictionary.name + "' id='fastly-edit-dictionary_"+ index + "' title='Edit dictionary' type='button'>" +
                    "<button class='action-delete fastly-delete-dictionary-icon' data-dictionary-id='" + dictionary.name + "' id='fastly-delete-dictionary_"+ index + "' title='Delete dictionary' type='button'>" +
                    "</td></tr>";
            });
            if (html !== '') {
                $('.no-dictionaries').hide();
            }
            $('#fastly-dictionaries-list').html(html);
        }

        /**
         * Queries Fastly API to retrieve the list of Dictionary containers
         *
         * @param active_version
         * @param loaderVisibility
         * @returns {*}
         */
        function listDictionaries(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.getDictionaries,
                showLoader: loaderVisibility,
                data: {'active_version': active_version},
                beforeSend: function () {
                    $('.loading-dictionaries').show();
                }
            });
        }

        /**
         * Save Dictionary container item
         *
         * @param dictionary_id
         * @param item_key
         * @param item_value
         * @param loaderVisibility
         * @returns {*}
         */
        function saveEdgeDictionaryItem(dictionary_id, item_key, item_value, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.createDictionaryItem,
                showLoader: loaderVisibility,
                data: {'dictionary_id': dictionary_id, 'item_key': item_key, 'item_value': item_value},
                beforeSend: function () {
                    resetAllMessages();
                }
            });
        }

        /**
         * Delete Dictionary container
         */
        function deleteDictionary()
        {
            let activate_vcl = false;

            if ($('#fastly_activate_vcl').is(':checked')) {
                activate_vcl = true;
            }

            let del_dictionary = $("input[name='dictionary']").val();

            $.ajax({
                type: "POST",
                url: config.deleteDictionary,
                data: {
                    'active_version': active_version,
                    'activate_flag': activate_vcl,
                    'dictionary': del_dictionary
                },
                showLoader: true,
                success: function (response) {
                    if (response.status === true) {
                        successDictionaryBtnMsg.text($.mage.__('Dictionary successfully deleted.')).show();
                        active_version = response.active_version;
                        // Fetch dictionaries
                        listDictionaries(active_version, false).done(function (dictResp) {
                            $('.loading-dictionaries').hide();
                            if (dictResp.status !== false) {
                                if (dictResp.dictionaries.length > 0) {
                                    dictionaries = dictResp.dictionaries;
                                    processDictionaries(dictResp.dictionaries);
                                } else {
                                    $('#fastly-dictionaries-list').html('');
                                    $('.no-dictionaries').show();
                                }
                            }
                        }).fail(function () {
                            return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                        });
                        modal.modal('closeModal');
                    } else {
                        resetAllMessages();
                        showErrorMessage(response.msg);
                    }
                },
                error: function () {
                    return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                }
            });
        }

        /**
         * Delete Dictionary container item
         *
         * @param dictionary_id
         * @param item_key
         * @param loaderVisibility
         * @returns {*}
         */
        function deleteEdgeDictionaryItem(dictionary_id, item_key, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.deleteDictionaryItem,
                showLoader: loaderVisibility,
                data: {'dictionary_id': dictionary_id, 'item_key': item_key},
                beforeSend: function () {
                    resetAllMessages();
                }
            });
        }

        /**
         * Dictionary container add button on click event
         */
        $('#add-dictionary-container-button').on('click', function () {
            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                })
            ).done(function (service) {
                if (service.status === false) {
                    return errorDictionaryBtnMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                overlay(dictionaryContainerOptions);
                setServiceLabel(active_version, next_version, service_name);
                $('.upload-button span').text('Create');

            }).fail(function () {
                return errorDictionaryBtnMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Dictionary container edit button on click event
         */
        $('body').on('click', 'button.fastly-edit-dictionary-icon', function () {
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (checkService) {
                active_version = checkService.active_version;
                let next_version = checkService.next_version;
                let service_name = checkService.service.name;
                setServiceLabel(active_version, next_version, service_name);
            });

            dictionary_id = $(this).data('dictionary-id');
            let dictionary_name = $(this).data('dictionary-name');
            // Handle Dictionaries
            if (dictionaries != null && dictionary_id != null) {
                $.ajax({
                    type: "POST",
                    url: config.getDictionaryItems,
                    showLoader: true,
                    data: {'dictionary_id': dictionary_id}
                }).done(function (response) {
                    if (response.status !== false) {
                        dictionaryItems = response.dictionaryItems;
                        itemsHtml = '';
                        if (response.dictionaryItems.length > 0) {
                            $.each(response.dictionaryItems, function (index, item) {
                                itemsHtml += '<tr><td>' +
                                    '<input name="key" value="'+ item.item_key +'" class="input-text admin__control-text dictionary-items-field" type="text" disabled></td>' +
                                    '<td><input name="value" data-type="dictionary" value="'+ item.item_value +'" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                                    '<td class="col-actions">' +
                                    '<button class="action-delete fastly-save-action save_dictionary_item" title="Save" type="button"><span>Save</span></button>' +
                                    '<button class="action-delete remove_dictionary_item"  title="Delete" type="button"><span>Delete</span></button>' +
                                    '</td></tr>';
                            });
                        } else {
                            itemsHtml += '<tr><td>' +
                                '<input name="key" value="" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                                '<td><input name="value" data-type="dictionary" value="" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                                '<td class="col-actions">' +
                                '<button class="action-delete fastly-save-action save_dictionary_item" title="Save" type="button"><span>Save</span></button>' +
                                '</td></tr>';
                        }
                    }

                    overlay(dictionaryItemOptions);
                    $('.modal-title').text($.mage.__('"'+ dictionary_name +'" dictionary container items'));
                    $('.upload-button').remove();

                    $('#dictionary-items-table > tbody').html(itemsHtml);
                });
            }
        });

        /**
         * Dictionary container add item button on click event
         */
        $('body').on('click', '#add-dictionary-item', function () {
            $('#dictionary-items-table > tbody').append('<tr><td><input name="key" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                '<td><input name="value" data-type="dictionary" required="required" class="input-text admin__control-text dictionary-items-field" type="text"></td>' +
                '<td class="col-actions">' +
                '<button class="action-delete fastly-save-action save_dictionary_item" title="Save" type="button"><span>Save</span></button>' +
                '<button class="action-delete remove_dictionary_item"  title="Delete" type="button" hidden><span>Delete</span></button>' +
                '</td></tr>');
        });

        /**
         * Dictionary container save item on click event
         */
        $('body').on('click', '.save_dictionary_item', function () {
            let keyField = $(this).closest('tr').find("input[name='key']");
            let valueField = $(this).closest('tr').find("input[name='value']");
            let item_key = keyField.val();
            let item_value = valueField.val();
            let errors = false;

            if (item_key === '') {
                errors = true;
                keyField.css('border-color', '#e22626');
            } else {
                keyField.css('border-color', '#878787');
            }

            if (item_value === '') {
                errors = true;
                valueField.css('border-color', '#e22626');
            } else {
                valueField.css('border-color', '#878787');
            }

            if (errors) {
                resetAllMessages();
                return showErrorMessage($.mage.__('Please enter all required fields.'));
            }

            let self = this;

            saveEdgeDictionaryItem(dictionary_id, item_key, item_value, true).done(function (response) {
                if (response.status === true) {
                    $(self).closest('tr').find("input[name='key']").prop('disabled', true);
                    $(self).closest('tr').find(".remove_dictionary_item").show();
                    showSuccessMessage($.mage.__('Dictionary item is successfully saved.'));
                } else {
                    showErrorMessage(response.msg);
                }
            }).fail(function () {
                showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
            });
        });

        /**
         * Dictionary container remove item on click event
         */
        $('body').on('click', '.remove_dictionary_item', function () {
            let item_key = $(this).closest('tr').find("input[name='key']").val();
            let self = this;
            confirm({
                title: 'Delete Dictionary Item',
                content: 'Are you sure you want to delete the "'+item_key+'" dictionary item?',
                actions: {
                    confirm: function () {
                        deleteEdgeDictionaryItem(dictionary_id, item_key, true).done(function (response) {
                            if (response.status === true) {
                                $(self).closest('tr').remove();
                                showSuccessMessage($.mage.__('Dictionary item is successfully deleted.'));
                            }
                        }).fail(function () {
                            showErrorMessage($.mage.__('An error occurred while processing your request. Please try again.'));
                        });
                    },
                    cancel: function () {}
                }
            });
        });

        /**
         * Dictionary container delete button on click event
         */
        $('body').on('click', 'button.fastly-delete-dictionary-icon', function () {
            dictionary_id = $(this).data('dictionary-id');

            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true
            }).done(function (checkService) {
                active_version = checkService.active_version;
                let next_version = checkService.next_version;
                let service_name = checkService.service.name;

                if (dictionaries != null && dictionary_id != null) {
                    let dictionaryHtml = '<input name="dictionary" value="' + dictionary_id + '" class="input-text admin__control-text dictionary-field" type="hidden" disabled>';
                    overlay(dictionaryDeleteContainerOptions);
                    setServiceLabel(active_version, next_version, service_name);
                    let containerWarning = $('#fastly-container-warning');
                    $('.modal-title').text($.mage.__('Delete "'+ dictionary_id +'" Dictionary container'));
                    containerWarning.text($.mage.__('You are about to delete the "' + dictionary_id + '" Dictionary container.'));
                    containerWarning.show();
                    if (dictionaryHtml !== '') {
                        $('#delete-dictionary-container').html(dictionaryHtml);
                    }
                }
            });
        });

    }
});