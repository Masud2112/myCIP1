/************************************************************************
 * This file is part of Simply I Do.
 *
 * Simply I Do - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://simplyido.com
 *
 * Simply I Do is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Simply I Do is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Simply I Do. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Simply I Do" word.
 ************************************************************************/

Espo.define('views/record/list-expanded', 'views/record/list', function (Dep) {

    return Dep.extend({

        template: 'record/list-expanded',

        checkboxes: false,

        selectable: false,

        rowActionsView: false,

        _internalLayoutType: 'list-row-expanded',

        presentationType: 'expanded',

        pagination: false,

        header: false,

        _internalLayout: null,

        checkedList: null,

        listContainerEl: '.list > ul',

        _loadListLayout: function (callback) {
            var type = this.type + 'Expanded';

            this.layoutLoadCallbackList.push(callback);

            if (this.layoutIsBeingLoaded) return;

            this.layoutIsBeingLoaded = true;
            this._helper.layoutManager.get(this.collection.name, type, function (listLayout) {
                this.layoutLoadCallbackList.forEach(function (c) {
                    c(listLayout)
                    this.layoutLoadCallbackList = [];
                    this.layoutIsBeingLoaded = false;
                }, this);
            }.bind(this));
        },

        _convertLayout: function (listLayout, model) {
            model = model || this.collection.model.prototype;

            var layout = {
                rows: [],
                right: false,
            };

            for (var i in listLayout.rows) {
                var row = listLayout.rows[i];
                var layoutRow = [];
                for (var j in row) {

                    var e = row[j];
                    var type = e.type || model.getFieldType(e.name) || 'base';

                    var item = {
                        name: e.name,
                        view: e.view || model.getFieldParam(e.name, 'view') || this.getFieldManager().getViewName(type),
                        options: {
                            defs: {
                                name: e.name,
                                params: e.params || {}
                            },
                            mode: 'list'
                        }
                    };
                    if (e.link) {
                        item.options.mode = 'listLink';
                    }
                    layoutRow.push(item);
                }
                layout.rows.push(layoutRow);
            }

            if ('right' in listLayout) {
                if (listLayout.right != false) {
                    layout.right = {
                        name: listLayout.right.name || 'right',
                        view: listLayout.right.view,
                        options: {
                            defs: {
                                params: {
                                    width: listLayout.right.width || '7%'
                                }
                            }
                        }
                    };
                }
            } else {
                if (this.rowActionsView) {
                    layout.right = this.getRowActionsDefs();
                }
            }
            return layout;
        },

        getRowSelector: function (id) {
            return 'li[data-id="' + id + '"]';
        },

        getItemEl: function (model, item) {
            return this.options.el + ' li[data-id="' + model.id + '"] .cell[data-name="' + item.name + '"]';
        },

        getRowContainerHtml: function (id) {
            return '<li data-id="'+id+'" class="list-group-item list-row"></li>';
        },

        prepareInternalLayout: function (internalLayout, model) {
            var rows = internalLayout.rows || [];
            rows.forEach(function (row) {
                row.forEach(function (col) {
                    col.el = this.getItemEl(model, col);
                }, this);
            }, this);
            if (internalLayout.right) {
                internalLayout.right.el = this.getItemEl(model, internalLayout.right);
            }
        },

    });
});


