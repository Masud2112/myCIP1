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
use Espo\Core\Exceptions\Error;

class PasswordHash
{
    private $config;

    /**
     * Salt format of SHA-512
     *
     * @var string
     */
    private $saltFormat = '$6${0}$';

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Get hash of a pawword
     *
     * @param  string $password
     * @return string
     */
    public function hash($password, $useMd5 = true)
    {
        $salt = $this->getSalt();

        if ($useMd5) {
            $password = md5($password);
        }

        $hash = crypt($password, $salt);
        $hash = str_replace($salt, '', $hash);

        return $hash;
    }

    /**
     * Get a salt from config and normalize it
     *
     * @return string
     */
    protected function getSalt()
    {
        $salt = $this->getConfig()->get('passwordSalt');
        if (!isset($salt)) {
            throw new Error('Option "passwordSalt" does not exist in config.php');
        }

        $salt = $this->normalizeSalt($salt);

        return $salt;
    }

    /**
     * Convert salt in format in accordance to $saltFormat
     *
     * @param  string $salt
     * @return string
     */
    protected function normalizeSalt($salt)
    {
        return str_replace("{0}", $salt, $this->saltFormat);
    }

    /**
     * Generate a new salt
     *
     * @return string
     */
    public function generateSalt()
    {
        return substr(md5(uniqid()), 0, 16);
    }
}