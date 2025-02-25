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

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class User extends \Espo\Core\Controllers\Record
{
    public function actionAcl($params, $data, $request)
    {
        $userId = $request->get('id');
        if (empty($userId)) {
            throw new Error();
        }

        if (!$this->getUser()->isAdmin() && $this->getUser()->id != $userId) {
            throw new Forbidden();
        }

        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (empty($user)) {
            throw new NotFound();
        }

        return $this->getAclManager()->getMap($user);
    }

    public function postActionChangeOwnPassword($params, $data, $request)
    {
        if (!array_key_exists('password', $data) || !array_key_exists('currentPassword', $data)) {
            throw new BadRequest();
        }
        return $this->getService('User')->changePassword($this->getUser()->id, $data['password'], true, $data['currentPassword']);
    }

    public function postActionChangePasswordByRequest($params, $data, $request)
    {
        if (empty($data['requestId']) || empty($data['password'])) {
            throw new BadRequest();
        }

        $p = $this->getEntityManager()->getRepository('PasswordChangeRequest')->where(array(
            'requestId' => $data['requestId']
        ))->findOne();

        if (!$p) {
            throw new Forbidden();
        }
        $userId = $p->get('userId');
        if (!$userId) {
            throw new Error();
        }

        $this->getEntityManager()->removeEntity($p);

        if ($this->getService('User')->changePassword($userId, $data['password'])) {
            return array(
                'url' => $p->get('url')
            );
        }
    }

    public function postActionPasswordChangeRequest($params, $data, $request)
    {
        if (empty($data['userName'])) {
            throw new BadRequest();
        }

        $userName = $data['userName'];
        //$emailAddress = $data['emailAddress'];
        $emailAddress = $userName;
        $url = null;
        if (!empty($data['url'])) {
            $url = $data['url'];
        }

        return $this->getService('User')->passwordChangeRequest($userName, $emailAddress, $url);
    }
}

