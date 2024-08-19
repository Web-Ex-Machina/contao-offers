<?php

declare(strict_types=1);

namespace WEM\OffersBundle\EventListener;

use WEM\OffersBundle\Model\Offer;

class CountOffersInsertTagListener
{
    public const TAG = 'countoffers';
    
    /**
     * Example {{countoffers::1,2,3...}}
     */
    public function replaceInsertTags(string $tag)
    {
        $chunks = explode('::', $tag);

        if (self::TAG !== $chunks[0]) {
            return false;
        }

        // Retrieve the PIDs wanted
        $arrPids = explode(",", $chunks[1]);

        // Call the Model
        $intCount = Offer::countItems(['pid' => $arrPids, 'published' => 1]);
        
        return $intCount;
    }
}