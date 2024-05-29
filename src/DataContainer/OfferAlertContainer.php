<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-job-offers
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\Backend;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\Environment;
use Contao\Message;
use Contao\ModuleModel;
use WEM\OffersBundle\Cronjob\SendAlerts;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\OfferFeed;

class OfferAlertContainer extends Backend
{
    public function __construct()
    {
        Parent::__construct();
    }

    /**
     * Design each row of the DCA.
     */
    public function listItems(array $row, string $label, DataContainer $dc, array $labels): array
    {
        $objFeed = OfferFeed::findByPk($row['feed']);

        $labels[0] = $row['email'];
        $labels[1] = $objFeed ? $objFeed->title : $row['feed'];
        $labels[2] = $GLOBALS['TL_LANG'][Alert::getTable()]['frequency'][$row['frequency']];
        $labels[3] = empty($row['lastJob']) ? '-' : Date::parse(Config::get('datimFormat'), (int) $row['lastJob']);
        $labels[4] = empty($row['activatedAt']) ? '-' : Date::parse(Config::get('datimFormat'), (int) $row['activatedAt']);

        return $labels;
    }

    /**
     * Get available feeds.
     *
     * @return array [Array]
     */
    public function getFeeds(): array
    {
        $arrChoices = [];
        $objFeeds = Database::getInstance()->execute("SELECT id,title FROM tl_wem_offer_feed ORDER BY title");

        while ($objFeeds->next()) {
            $arrChoices[$objFeeds->id] = $objFeeds->title;
        }

        return $arrChoices;
    }

    /**
     * @throws \Exception
     */
    public function getOffersAlertModules(): array
    {
        $arrChoices = [];

        $objModules = ModuleModel::findBy('type','offersalert');

        while ($objModules->next()) {
            $objTheme = $objModules->current()->getRelated('pid');
            $arrChoices[$objModules->id] = $objModules->name . ' ('.($objTheme ? $objTheme->name : '-').')';
        }

        return $arrChoices;
    }


    public function sendAlerts(): void
    {
        $objJob = new SendAlerts(); // TODO : ça fonctionne ça ? manque pas $logger, $notificationCenter ??
        $objJob->do(false);

        Message::addInfo($GLOBALS['TL_LANG']['WEM']['OFFERS']['jobExecuted']);

        $referer = preg_replace('/&(amp;)?(key)=[^&]*/', '', Environment::get('request'));
        $this->redirect($referer);
    }
}
