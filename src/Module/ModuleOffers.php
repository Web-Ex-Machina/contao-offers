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

use Contao\System;
use Contao\Module;
use Contao\ContentModel;
use Contao\Model\Collection;
use Contao\FrontendTemplate;
use WEM\OffersBundle\Model\Offer;
use WEM\UtilsBundle\Classes\StringUtil;

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
     * @param Collection $objArticles
     * @param bool $blnAddArchive
     *
     * @return array
     * @throws \Exception
     */
    protected function parseOffers(Collection $objArticles, bool $blnAddArchive = false): array
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
     * @param Offer $objArticle
     * @param bool $blnAddArchive
     * @param string $strClass
     * @param int $intCount
     *
     * @return string
     * @throws \Exception
     */
    protected function parseOffer(Offer $objArticle, bool $blnAddArchive = false, string $strClass = '', int $intCount = 0): string
    {
        $objTemplate = new FrontendTemplate($this->offer_template);
        $objTemplate->setData($objArticle->row());

        if ('' !== $objArticle->cssClass) {
            $strClass = ' '.$objArticle->cssClass . $strClass;
        }

        $objTemplate->model = $objArticle;
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

        $objTemplate->hasText = static fn() => ContentModel::countPublishedByPidAndTable($objArticle->id, 'tl_wem_offer') > 0;

        // Retrieve item attributes
        $objTemplate->blnDisplayAttributes = (bool) $this->offer_displayAttributes;

        if ((bool) $this->offer_displayAttributes && null !== $this->offer_attributes) {
            $objTemplate->attributes = $objArticle->getAttributesFull(StringUtil::deserialize($this->offer_attributes));
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
        $packages = json_decode(file_get_contents('./../../vendor/composer/installed.json'));

        foreach ($packages->packages as $p) {
            $p = (array) $p;
            if ($package === $p['name']) {
                return $p['version'];
            }
        }

        return null;
    }
}
