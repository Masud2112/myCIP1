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

Espo.define('MultiCollection', 'Collection', function (Collection) {
    var MultiCollection = Collection.extend({

        /**
         * @prop {Object} seeds Hash off model classes.
         */
        seeds: null,

        initialize: function (models, options) {
            options = options || {};

            this.sortBy = options.sortBy || this.sortBy;
            this.asc = ('asc' in options) ? options.asc : this.asc;

            this.data = {};

            Backbone.Collection.prototype.initialize.call(this);
        },

        parse: function (resp, options) {
            this.total = resp.total;
            return resp.list.map(function (attributes) {
                var a = _.clone(attributes);
                delete a['_scope'];
                return new this.seeds[attributes._scope](a, options);
            }.bind(this));
        },

    });

    return MultiCollection;

});
