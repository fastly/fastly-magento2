define(
    [
        'uiComponent',
        'mage/template',
        'prototype'
    ],
    function (Component, mageTemplate) {
        return Component.extend({
            initialize: function (config) {
                var htmlId = config.htmlId;
                var arrayRowX = 'arrayRow' + htmlId;
                var addToEndBtnX = 'addToEndBtn' + htmlId;
                var html = config.html;
                var templateValuesOrig = config.templateValues;
                var arrayRows = config.arrayRows;

                window[arrayRowX] = {

                    // define row prototypeJS template
                    template: mageTemplate(html),

                    add: function (rowData, insertAfterId) {
                        // generate default template data
                        var templateValues;

                        // Prepare template values
                        if (rowData) {
                            templateValues = rowData;
                        } else {
                            // Handles adding of new empty field
                            var d = new Date();

                            templateValues = {
                                option_extra_attrs : {},
                                _id : '_' + d.getTime() + '_' + d.getMilliseconds()
                            };
                            for (var prop in templateValuesOrig) {
                                if (templateValuesOrig.hasOwnProperty(prop)) {
                                    var columnName = templateValuesOrig[prop];
                                    templateValues[columnName] = '';
                                }
                            }
                        }

                        // Insert new row after specified row or at the bottom
                        if (insertAfterId) {
                            Element.insert($(insertAfterId), {after: this.template(templateValues)});
                        } else {
                            Element.insert($('addRow' + htmlId), {bottom: this.template(templateValues)});
                        }

                        // Fill controls with data
                        if (rowData) {
                            var rowInputElementNames = Object.keys(rowData.column_values);
                            for (var key in rowInputElementNames) {
                                if (rowInputElementNames.hasOwnProperty(key)) {
                                    if ($(rowInputElementNames[key])) {
                                        $(rowInputElementNames[key]).value = rowData.column_values[rowInputElementNames[key]];
                                    }
                                }
                            }
                        }

                        // Add event for {addAfterBtn} button
                        if (config.isAddAfter != 0) {
                            Event.observe('addAfterBtn' + templateValues._id, 'click', this.add.bind(this, false, templateValues._id));
                        }
                    },

                    del: function (rowId) {
                        $(rowId).remove();
                    }
                };

                // Bind add action to "Add" button in last row
                Event.observe(
                    addToEndBtnX,
                    'click',
                    window[arrayRowX].add.bind(window[arrayRowX], false, false)
                );

                // Add database values on load
                for (var key in arrayRows) {
                    if (arrayRows.hasOwnProperty(key)) {
                        window[arrayRowX].add(arrayRows[key]);
                    }
                }

                // Toggle the grid availability, if element is disabled (depending on scope)
                if (config.disabled != 0) {
                    toggleValueElements({checked: true}, $('grid' + htmlId).parentNode);
                }
            }
        });
    }
);