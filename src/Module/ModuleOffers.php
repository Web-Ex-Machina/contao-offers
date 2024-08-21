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

use Contao\Config;
use Contao\ContentModel;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\RequestToken;
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
     * @param Model\Collection $objItems
     * @param bool             $blnAddArchive
     *
     * @return array
     */
    protected function parseOffers($objItems, $blnAddArchive = false)
    {
        $limit = $objItems->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrItems = [];

        while ($objItems->next()) {
            $objItem = $objItems->current();

            $arrItems[] = $this->parseOffer($objItem, $blnAddArchive, ((1 === ++$count) ? ' first' : '').(($count === $limit) ? ' last' : '').((0 === ($count % 2)) ? ' odd' : ' even'), $count);
        }

        return $arrItems;
    }

    /**
     * Parse an item and return it as string.
     *
     * @param Offer     $objItem
     * @param bool      $blnAddArchive
     * @param string    $strClass
     * @param int       $intCount
     *
     * @return string
     */
    protected function parseOffer($objItem, $blnAddArchive = false, $strClass = '', $intCount = 0)
    {
        $objTemplate = new FrontendTemplate($this->offer_template);
        $objTemplate->setData($objItem->row());

        if ('' !== $objItem->cssClass) {
            $strClass = ' '.$objItem->cssClass . $strClass;
        }

        $objTemplate->model = $objItem;
        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount; // see #5708

        // Add the meta information
        $objTemplate->date = (int) $objItem->date;
        $objTemplate->timestamp = $objItem->date;
        $objTemplate->datetime = date('Y-m-d\TH:i:sP', (int) $objItem->date);

        // Add an image
        if ($objItem->addImage)
        {
            $figure = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->from($objItem->singleSRC)
                ->setSize($objItem->size)
                ->enableLightbox((bool) $objItem->fullsize)
                ->buildIfResourceExists();

            if (null !== $figure)
            {
                $figure->applyLegacyTemplateData($objTemplate, $objItem->imagemargin, $objItem->floating);
            }
        }

        // Retrieve item teaser
        if ($objItem->teaser)
        {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = StringUtil::encodeEmail($objItem->teaser);
        }

        // Retrieve item content
        $id = $objItem->id;

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

        $objTemplate->hasText = static function () use ($objItem)
        {
            return ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_wem_offer') > 0;
        };

        // Retrieve item attributes
        $objTemplate->blnDisplayAttributes = (bool) $this->offer_displayAttributes;

        if ((bool) $this->offer_displayAttributes && null !== $this->offer_attributes) {
            $objTemplate->attributes = $objItem->getAttributesFull(deserialize($this->offer_attributes));
        }

        // Notice the template if we want/can display apply button
        if ($this->blnDisplayApplyButton) {
            $objTemplate->blnDisplayApplyButton = true;
            $objTemplate->applyUrl = $this->addToUrl('apply='.$objItem->id, true, ['offer']);
        }

        // Notice the template if we want to display the text
        if ($this->offer_displayTeaser) {
            $objTemplate->blnDisplayText = true;
        } else {
            $objTemplate->detailsUrl = $this->addToUrl('seeDetails='.$objItem->id, true, ['offer']);
        }

        // Parse the URL if we have a jumpTo configured
        if ($objTarget = $objItem->getRelated('pid')->getRelated('jumpTo')) {
            $params = (Config::get('useAutoItem') ? '/' : '/items/') . ($objItem->code ?: $objItem->id);
            $objTemplate->jumpTo = $objTarget->getFrontendUrl($params);
        }

        return $objTemplate->parse();
    }

    /**
     * Parse and return an application form for a job.
     *
     * @param int    $intId       [Job ID]
     * @param string $strTemplate [Template name]
     *
     * @return string
     */
    protected function getApplicationForm($intId, $strTemplate = 'offer_apply')
    {
        if (!$this->offer_applicationForm) {
            return '';
        }

        $strForm = $this->getForm($this->offer_applicationForm);

        $objItem = Offer::findByPk($intId);

        if (!$objItem) {
            return '';
        }

        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->id = $objItem->id;
        $objTemplate->code = $objItem->code;
        $objTemplate->title = $objItem->title;
        $objTemplate->recipient = $GLOBALS['TL_ADMIN_EMAIL'];
        $objTemplate->time = time();
        $objTemplate->token = RequestToken::get();
        $objTemplate->form = $strForm;

        return $objTemplate->parse();
    }

    /**
     * Get a package's version.
     *
     * @param string $package The package name
     *
     * @return string|null The package version if found, null otherwise
     */
    protected function getCustomPackageVersion(string $package): ?string
    {
        $packages = json_decode(file_get_contents(TL_ROOT.'/vendor/composer/installed.json'));

        foreach ($packages->packages as $p) {
            $p = (array) $p;
            if ($package === $p['name']) {
                return $p['version'];
            }
        }

        return null;
    }
}
