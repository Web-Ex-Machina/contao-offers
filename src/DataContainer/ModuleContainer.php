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

namespace WEM\OffersBundle\DataContainer;

use Contao\Backend;

class ModuleContainer extends Backend
{
    /**
     * Return all templates as array.
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->getTemplateGroup('offer_');
    }

    /**
     * Return all feeds as array.
     *
     * @return array
     */
    public function getFeeds()
    {
        $arrFeeds = [];
        $objFeeds = $this->Database->execute('SELECT id, title FROM tl_wem_offer_feed ORDER BY title');

        if (!$objFeeds || 0 === $objFeeds->count()) {
            return $arrFeeds;
        }

        while ($objFeeds->next()) {
            $arrFeeds[$objFeeds->id] = $objFeeds->title;
        }

        return $arrFeeds;
    }

    /**
     * Return all alerts available gateways.
     *
     * @return array
     */
    public function getAlertsOptions()
    {
        return [
            'email' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['GATEWAY']['email'],
        ];
    }

    /**
     * Return all alerts available gateways.
     *
     * @return array
     */
    public function getConditionsOptions()
    {
        $this->loadDataContainer('tl_wem_offer');
        $fields = [];

        foreach ($GLOBALS['TL_DCA']['tl_wem_offer']['fields'] as $k => $v) {
            // if (!empty($v['eval']) && true === $v['eval']['wemoffers_isAvailableForAlerts']) {
            if (!empty($v['eval']) && true === $v['eval']['isAlertCondition']) {
                $fields[$k] = $v['label'][0] ?: $k;
            }
        }

        return $fields;
    }

    /**
     * Return all job alerts available gateways.
     *
     * @return array
     */
    public function getFiltersOptions()
    {
        $this->loadDataContainer('tl_wem_offer');
        $fields = [];

        foreach ($GLOBALS['TL_DCA']['tl_wem_offer']['fields'] as $k => $v) {
            // if (!empty($v['eval']) && true === $v['eval']['wemoffers_isAvailableForFilters']) {
            if (!empty($v['eval']) && true === $v['eval']['isFilter']) {
                $fields[$k] = $v['label'][0] ?: $k;
            }
        }

        return $fields;
    }

    /**
     * Get Notification Choices for this kind of modules.
     *
     * @return [Array]
     */
    public function getSubscribeNotificationChoices()
    {
        $arrChoices = [];
        $objNotifications = $this->Database->execute("SELECT id,title FROM tl_nc_notification WHERE type='wem_offers_alerts_subscribe' ORDER BY title");

        while ($objNotifications->next()) {
            $arrChoices[$objNotifications->id] = $objNotifications->title;
        }

        return $arrChoices;
    }

    /**
     * Get Notification Choices for this kind of modules.
     *
     * @return [Array]
     */
    public function getUnsubscribeNotificationChoices()
    {
        $arrChoices = [];
        $objNotifications = $this->Database->execute("SELECT id,title FROM tl_nc_notification WHERE type='wem_offers_alerts_unsubscribe' ORDER BY title");

        while ($objNotifications->next()) {
            $arrChoices[$objNotifications->id] = $objNotifications->title;
        }

        return $arrChoices;
    }
}
