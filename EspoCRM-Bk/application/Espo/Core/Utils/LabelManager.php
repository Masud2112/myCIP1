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

namespace Espo\Core\Utils;

use \Espo\Core\Utils\Util;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;

class LabelManager extends \Espo\Core\Injectable
{
    protected $dependencyList = [
        'config',
        'fileManager',
        'metadata',
        'defaultLanguage'
    ];

    protected $ignoreList = [
        'Global.sets'
    ];

    public function getScopeList()
    {
        $scopeList = [];

        $languageObj = $this->getDefaultLanguage();

        $data = $languageObj->getAll();
        foreach (array_keys($data) as $scope) {
            if (!in_array($scope, $scopeList)) {
                $scopeList[] = $scope;
            }
        }

        foreach ($this->getMetadata()->get('scopes') as $scope => $data) {
            if (!in_array($scope, $scopeList)) {
                $scopeList[] = $scope;
            }
        }

        return $scopeList;
    }

    public function getScopeData($language, $scope)
    {
        $languageObj = new Language($language, $this->getFileManager(), $this->getMetadata());

        $data = $languageObj->get($scope);

        if (empty($data)) {
            return (object) [];
        }

        if ($this->getMetadata()->get(['scopes', $scope, 'entity'])) {

            if (empty($data['fields'])) {
                $data['fields'] = array();
            }
            foreach ($this->getMetadata()->get(['entityDefs', $scope, 'fields']) as $field => $item) {
                if (!array_key_exists($field, $data['fields'])) {
                    $data['fields'][$field] = $languageObj->get('Global.fields.' . $field);
                    if (is_null($data['fields'][$field])) {
                        $data['fields'][$field] = '';
                    }
                }
            }
            if (empty($data['links'])) {
                $data['links'] = array();
            }
            foreach ($this->getMetadata()->get(['entityDefs', $scope, 'links']) as $link => $item) {
                if (!array_key_exists($link, $data['links'])) {
                    $data['links'][$link] = $languageObj->get('Global.links.' . $link);
                    if (is_null($data['links'][$link])) {
                        $data['links'][$link] = '';
                    }
                }
            }

            if (empty($data['labels'])) {
                $data['labels'] = array();
            }
            if (!array_key_exists('Create ' . $scope, $data['labels'])) {
                $data['labels']['Create ' . $scope] = '';
            }
        }

        if ($scope === 'Global') {
            if (empty($data['scopeNames'])) {
                $data['scopeNames'] = array();
            }
            if (empty($data['scopeNamesPlural'])) {
                $data['scopeNamesPlural'] = array();
            }
            foreach ($this->getMetadata()->get(['scopes']) as $scopeKey => $item) {
                if (!empty($item['entity'])) {
                    if (empty($data['scopeNamesPlural'][$scopeKey])) {
                        $data['scopeNamesPlural'][$scopeKey] = '';
                    }
                }
                if (empty($data['scopeNames'][$scopeKey])) {
                    $data['scopeNames'][$scopeKey] = '';
                }
            }
        }

        foreach ($data as $key => $value) {
            if (empty($value)) {
                unset($data[$key]);
            }
        }

        $finalData = array();

        foreach ($data as $category => $item) {
            if (in_array($scope . '.' . $category, $this->ignoreList)) continue;
            foreach ($item as $key => $categoryItem) {
                if (is_array($categoryItem)) {
                    foreach ($categoryItem as $subKey => $subItem) {
                        $finalData[$category][$category .'[.]' . $key .'[.]' . $subKey] = $subItem;
                    }
                } else {
                    $finalData[$category][$category .'[.]' . $key] = $categoryItem;
                }
            }
        }

        return $finalData;
    }

    public function saveLabels($language, $scope, $labels)
    {
        $languageObj = new Language($language, $this->getFileManager(), $this->getMetadata());
        $languageOriginalObj = new Language($language, $this->getFileManager(), $this->getMetadata(), false, true);

        $returnDataHash = array();

        foreach ($labels as $key => $value) {
            $arr = explode('[.]', $key);
            $category = $arr[0];
            $name = $arr[1];

            $setPath = [$scope, $category, $name];

            $setValue = null;

            if (count($arr) == 2) {
                if ($value !== '') {
                    $languageObj->set($scope, $category, $name, $value);
                    $setValue = $value;
                } else {
                    $setValue = $languageOriginalObj->get(implode('.', [$scope, $category, $name]));
                    if (is_null($setValue) && $scope !== 'Global') {
                        $setValue = $languageOriginalObj->get(implode('.', ['Global', $category, $name]));
                    }
                    $languageObj->delete($scope, $category, $name);
                }
            } else if (count($arr) == 3) {
                $name = $arr[1];
                $attribute = $arr[2];
                $data = $languageObj->get($scope . '.' . $category . '.' . $name);

                $setPath[] = $attribute;

                if (is_array($data)) {
                    if ($value !== '') {
                        $data[$attribute] = $value;
                        $setValue = $value;
                    } else {
                        $dataOriginal = $languageOriginalObj->get($scope . '.' . $category . '.' . $name);
                        if (is_array($dataOriginal) && isset($dataOriginal[$attribute])) {
                            $data[$attribute] = $dataOriginal[$attribute];
                            $setValue = $dataOriginal[$attribute];
                        }
                    }
                    $languageObj->set($scope, $category, $name, $data);
                }
            }

            if (!is_null($setValue)) {
                $frontKey = implode('[.]', $setPath);
                $returnDataHash[$frontKey] = $setValue;
            }
        }

        $languageObj->save();

        return $returnDataHash;
    }
}
