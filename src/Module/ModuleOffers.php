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

namespace WEM\OffersBundle\Module;

/**
 * Common functions for job offers modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModuleOffers extends \Module
{
    /**
     * Parse one or more items and return them as array.
     *
     * @param Model\Collection $objArticles
     * @param bool             $blnAddArchive
     *
     * @return array
     */
    protected function parseOffers($objArticles, $blnAddArchive = false)
    {
        $limit = $objArticles->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrArticles = [];

        while ($objArticles->next()) {
            $objArticle = $objArticles->current();

            $arrArticles[] = $this->parseOffer($objArticle, $blnAddArchive, ((1 === ++$count) ? ' first' : '').(($count === $limit) ? ' last' : '').((0 === ($count % 2)) ? ' odd' : ' even'), $count);
        }

        return $arrArticles;
    }

    /**
     * Parse an item and return it as string.
     *
     * @param NewsModel $objArticle
     * @param bool      $blnAddArchive
     * @param string    $strClass
     * @param int       $intCount
     *
     * @return string
     */
    protected function parseOffer($objArticle, $blnAddArchive = false, $strClass = '', $intCount = 0)
    {
        $objTemplate = new \FrontendTemplate($this->offer_template);
        $objTemplate->setData($objArticle->row());

        if ('' !== $objArticle->cssClass) {
            $strClass = ' '.$objArticle->cssClass.$strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount; // see #5708

        // Add the meta information
        $objTemplate->date = (int) $objArticle->postedAt;
        $objTemplate->timestamp = $objArticle->postedAt;
        $objTemplate->datetime = date('Y-m-d\TH:i:sP', (int) $objArticle->postedAt);

        // Parse locations
        if (is_array(deserialize($objArticle->locations))) {
            $objTemplate->locations = implode(', ', deserialize($objArticle->locations));
        } else {
            $objTemplate->locations = $objArticle->locations;
        }

        // Fetch the offer offer file
        if ($objFile = \FilesModel::findByUuid($objArticle->file)) {
            $objTemplate->file = $objFile->path;
            $objTemplate->isImage = @is_array(getimagesize($objFile->path));
        } else {
            $objTemplate->file = null;
        }

        // Notice the template if we want/can display apply button
        if ($this->blnDisplayApplyButton) {
            $objTemplate->blnDisplayApplyButton = true;
            $objTemplate->applyUrl = $this->addToUrl('apply='.$objArticle->id, true, ['offer']);
        }

        // Notice the template if we want to display the text
        if ($this->offer_displayTeaser) {
            $objTemplate->blnDisplayText = true;
        } else {
            $objTemplate->detailsUrl = $this->addToUrl('seeDetails='.$objArticle->id, true, ['offer']);
        }

        // Notify the template we must open this item apply modal
        if ($this->openApplyModalOnStart && $objArticle->id === $this->openApplyModalOnStart) {
            $objTemplate->openApplyModalOnStart = true;
        }

        return $objTemplate->parse();
    }
}
