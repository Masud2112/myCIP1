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

Espo.define('views/fields/enum', ['views/fields/base', 'lib!Selectize'], function (Dep) {

    return Dep.extend({

        type: 'enum',

        listTemplate: 'fields/enum/list',

        detailTemplate: 'fields/enum/detail',

        editTemplate: 'fields/enum/edit',

        searchTemplate: 'fields/enum/search',

        translatedOptions: null,

        searchTypeList: ['anyOf', 'noneOf', 'isEmpty', 'isNotEmpty'],

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.translatedOptions = this.translatedOptions;
            var value = this.model.get(this.name);
            if (
                value !== null
                &&
                value !== ''
                ||
                value === '' && (value in (this.translatedOptions || {}) && (this.translatedOptions || {})[value] !== '')
            ) {
                data.isNotEmpty = true;
            }
            return data;
        },

        setup: function () {
            if (!this.params.options) {
                var methodName = 'get' + Espo.Utils.upperCaseFirst(this.name) + 'Options';
                if (typeof this.model[methodName] == 'function') {
                    this.params.options = this.model[methodName].call(this.model);
                }
            }

            this.setupOptions();

            if ('translatedOptions' in this.options) {
                this.translatedOptions = this.options.translatedOptions;
            }

            if ('translatedOptions' in this.params) {
                this.translatedOptions = this.params.translatedOptions;
            }

            if (this.params.translation) {
                var data = this.getLanguage().data;
                var arr = this.params.translation.split('.');
                var pointer = this.getLanguage().data;
                arr.forEach(function (key) {
                    if (key in pointer) {
                        pointer = pointer[key];
                        t = pointer;
                    }
                }, this);

                this.translatedOptions = null;
                var translatedOptions = {};
                if (this.params.options) {
                    this.params.options.forEach(function (o) {
                        if (typeof t === 'object' && o in t) {
                            translatedOptions[o] = t[o];
                        } else {
                            translatedOptions[o] = o;
                        }
                    }, this);
                    this.translatedOptions = translatedOptions;
                }
            }

            if (this.translatedOptions === null) {
                this.translatedOptions = this.getLanguage().translate(this.name, 'options', this.model.name) || {};
                if (this.translatedOptions === this.name) {
                    this.translatedOptions = null;
                }
            }

            if (this.params.isSorted && this.translatedOptions) {
                this.params.options = Espo.Utils.clone(this.params.options);
                this.params.options = this.params.options.sort(function (v1, v2) {
                     return (this.translatedOptions[v1] || v1).localeCompare(this.translatedOptions[v2] || v2);
                }.bind(this));
            }

            if (this.options.customOptionList) {
                this.setOptionList(this.options.customOptionList);
            }
        },

        setupOptions: function () {
        },

        setOptionList: function (optionList) {
            if (!this.originalOptionList) {
                this.originalOptionList = this.params.options;
            }
            this.params.options = Espo.Utils.clone(optionList);

            if (this.mode == 'edit') {
                if (this.isRendered()) {
                    this.reRender();
                    if (~(this.params.options || []).indexOf(this.model.get(this.name))) {
                        this.trigger('change');
                    }
                } else {
                    this.once('after:render', function () {
                        if (~(this.params.options || []).indexOf(this.model.get(this.name))) {
                            this.trigger('change');
                        }
                    }, this);
                }
            }
        },

        resetOptionList: function () {
            if (this.originalOptionList) {
                this.params.options = Espo.Utils.clone(this.originalOptionList);
            }

            if (this.mode == 'edit') {
                if (this.isRendered()) {
                    this.reRender();
                }
            }
        },

        setupSearch: function () {
            this.events = _.extend({
                'change select.search-type': function (e) {
                    this.handleSearchType($(e.currentTarget).val());
                },
            }, this.events || {});
        },

        handleSearchType: function (type) {
            var $inputContainer = this.$el.find('div.input-container');

            if (~['anyOf', 'noneOf'].indexOf(type)) {
                $inputContainer.removeClass('hidden');
            } else {
                $inputContainer.addClass('hidden');
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode == 'search') {

                var $element = this.$element = this.$el.find('[name="' + this.name + '"]');

                var type = this.$el.find('select.search-type').val();
                this.handleSearchType(type);

                var valueList = this.getSearchParamsData().valueList || this.searchParams.value || [];
                this.$element.val(valueList.join(':,:'));

                var data = [];
                (this.params.options || []).forEach(function (value) {
                    var label = this.getLanguage().translateOption(value, this.name, this.scope);
                    if (this.translatedOptions) {
                        if (value in this.translatedOptions) {
                            label = this.translatedOptions[value];
                        }
                    }
                    data.push({
                        value: value,
                        label: label
                    });
                }, this);

                this.$element.selectize({
                    options: data,
                    delimiter: ':,:',
                    labelField: 'label',
                    valueField: 'value',
                    highlight: false,
                    searchField: ['label'],
                    plugins: ['remove_button'],
                    score: function (search) {
                        var score = this.getScoreFunction(search);
                        search = search.toLowerCase();
                        return function (item) {
                            if (item.label.toLowerCase().indexOf(search) === 0) {
                                return score(item);
                            }
                            return 0;
                        };
                    }
                });
            }
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (!this.model.get(this.name)) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
                    return true;
                }
            }
        },

        fetch: function () {
            var value = this.$el.find('[name="' + this.name + '"]').val();
            var data = {};
            data[this.name] = value;
            return data;
        },

        parseItemForSearch: function (item) {
            return item;
        },

        fetchSearch: function () {
            var type = this.$el.find('[name="'+this.name+'-type"]').val();

            var list = this.$element.val().split(':,:');
            if (list.length === 1 && list[0] == '') {
                list = [];
            }

            list.forEach(function (item, i) {
                list[i] = this.parseItemForSearch(item);
            }, this);

            if (type === 'anyOf') {
                if (list.length === 0) {
                    return {
                        data: {
                            type: 'anyOf',
                            valueList: list
                        }
                    };
                }
                return {
                    type: 'in',
                    value: list,
                    data: {
                        type: 'anyOf',
                        valueList: list
                    }
                };
            } else if (type === 'noneOf') {
                if (list.length === 0) {
                    return {
                        data: {
                            type: 'noneOf',
                            valueList: list
                        }
                    };
                }
                return {
                    type: 'or',
                    value: [
                        {
                            type: 'isNull',
                            attribute: this.name
                        },
                        {
                            type: 'notIn',
                            value: list,
                            attribute: this.name
                        }
                    ],
                    data: {
                        type: 'noneOf',
                        valueList: list
                    }
                };
            } else if (type === 'isEmpty') {
                return {
                    type: 'or',
                    value: [
                        {
                            type: 'isNull',
                            attribute: this.name
                        },
                        {
                            type: 'equals',
                            value: '',
                            attribute: this.name
                        }
                    ],
                    data: {
                        type: 'isEmpty'
                    }
                };
            } else if (type === 'isNotEmpty') {
                return {
                    type: 'and',
                    value: [
                        {
                            type: 'isNotNull',
                            attribute: this.name
                        },
                        {
                            type: 'notEquals',
                            value: '',
                            attribute: this.name
                        }
                    ],
                    data: {
                        type: 'isNotEmpty'
                    }
                };
            }
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || 'anyOf';
        }

    });
});

