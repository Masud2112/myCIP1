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

namespace Espo\Core;

class DataManager
{
    private $container;

    private $cachePath = 'data/cache';


    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Rebuild the system with metadata, database and cache clearing
     *
     * @return bool
     */
    public function rebuild($entityList = null)
    {
        $result = $this->clearCache();

        $result &= $this->rebuildMetadata();

        $result &= $this->rebuildDatabase($entityList);

        $this->rebuildScheduledJobs();

        return $result;
    }

    /**
     * Clear a cache
     *
     * @return bool
     */
    public function clearCache()
    {
        $result = $this->getContainer()->get('fileManager')->removeInDir($this->cachePath);

        if ($result != true) {
            throw new Exceptions\Error("Error while clearing cache");
        }

        $this->updateCacheTimestamp();

        return $result;
    }

    /**
     * Rebuild database
     *
     * @return bool
     */
    public function rebuildDatabase($entityList = null)
    {
        try {
            $result = $this->getContainer()->get('schema')->rebuild($entityList);
        } catch (\Exception $e) {
            $result = false;
            $GLOBALS['log']->error('Fault to rebuild database schema'.'. Details: '.$e->getMessage());
        }

        if ($result != true) {
            throw new Exceptions\Error("Error while rebuilding database. See log file for details.");
        }

        $this->updateCacheTimestamp();

        return $result;
    }

    /**
     * Rebuild metadata
     *
     * @return bool
     */
    public function rebuildMetadata()
    {
        $metadata = $this->getContainer()->get('metadata');

        $metadata->init(true);

        $ormData = $this->getContainer()->get('ormMetadata')->getData(true);

        $this->updateCacheTimestamp();

        return empty($ormData) ? false : true;
    }

    public function rebuildScheduledJobs()
    {
        $metadata = $this->getContainer()->get('metadata');
        $entityManager = $this->getContainer()->get('entityManager');

        $jobs = $metadata->get(['entityDefs', 'ScheduledJob', 'jobs'], array());

        foreach ($jobs as $jobName => $defs) {
            if ($jobName && !empty($defs['isSystem']) && !empty($defs['scheduling'])) {
                if (!$entityManager->getRepository('ScheduledJob')->where(array(
                    'job' => $jobName,
                    'status' => 'Active',
                    'scheduling' => $defs['scheduling']
                ))->findOne()) {
                    $job = $entityManager->getRepository('ScheduledJob')->where(array(
                        'job' => $jobName
                    ))->findOne();
                    if ($job) {
                        $entityManager->removeEntity($job);
                    }
                    $job = $entityManager->getEntity('ScheduledJob');
                    $job->set(array(
                        'job' => $jobName,
                        'status' => 'Active',
                        'scheduling' => $defs['scheduling'],
                        'isInternal' => true,
                        'name' => $jobName
                    ));
                    $entityManager->saveEntity($job);
                }
            }
        }
    }

    /**
     * Update cache timestamp
     *
     * @return bool
     */
    public function updateCacheTimestamp()
    {
        $this->getContainer()->get('config')->updateCacheTimestamp();
        $this->getContainer()->get('config')->save();
        return true;
    }
}

