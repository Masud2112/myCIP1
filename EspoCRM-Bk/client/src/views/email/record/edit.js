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

Espo.define('views/email/record/edit', ['views/record/edit', 'views/email/record/detail'], function (Dep, Detail) {

    return Dep.extend({

        init: function () {
            Dep.prototype.init.call(this);
            Detail.prototype.layoutNameConfigure.call(this);
        },

        handleAttachmentField: function () {
            if ((this.model.get('attachmentsIds') || []).length == 0 && !this.isNew) {
                this.hideField('attachments');
            } else {
                this.showField('attachments');
            }
        },

        handleCcField: function () {
            this.showField('cc');
        },

        handleBccField: function () {
            this.showField('bcc');
        },

        afterRender: function () {
        	Dep.prototype.afterRender.call(this);

            if (this.model.get('status') === 'Draft') {
                this.setFieldReadOnly('dateSent');
            }

            this.handleAttachmentField();
            this.listenTo(this.model, 'change:attachmentsIds', function () {
                this.handleAttachmentField();
            }, this);
            this.handleCcField();
            this.listenTo(this.model, 'change:cc', function () {
                this.handleCcField();
            }, this);
            this.handleBccField();
            this.listenTo(this.model, 'change:bcc', function () {
                this.handleBccField();
            }, this);
        },

    });
});

