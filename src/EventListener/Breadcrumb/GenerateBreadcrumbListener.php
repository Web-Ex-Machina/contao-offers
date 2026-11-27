<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\EventListener\Breadcrumb;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use Contao\Module;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\OffersBundle\Model\Offer;

#[AsHook('generateBreadcrumb')]
class GenerateBreadcrumbListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(array $items, Module $module): array
    {
        $autoItem = Input::get('auto_item');

        // Check if we have an auto_item and if it's an Offer
        if ($autoItem === null || ! ($objOffer = Offer::findByIdOrCode($autoItem)) instanceof Offer) {
            return $items;
        }

        array_pop($items);

        $items[] = [
            'isRoot' => false,
            'isActive' => true,
            'href' => $this->requestStack->getCurrentRequest()?->getRequestUri(),
            'title' => $objOffer->title,
            'link' => $objOffer->title,
            'class' => '',
        ];

        return $items;
    }
}
