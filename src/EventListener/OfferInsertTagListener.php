<?php

declare(strict_types=1);

namespace WEM\OffersBundle\EventListener;

use Contao\FilesModel;
use Contao\Input;
use WEM\OffersBundle\Model\Offer;

class OfferInsertTagListener
{
    public const TAG = 'offer';
    
    /**
     * Examples: 
     * {{offer::title}}
     * {{offer::title::1}}
     */
    public function replaceInsertTags(string $tag)
    {
        $chunks = explode('::', $tag);

        if (self::TAG !== $chunks[0]) {
            return false;
        }

        // Check if we want a specific offer or the current one
        if (3 === count($chunks)) {
            $varOffer = $chunks[2];
        } else if (Input::get('auto_item')) {
            $varOffer = Input::get('auto_item');
        } else if (Input::post('offer')) {
            $varOffer = Input::post('offer');
        }

        if (!$varOffer) {
            return false;
        }

        $objOffer = Offer::findByIdOrCode($varOffer);

        // If objOffer does not exist, return empty string
        // We can't throw an Exception that can break a website just because an ID is wrong, can't we?
        if (null === $objOffer) {
            return '';
        }

        // Specific behavior for singleSRC
        if ('singleSRC' === $chunks[1]) {
            $objFile = FilesModel::findByUuid($objOffer->{$chunks[1]});

            return $objFile->path;
        }
        
        return $objOffer->getAttributeValue($chunks[1]) ?: $objOffer->{$chunks[1]};
    }
}