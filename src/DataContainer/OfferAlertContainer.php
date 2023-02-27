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

class OfferAlertContainer
{
    /**
     * Design each row of the DCA.
     *
     * @return string
     */
    public function listItems($row)
    {
        return sprintf(
            '%s <span style="color:#888">[%s]</span>',
            $row['name'],
            $row['email']
        );
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
