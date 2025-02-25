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

Espo.define('Views.Stream.Notes.EmailReceived', 'Views.Stream.Note', function (Dep) {

    return Dep.extend({

        template: 'stream.notes.email-received',

        isRemovable: true,

        isSystemAvatar: true,

        data: function () {
            return _.extend({
                emailId: this.emailId,
                emailName: this.emailName,
                hasPost: this.hasPost,
                hasAttachments: this.hasAttachments
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            var data = this.model.get('data') || {};

            this.emailId = data.emailId;
            this.emailName = data.emailName;

            if (
                this.parentModel
                &&
                (this.model.get('parentType') == this.parentModel.name && this.model.get('parentId') == this.parentModel.id)
            ) {
                if (this.model.get('post')) {
                    this.createField('post', null, null, 'Stream.Fields.Post');
                    this.hasPost = true;
                }
                if ((this.model.get('attachmentsIds') || []).length) {
                    this.createField('attachments', 'attachmentMultiple', {}, 'Stream.Fields.AttachmentMultiple');
                    this.hasAttachments = true;
                }
            }

            this.messageData['email'] = '<a href="#Email/view/' + data.emailId + '">' + data.emailName + '</a>';

            this.messageName = 'emailReceived';

            if (data.isInitial) {
                this.messageName += 'Initial';
            }

            if (data.personEntityId) {
                this.messageName += 'From';
                this.messageData['from'] = '<a href="#'+data.personEntityType+'/view/' + data.personEntityId + '">' + data.personEntityName + '</a>';
            }

            if (this.model.get('parentType') === data.personEntityType && this.model.get('parentId') == data.personEntityId) {
                this.isThis = true;
            }

            if (this.isThis) {
                this.messageName += 'This';
            }

            this.createMessage();
        },

    });
});

