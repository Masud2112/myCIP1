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

Espo.define('views/package/modals/add-field', 'views/modal', function (Dep) {

    return Dep.extend({

        template: 'package/modals/add-field',

        events: {
            'click a[data-action="addField"]': function (e) {
                this.trigger('add-field', $(e.currentTarget).data().name);
            }
        },

        data: function () {
            var dataList = [];
            var d = [];
            this.fieldList.forEach(function (field, i) {
                if (i % 4 === 0) {
                    dataList.push([]);
                }
                dataList[dataList.length -1].push(field);
            }, this);

            return {
                dataList: dataList,
                scope: this.scope
            };
        },

        setup: function () {
            this.header = this.translate('Add Field');

            var scope = this.scope = this.options.scope;

            var fields = this.getMetadata().get('entityDefs.' + scope + '.fields') || {};

            var fieldList = [];

            Object.keys(fields).forEach(function (field) {
                var d = fields[field];
                if (field in this.options.ignoreFieldList) return;
                if (d.disabled) return;

                fieldList.push(field);
            }, this);

            this.fieldList = this.getLanguage().sortFieldList(scope, fieldList);
        }

    });
});

