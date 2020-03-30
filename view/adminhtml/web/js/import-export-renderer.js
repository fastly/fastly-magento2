define(
    [
        'jquery'
    ],
    function ($) {
        'use strict';
        return {
            renderEdgeAcls: function (data, withButton = true) {
                let button = (withButton) ? `<button class="action-delete export-list-icon acl-items-btn" title="Show Items" type="button">` : '';
                let html = this._renderTitle('Edge ACLs');
                $.each(data, function (index, acl) {
                    html += `<div class="admin__field field">
                        <div class="admin__field-control export-field">
                            <div class="admin__field-option admin__control-table">
                                <input class="admin__control-checkbox export-checkbox export-acl" type="checkbox" name="edge_acls[${acl.name}]" id="${acl.id}" checked/>
                                <label class="admin__field-label" for="${acl.id}">${acl.name}</label>
                                ${button}
                            </div>
                        </div>
                    </div>`;
                });
                html += this._renderEmptyLabel(data, 'There are no Edge ACLs');
                return html;
            },

            renderEdgeDisctionaries: function (data, withButton = true) {
                let button = (withButton) ? `<button class="action-delete export-list-icon dictionary-items-btn" title="Show Items" type="button">` : '';
                let html = this._renderTitle('Edge Dictionaries');
                $.each(data, function (index, dictionary) {
                    let checked = (dictionary.name.split('_', 1)[0] === 'magentomodule') ? '' : 'checked';
                    html += `<div class="admin__field field">
                        <div class="admin__field-control export-field">
                            <div class="admin__field-option admin__control-table">
                                <input class="admin__control-checkbox export-checkbox export-dictionary" type="checkbox" name="edge_dictionaries[${dictionary.name}]" id="${dictionary.id}" ${checked}/>
                                <label class="admin__field-label" for="${dictionary.id}">${dictionary.name}</label>
                                ${button}
                            </div>
                        </div>
                    </div>`;
                });
                html += this._renderEmptyLabel(data, 'There are no Edge Dictionaries');
                return html;
            },

            renderCustomSnippets: function (data) {
                let html = this._renderTitle('Custom Snippets');
                $.each(data, function (index, content) {
                    html += `<div class="admin__field field">
                        <div class="admin__field-control export-field">
                            <div class="admin__field-option admin__control-table">
                                <input class="admin__control-checkbox export-checkbox export-custom-snippet" type="checkbox" id="${index}" name="custom_snippets[${index}]" checked/>
                                <label class="admin__field-label" for="${index}">${index}</label>
                                <div class="admin__field field">
                                    <div class="admin__field-note export-note">${content}</div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
                html += this._renderEmptyLabel(data, 'There are no Custom Snippets');
                return html;
            },

            renderActiveEdgeModules: function (data) {
                let html = this._renderTitle('Active Edge Modules');
                $.each(data, function (index, edgeModule) {
                    html += `<div class="admin__field field">
                        <div class="admin__field-control export-field">
                            <div class="admin__field-option admin__control-table">
                                <input class="admin__control-checkbox export-checkbox export-active-modules" type="checkbox" name="active_modules[${edgeModule.manifest_id}]" id="${edgeModule.manifest_id}" checked/>
                                <label class="admin__field-label" for="${edgeModule.manifest_id}">${edgeModule.manifest_name}</label>
                            </div>
                        </div>
                    </div>
                    <div class="admin__field field">
                        <div class="admin__field-note export-note" id="${index}">${edgeModule.manifest_description}</div>
                    </div>`;
                    for (const key in edgeModule.values) {
                        if (edgeModule.values.hasOwnProperty(key)) {
                            html += `<div class="admin__field field">
                                <div class="admin__field-note export-note"><strong>${key}: </strong>${edgeModule.values[key]}</div>
                            </div>`;
                        }
                    }
                });
                html += this._renderEmptyLabel(data, 'There are no Active Modules');
                return html;
            },

            renderAdminPathTimeout: function (data) {
                let html = this._renderTitle('Admin Path Timeout');
                html += `<div class="admin__field field">
                    <div class="admin__field-note export-note">${data}s</div>
                </div>`;
                return html;
            },

            renderDetails: function (id, data) {
                let html = `<div class="admin__field field export-dictionary-items ${id}">`;
                $.each(data, function (index, text) {
                    html += `<div class="admin__field field">
                        <div class="admin__field-note export-note">${text}</div>
                    </div>`;
                });
                if (data === undefined || data.length === 0) {
                    html += `<div class="admin__field field">
                        <div class="admin__field-note export-note">no items</div>
                    </div>`;
                }
                html += `</div>`;
                return html;
            },

            _renderTitle: function (title) {
                return this._renderLabel(`<b>${title}</b>`);
            },
            _renderEmptyLabel: function (data, text) {
                if (data === undefined || data.length === 0) {
                    return this._renderLabel(text);
                }
                return '';
            },
            _renderLabel: function (label) {
                return `<div class="admin__field field">
                    <div class="admin__field-control export-field">
                        <div class="admin__field-option admin__control-table">
                            <label class="admin__field-label">${label}</label>
                        </div>
                    </div>
                </div>`;
            },

            stringifyDictionaryDetail: function (index, item) {
                return `${item.item_key} (${item.item_value})`
            },

            stringifyAclDetail: function (index, item) {
                let ip = (item.negated === '1') ? '!' : '';
                ip += item.ip
                ip += (item.subnet) ? `/${item.subnet}` : '';
                let comment = (item.comment !== '') ? ` (${item.comment})` : '';
                let created_at = new Date(item.created_at);
                return `${ip}${comment} ${created_at.toUTCString()}`;
            }
        };
    }
);
