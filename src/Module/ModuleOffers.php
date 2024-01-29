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

use Contao\ContentModel;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use WEM\OffersBundle\Model\OfferFeedAttribute;
use WEM\OffersBundle\Model\Offer;

/**
 * Common functions for job offers modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModuleOffers extends Module
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
     * @param Offer     $objArticle
     * @param bool      $blnAddArchive
     * @param string    $strClass
     * @param int       $intCount
     *
     * @return string
     */
    protected function parseOffer($objArticle, $blnAddArchive = false, $strClass = '', $intCount = 0)
    {
        $objTemplate = new FrontendTemplate($this->offer_template);
        $objTemplate->setData($objArticle->row());

        if ('' !== $objArticle->cssClass) {
            $strClass = ' '.$objArticle->cssClass . $strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount; // see #5708

        // Add the meta information
        $objTemplate->date = (int) $objArticle->date;
        $objTemplate->timestamp = $objArticle->date;
        $objTemplate->datetime = date('Y-m-d\TH:i:sP', (int) $objArticle->date);

        // Add an image
        if ($objArticle->addImage)
        {
            $figure = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->from($objArticle->singleSRC)
                ->setSize($objArticle->size)
                ->enableLightbox((bool) $objArticle->fullsize)
                ->buildIfResourceExists();

            if (null !== $figure)
            {
                $figure->applyLegacyTemplateData($objTemplate, $objArticle->imagemargin, $objArticle->floating);
                $objTemplate->picture = $figure->getLegacyTemplateData();
            }
        }

        // Retrieve item teaser
        if ($objArticle->teaser)
        {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = StringUtil::encodeEmail($objArticle->teaser);
        }

        // Retrieve item content
        $id = $objArticle->id;

        $objTemplate->text = function () use ($id)
        {
            $strText = '';
            $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_offer');

            if ($objElement !== null)
            {
                while ($objElement->next())
                {
                    $strText .= $this->getContentElement($objElement->current());
                }
            }

            return $strText;
        };

        $objTemplate->hasText = static function () use ($objArticle)
        {
            return ContentModel::countPublishedByPidAndTable($objArticle->id, 'tl_wem_offer') > 0;
        };

        // Retrieve item attributes
        $objTemplate->blnDisplayAttributes = (bool) $this->offer_displayAttributes;

        if ((bool) $this->offer_displayAttributes && null !== $this->offer_attributes) {
            $objTemplate->attributes = $objArticle->getAttributesFull(deserialize($this->offer_attributes));
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
