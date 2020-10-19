<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina.
 *
 * @category ContaoBundle
 *
 * @author   Web ex Machina <contact@webexmachina.fr>
 *
 * @see     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\JobOffersBundle\DataContainer;

class ModuleContainer extends \Backend
{
    /**
     * Return all job templates as array.
     *
     * @return array
     */
    public function getJobsTemplates()
    {
        return $this->getTemplateGroup('job_');
    }

    /**
     * Return all job feeds as array.
     *
     * @return array
     */
    public function getJobFeeds()
    {
        $arrFeeds = [];
        $objFeeds = $this->Database->execute('SELECT id, title FROM tl_wem_job_feed ORDER BY title');

        if (!$objFeeds || 0 === $objFeeds->count()) {
            return $arrFeeds;
        }

        while ($objFeeds->next()) {
            $arrFeeds[$objFeeds->id] = $objFeeds->title;
        }

        return $arrFeeds;
    }

    /**
     * Return all job alerts available gateways.
     *
     * @return array
     */
    public function getJobAlertsOptions()
    {
        return [
            'email' => $GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['GATEWAY']['email'],
        ];
    }

    /**
     * Return all job alerts available gateways.
     *
     * @return array
     */
    public function getJobFiltersOptions()
    {
        $this->loadDataContainer('tl_wem_job');
        $fields = [];

        foreach ($GLOBALS['TL_DCA']['tl_wem_job']['fields'] as $k => $v) {
            if (!empty($v['eval']) && true === $v['eval']['wemjoboffers_availableForFilters']) {
                $fields[$k] = $v['label'][0] ?: $k;
            }
        }

        return $fields;
    }
}