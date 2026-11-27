<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\EventListener\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\FilesModel;
use Contao\Input;
use WEM\OffersBundle\Model\Offer;

/**
 * Examples:
 * {{offer::title}}
 * {{offer::title::1}}.
 */
#[AsInsertTag('offer')]
class OfferInsertTagListener
{
    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $field = (string) $insertTag->getParameters()->get(0);

        // Check if we want a specific offer or the current one
        $varOffer = $insertTag->getParameters()->get(1) ?? Input::get('auto_item');
        $objOffer = $varOffer !== null ? Offer::findByIdOrCode((string) $varOffer) : null;

        // We can't throw an Exception that can break a website just because an ID is wrong
        if (! $objOffer instanceof Offer || $field === '') {
            return new InsertTagResult('');
        }

        // Specific behavior for singleSRC
        if ($field === 'singleSRC') {
            return new InsertTagResult((string) FilesModel::findByUuid($objOffer->singleSRC)?->path, OutputType::url);
        }

        $value = $objOffer->getAttributeValue($field) ?: $objOffer->{$field};

        return new InsertTagResult(\is_scalar($value) ? (string) $value : '', OutputType::html);
    }
}
