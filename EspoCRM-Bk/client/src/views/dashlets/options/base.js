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

Espo.define('views/dashlets/options/base', ['views/modal', 'views/record/detail', 'model', 'view-record-helper'], function (Dep, Detail, Model, ViewRecordHelper) {

    var self;

    return Dep.extend({

        name: null,

        template: 'dashlets/options/base',

        cssName: 'options-modal',

        className: 'dialog dialog-record',

        fieldsMode: 'edit',

        data: function () {
            return {
                options: this.optionsData,
            };
        },

        buttonList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary'
            },
            {
                name: 'cancel',
                label: 'Cancel'
            }
        ],

        getDetailLayout: function () {
            var layout = this.getMetadata().get(['dashlets', this.name, 'options', 'layout']);
            if (layout) {
                return layout;
            }
            layout = [{rows: []}];
            var i = 0;
            var a = [];
            for (var field in this.fields) {

                if (!(i % 2)) {
                    a = [];
                    layout[0].rows.push(a);
                }
                a.push({name: field});
                i++;
            }
            return layout;
        },

        init: function () {
            Dep.prototype.init.call(this);

            this.fields = Espo.Utils.cloneDeep(this.options.fields);

            this.fieldList = Object.keys(this.fields);
            this.optionsData = this.options.optionsData;
        },

        setup: function (dialog) {
            this.id = 'dashlet-options';

            this.recordHelper = new ViewRecordHelper();

            var self = this;
            var model = this.model = new Model();
            model.name = 'DashletOptions';
            model.defs = {
                fields: this.fields
            };
            model.set(this.optionsData);

            model.dashletName = this.name;

            this.createView('record', 'views/record/detail-middle', {
                model: model,
                recordHelper: this.recordHelper,
                _layout: {
                    type: 'record',
                    layout: Detail.prototype.convertDetailLayout.call(this, this.getDetailLayout())
                },
                el: this.options.el + ' .record',
                layoutData: {
                    model: model,
                    columnCount: 2,
                }
            });

            this.header = this.getLanguage().translate('Dashlet Options') + ': ' + this.getLanguage().translate(this.name, 'dashlets');
        },

        fetchAttributes: function () {
            var attributes = {};
            this.fieldList.forEach(function (field) {
                var fieldView = this.getView('record').getView(field);
                _.extend(attributes, fieldView.fetch());
            }, this);

            this.model.set(attributes, {silent: true});

            var valid = true;
            this.fieldList.forEach(function (field) {
                var fieldView = this.getView('record').getView(field);
                valid = !fieldView.validate() && valid;
            }, this);

            if (!valid) {
                this.notify('Not Valid', 'error');
                return null;
            }
            return attributes;
        },

        actionSave: function (dialog) {
            var attributes = this.fetchAttributes();

            if (attributes == null) {
                return;
            }

            this.trigger('save', attributes);
        },

        getFieldViews: function (withHidden) {
            if (this.hasView('record')) {
                return this.getView('record').getFieldViews(withHidden) || {};
            }
            return {};
        },

        getFieldView: function () {
            return (this.getFieldViews(true) || {})[name] || null;
        },

        hideField: function (name, locked) {
            this.recordHelper.setFieldStateParam(name, 'hidden', true);
            if (locked) {
                this.recordHelper.setFieldStateParam(name, 'hiddenLocked', true);
            }

            var processHtml = function () {
                var fieldView = this.getFieldView(name);

                if (fieldView) {
                    var $field = fieldView.$el;
                    var $cell = $field.closest('.cell[data-name="' + name + '"]');
                    var $label = $cell.find('label.control-label[data-name="' + name + '"]');

                    $field.addClass('hidden');
                    $label.addClass('hidden');
                    $cell.addClass('hidden-cell');
                } else {
                    this.$el.find('.cell[data-name="' + name + '"]').addClass('hidden-cell');
                    this.$el.find('.field[data-name="' + name + '"]').addClass('hidden');
                    this.$el.find('label.control-label[data-name="' + name + '"]').addClass('hidden');
                }
            }.bind(this);
            if (this.isRendered()) {
                processHtml();
            } else {
                this.once('after:render', function () {
                    processHtml();
                }, this);
            }

            var view = this.getFieldView(name);
            if (view) {
                view.setDisabled(locked);
            }
        },

        showField: function (name) {
            if (this.recordHelper.getFieldStateParam(name, 'hiddenLocked')) {
                return;
            }
            this.recordHelper.setFieldStateParam(name, 'hidden', false);

            var processHtml = function () {
                var fieldView = this.getFieldView(name);

                if (fieldView) {
                    var $field = fieldView.$el;
                    var $cell = $field.closest('.cell[data-name="' + name + '"]');
                    var $label = $cell.find('label.control-label[data-name="' + name + '"]');

                    $field.removeClass('hidden');
                    $label.removeClass('hidden');
                    $cell.removeClass('hidden-cell');
                } else {
                    this.$el.find('.cell[data-name="' + name + '"]').removeClass('hidden-cell');
                    this.$el.find('.field[data-name="' + name + '"]').removeClass('hidden');
                    this.$el.find('label.control-label[data-name="' + name + '"]').removeClass('hidden');
                }
            }.bind(this);

            if (this.isRendered()) {
                processHtml();
            } else {
                this.once('after:render', function () {
                    processHtml();
                }, this);
            }

            var view = this.getFieldView(name);
            if (view) {
                if (!view.disabledLocked) {
                    view.setNotDisabled();
                }
            }
        }
    });
});


