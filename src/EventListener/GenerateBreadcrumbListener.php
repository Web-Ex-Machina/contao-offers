<?php

declare(strict_types=1);

namespace WEM\OffersBundle\EventListener;

use Contao\Environment;
use Contao\Input;
use Contao\Module;
use WEM\OffersBundle\Model\Offer;

class GenerateBreadcrumbListener
{
    public function onGenerateBreadcrumb(array $items, Module $module): array
    {
        // Check if we have an auto_item and if it's an Offer
        if (Input::get('auto_item') && $objOffer = Offer::findByIdOrCode(Input::get('auto_item'))) {
            array_pop($items);

            $items[] = [
                'isRoot' => false,
                'isActive' => true,
                'href' => Environment::get('request'),
                'title' => $objOffer->title,
                'link' => $objOffer->title,
                'class' => '',
            ];
        }

        return $items;
    }
}