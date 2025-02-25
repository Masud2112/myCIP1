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


Espo.define('Crm:Views.CampaignLogRecord.Fields.Data', 'Views.Fields.Base', function (Dep) {

    return Dep.extend({

        listTemplate: 'crm:campaign-log-record.fields.data.detail',

    	getValueForDisplay: function () {
    		var action = this.model.get('action');

    		switch (action) {
    			case 'Sent':
                case 'Opened':
                    if (this.model.get('objectId') && this.model.get('objectType') && this.model.get('objectName')) {
                        return '<a href="#'+this.model.get('objectType')+'/view/'+this.model.get('objectId')+'">'+this.model.get('objectName')+'</a>';
                    }
                    return this.model.get('stringData') || '';
    			case 'Clicked':
                    if (this.model.get('objectId') && this.model.get('objectType') && this.model.get('objectName')) {
                        return '<a href="#'+this.model.get('objectType')+'/view/'+this.model.get('objectId')+'">'+this.model.get('objectName')+'</a>';
                    }
    				return '<span>' + (this.model.get('stringData') || '') + '</span>';
                case 'Opted Out':
                    return '<span class="text-danger">' + this.model.get('stringData') + '</span>';
                case 'Bounced':
                    var emailAddress = this.model.get('stringData');
                    var type = this.model.get('stringAdditionalData');
                    if (type == 'Hard') {
                        return '<s class="text-danger">' + emailAddress + '</s>';
                    } else {
                        return '<s class="">' + emailAddress + '</s>';
                    }
    		}
    		return '';
    	}

    });
});


