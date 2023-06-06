define(
    [
        'jquery',
        'mage/template'
    ],
    function ($, template) {
        'use strict';
        return {
            renderEdgeAcls: function (data, withButton = true) {
                let button = (withButton) ? '<button class="action-delete export-list-icon acl-items-btn" title="Show Items" type="button">' : '';
                let html = this._renderTitle('Edge ACLs');
                $.each(data, function (index, acl) {
                    html += template(
                        `<div class="admin__field field">
                            <div class="admin__field-control export-field">
                                <div class="admin__field-option admin__control-table">
                                    <input class="admin__control-checkbox export-checkbox export-acl" type="checkbox" name="edge_acls[<%- aclName %>]" id="<%- aclId %>" checked/>
                                    <label class="admin__field-label" for="<%- aclId %>"><%- aclName %></label>
                                    ${button}
                                </div>
                            </div>
                        </div>`,
                        {
                            aclName: acl.name,
                            aclId: acl.id,
                        }
                    );
                });
                html += this._renderEmptyLabel(data, 'There are no Edge ACLs');
                return html;
            },

            renderEdgeDisctionaries: function (data, withButton = true) {
                let button = (withButton) ? '<button class="action-delete export-list-icon dictionary-items-btn" title="Show Items" type="button">' : '';
                let html = this._renderTitle('Edge Dictionaries');
                $.each(data, function (index, dictionary) {
                    let checked = (dictionary.name.split('_', 1)[0] === 'magentomodule') ? '' : 'checked';
                    html += template(
                        `<div class="admin__field field">
                            <div class="admin__field-control export-field">
                                <div class="admin__field-option admin__control-table">
                                    <input class="admin__control-checkbox export-checkbox export-dictionary" type="checkbox" name="edge_dictionaries[<%- dictionaryName %>]" id="<%- dictionaryId %>" ${checked}/>
                                    <label class="admin__field-label" for="<%- dictionaryId %>"><%- dictionaryName %></label>
                                    ${button}
                                </div>
                            </div>
                        </div>`,
                        {
                            dictionaryName: dictionary.name,
                            dictionaryId: dictionary.id,
                        }
                    );
                });
                html += this._renderEmptyLabel(data, 'There are no Edge Dictionaries');
                return html;
            },

            renderCustomSnippets: function (data) {
                let html = this._renderTitle('Custom Snippets');
                $.each(data, function (index, content) {
                    html += template(
                        `<div class="admin__field field">
                            <div class="admin__field-control export-field">
                                <div class="admin__field-option admin__control-table">
                                    <input class="admin__control-checkbox export-checkbox export-custom-snippet" type="checkbox" id="<%- index %>" name="custom_snippets[<%- index %>]" checked/>
                                    <label class="admin__field-label" for="<%- index %>"><%- index %></label>
                                    <div class="admin__field field">
                                        <div class="admin__field-note export-note"><%- content %></div>
                                    </div>
                                </div>
                            </div>
                        </div>`,
                        {
                            index,
                            content,
                        }
                    );
                });
                html += this._renderEmptyLabel(data, 'There are no Custom Snippets');
                return html;
            },

            renderActiveEdgeModules: function (data) {
                let html = this._renderTitle('Active Edge Modules');
                $.each(data, function (index, edgeModule) {
                    html += template(
                        `<div class="admin__field field">
                            <div class="admin__field-control export-field">
                                <div class="admin__field-option admin__control-table">
                                    <input class="admin__control-checkbox export-checkbox export-active-modules" type="checkbox" name="active_modules[<%- manifestId %>]" id="<%- manifestId %>" checked/>
                                    <label class="admin__field-label" for="<%- manifestId %>"><%- manifestName %></label>
                                </div>
                            </div>
                        </div>
                        <div class="admin__field field">
                            <div class="admin__field-note export-note" id="<%- index %>"><%- manifestDescription %></div>
                        </div>`,
                        {
                            index,
                            manifestId: edgeModule.manifest_id,
                            manifestName: edgeModule.manifest_name,
                            manifestDescription: edgeModule.manifest_description,
                        }
                    );
                    for (const key in edgeModule.values) {
                        if (edgeModule.values.hasOwnProperty(key)) {
                            html += template(
                                `<div class="admin__field field">
                                    <div class="admin__field-note export-note"><strong><%- key %>: </strong><%- value %></div>
                                </div>`,
                                {
                                    key,
                                    value: edgeModule.values[key],
                                }
                            );
                        }
                    }
                });
                html += this._renderEmptyLabel(data, 'There are no Active Modules');
                return html;
            },

            renderAdminPathTimeout: function (data) {
                let html = this._renderTitle('Admin Path Timeout');
                html += template(
                    `<div class="admin__field field">
                        <div class="admin__field-note export-note"><%- data %>s</div>
                    </div>`,
                    {
                        data,
                    }
                );
                return html;
            },

            renderDetails: function (id, data) {
                let html = template(
                    `<div class="admin__field field export-dictionary-items <%- id %>">`,
                    {
                        id,
                    }
                );
                $.each(data, function (index, text) {
                    html += template(
                        `<div class="admin__field field">
                            <div class="admin__field-note export-note"><%- text %></div>
                        </div>`,
                        {
                            text,
                        }
                    );
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
                return template(
                    `<div class="admin__field field">
                        <div class="admin__field-control export-field">
                            <div class="admin__field-option admin__control-table">
                                <label class="admin__field-label">
                                    <b><%- title %></b>
                                </label>
                            </div>
                        </div>
                    </div>`,
                    {
                        title,
                    }
                );
            },

            _renderEmptyLabel: function (data, text) {
                if (data === undefined || data.length === 0) {
                    return this._renderLabel(text);
                }
                return '';
            },

            _renderLabel: function (label) {
                return template(
                    `<div class="admin__field field">
                        <div class="admin__field-control export-field">
                            <div class="admin__field-option admin__control-table">
                                <label class="admin__field-label"><%- label %></label>
                            </div>
                        </div>
                    </div>`,
                    {
                        label,
                    }
                );
            },

            stringifyDictionaryDetail: function (index, item) {
                return template(
                    '<%- itemKey %> (<%- itemValue %>)',
                    {
                        itemKey: item.item_key,
                        itemValue: item.item_value,
                    }
                );
            },

            stringifyAclDetail: function (index, item) {
                return template(
                    `<% if (negated) { %>!<% } %>
                    <%- ip %>
                    <% if (subnet) { %>/<%- subnet %><% } %>
                    <% if (comment) { %> (<%- comment %>)<% } %>
                    - <%- createdAt %>`,
                    {
                        negated: item.negated === '1',
                        ip: item.ip,
                        subnet: item.subnet,
                        comment: item.comment,
                        createdAt: new Date(item.created_at).toUTCString(),
                    }
                );
            }
        };
    }
);
