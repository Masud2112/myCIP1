<?php
/************************************************************************
 * This file is part of Simply I Do.
 *
 * Simply I Do - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

return array (
    'database' => array (
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'port' => '',
        'charset' => 'utf8',
        'dbname' => '',
        'user' => '',
        'password' => '',
    ),
    'useCache' => true,
    'recordsPerPage' => 20,
    'recordsPerPageSmall' => 5,
    'applicationName' => 'Simply I Do',
    'version' => '4.8.2',
    'timeZone' => 'UTC',
    'dateFormat' => 'MM/DD/YYYY',
    'timeFormat' => 'hh:mm a',
    'weekStart' => 0,
    'thousandSeparator' => ',',
    'decimalMark' => '.',
    'exportDelimiter' => ';',
    'currencyList' => ['USD'],
    'defaultCurrency' => 'USD',
    'baseCurrency' => 'USD',
    'currencyRates' => [],
    'outboundEmailIsShared' => true,
    'outboundEmailFromName' => 'Simply I Do',
    'outboundEmailFromAddress' => '',
    'smtpServer' => '',
    'smtpPort' => 25,
    'smtpAuth' => true,
    'smtpSecurity' => '',
    'smtpUsername' => '',
    'smtpPassword' => '',
    'languageList' => [
        'en_GB',
        'en_US',
        'es_MX',
        'cs_CZ',
        'da_DK',
        'de_DE',
        'es_ES',
        'fr_FR',
        'id_ID',
        'it_IT',
        'nb_NO',
        'nl_NL',
        'tr_TR',
        'sr_RS',
        'ro_RO',
        'ru_RU',
        'pl_PL',
        'pt_BR',
        'uk_UA',
        'vi_VN',
        'zh_CN'
    ],
    'language' => 'en_US',
    'logger' =>
    array (
        'path' => 'data/logs/espo.log',
        'level' => 'WARNING', /** DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY */
        'rotation' => true,
        'maxFileNumber' => 30,
    ),
    'authenticationMethod' => 'Espo',
    'globalSearchEntityList' =>
    array (
        'Account',
        'Contact',
        'Lead',
        'Opportunity',
    ),
    'tabList' => ["Account", "Contact", "Lead", "Opportunity", "Case", "Email", "Calendar", "Meeting", "Call", "Task", "_delimiter_", "Document", "Campaign", "KnowledgeBaseArticle", "Stream", "User"],
    'quickCreateList' => ["Account", "Contact", "Lead", "Opportunity", "Meeting", "Call", "Task", "Case", "Email"],
    'exportDisabled' => false,
    'assignmentEmailNotifications' => false,
    'assignmentEmailNotificationsEntityList' => ['Lead', 'Opportunity', 'Task', 'Case'],
    'assignmentNotificationsEntityList' => ['Meeting', 'Call', 'Task', 'Email'],
    "portalStreamEmailNotifications" => true,
    'streamEmailNotificationsEntityList' => ['Case'],
    'emailMessageMaxSize' => 10,
    'notificationsCheckInterval' => 10,
    'disabledCountQueryEntityList' => ['Email'],
    'maxEmailAccountCount' => 2,
    'followCreatedEntities' => false,
    'b2cMode' => false,
    'restrictedMode' => false,
    'theme' => 'HazyblueVertical',
    'massEmailMaxPerHourCount' => 100,
    'personalEmailMaxPortionSize' => 10,
    'inboundEmailMaxPortionSize' => 20,
    'authTokenLifetime' => 0,
    'authTokenMaxIdleTime' => 120,
    'userNameRegularExpression' => '[^a-z0-9\-@_\.\s]',
    'addressFormat' => 1,
    'displayListViewRecordCount' => true,
    'dashboardLayout' => [
        (object) [
            'name' => 'My Espo',
            'layout' => [
                (object) [
                    'id' => 'default-activities',
                    'name' => 'Activities',
                    'x' => 2,
                    'y' => 2,
                    'width' => 2,
                    'height' => 2
                ],
                (object) [
                    'id' => 'default-stream',
                    'name' => 'Stream',
                    'x' => 0,
                    'y' => 0,
                    'width' => 2,
                    'height' => 4
                ],
                (object) [
                    'id' => 'default-tasks',
                    'name' => 'Tasks',
                    'x' => 2,
                    'y' => 0,
                    'width' => 2,
                    'height' => 2
                ]
            ]
        ]
    ],
    'calendarEntityList' => ['Meeting', 'Call', 'Task'],
    'activitiesEntityList' => ['Meeting', 'Call'],
    'historyEntityList' => ['Meeting', 'Call', 'Email'],
    'lastViewedCount' => 20,
    'cleanupJobPeriod' => '1 month',
    'cleanupActionHistoryPeriod' => '15 days',
    'cleanupAuthTokenPeriod' => '1 month',
    'currencyFormat' => 1,
    'currencyDecimalPlaces' => null,
    'aclStrictMode' => false,
    'isInstalled' => false
);

