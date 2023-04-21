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

use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\OfferFeed;

class OfferAlertContainer
{
    /**
     * Design each row of the DCA.
     *
     * @return string
     */
    public function listItems(array $row, string $label, \Contao\DataContainer $dc, array $labels): array
    {
        // return sprintf(
        //     '%s <span style="color:#888">[%s]</span>',
        //     $row['name'],
        //     $row['email']
        // );

        $objFeed = OfferFeed::findByPk($row['feed']);

        $labels[0] = $row['email'];
        $labels[1] = $objFeed ? $objFeed->title : $row['feed'];
        $labels[2] = $GLOBALS['TL_LANG'][Alert::getTable()]['frequency'][$row['frequency']];
        $labels[3] = !empty($row['lastJob']) ? \Contao\Date::parse(\Contao\Config::get('datimFormat'), (int) $row['lastJob']) : '-';
        $labels[4] = !empty($row['activatedAt']) ? \Contao\Date::parse(\Contao\Config::get('datimFormat'), (int) $row['activatedAt']) : '-';

        return $labels;
    }

    /**
     * Get available feeds.
     *
     * @return [Array]
     */
    public function getFeeds()
    {
        $arrChoices = [];
        $objFeeds = \Database::getInstance()->execute("SELECT id,title FROM tl_wem_offer_feed ORDER BY title");

        while ($objFeeds->next()) {
            $arrChoices[$objFeeds->id] = $objFeeds->title;
        }

        return $arrChoices;
    }
}
