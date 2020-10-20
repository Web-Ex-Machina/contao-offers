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

namespace WEM\JobOffersBundle\Module;

/**
 * Common functions for job offers modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModuleJobOffers extends \Module
{
    /**
     * Parse one or more items and return them as array.
     *
     * @param Model\Collection $objArticles
     * @param bool             $blnAddArchive
     *
     * @return array
     */
    protected function parseJobOffers($objArticles, $blnAddArchive = false)
    {
        $limit = $objArticles->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrArticles = [];

        while ($objArticles->next()) {
            /** @var NewsModel $objArticle */
            $objArticle = $objArticles->current();

            $arrArticles[] = $this->parseJobOffer($objArticle, $blnAddArchive, ((1 === ++$count) ? ' first' : '').(($count === $limit) ? ' last' : '').((0 === ($count % 2)) ? ' odd' : ' even'), $count);
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
    protected function parseJobOffer($objArticle, $blnAddArchive = false, $strClass = '', $intCount = 0)
    {
        $objTemplate = new \FrontendTemplate($this->job_template);
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

        // Retrieve and parse the HR Picture
        if ($objArticle->hrPicture && $objFile = \FilesModel::findByUuid($objArticle->hrPicture)) {
            $objTemplate->hrPicture = \Image::get($objFile->path, 300, 300);
        }

        // Parse locations
        if (is_array(deserialize($objArticle->locations))) {
            $objTemplate->locations = implode(', ', deserialize($objArticle->locations));
        } else {
            $objTemplate->locations = $objArticle->locations;
        }

        // Fetch the job offer file
        if ($objFile = \FilesModel::findByUuid($objArticle->file)) {
            $objTemplate->file = $objFile->path;
        } else {
            $objTemplate->file = null;
        }

        // Notice the template if we want/can display apply button
        if ($this->blnDisplayApplyButton) {
            $objTemplate->blnDisplayApplyButton = true;
            $objTemplate->applyUrl = $this->addToUrl('apply='.$objArticle->id, true, ['job']);

            // Comply with i18nl10n constraints
            if (\array_key_exists('VerstaerkerI18nl10nBundle', $this->bundles)) {
                $objTemplate->applyUrl = $GLOBALS['TL_LANGUAGE'].'/'.$objTemplate->applyUrl;
            }
        }

        // Notice the template if we want to display the text
        if ($this->job_displayTeaser) {
            $objTemplate->blnDisplayText = true;
        } else {
            $objTemplate->detailsUrl = $this->addToUrl('seeDetails='.$objArticle->id, true, ['job']);

            // Comply with i18nl10n constraints
            if (\array_key_exists('VerstaerkerI18nl10nBundle', $this->bundles)) {
                $objTemplate->detailsUrl = $GLOBALS['TL_LANGUAGE'].'/'.$objTemplate->detailsUrl;
            }
        }

        // Notify the template we must open this item apply modal
        if ($this->openApplyModalOnStart && $objArticle->id === $this->openApplyModalOnStart) {
            $objTemplate->openApplyModalOnStart = true;
        }

        // Tag the response
        if (\System::getContainer()->has('fos_http_cache.http.symfony_response_tagger')) {
            /** @var ResponseTagger $responseTagger */
            $responseTagger = \System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(['contao.db.tl_pzl_job.'.$objArticle->id]);
        }

        return $objTemplate->parse();
    }
}
