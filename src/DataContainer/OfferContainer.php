<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeedAttribute;

class OfferContainer
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Format items list.
     */
    #[AsCallback(table: 'tl_wem_offer', target: 'list.sorting.child_record')]
    public function listItems(array $r): string
    {
        return \sprintf('%s <span class="label-info">[%s]</span>', $r['title'], $r['code']);
    }

    /**
     * Add the feed attributes to the palette.
     */
    #[AsCallback(table: 'tl_wem_offer', target: 'config.onload')]
    public function updatePalettes(DataContainer|null $dc = null): void
    {
        if (! $dc?->id || $this->requestStack->getCurrentRequest()?->query->get('act') !== 'edit') {
            return;
        }

        $objOffer = Offer::findById($dc->id);

        if (! $objOffer instanceof Offer) {
            return;
        }

        $objAttributes = OfferFeedAttribute::findItems([
            'pid' => $objOffer->pid,
        ]);

        if (! $objAttributes instanceof \Contao\Model\Collection) {
            return;
        }

        $objPalette = PaletteManipulator::create();

        foreach ($objAttributes as $objAttribute) {
            if (! str_contains((string) $GLOBALS['TL_DCA']['tl_wem_offer']['palettes']['default'], (string) $objAttribute->name)) {
                $objPalette->addField(
                    $objAttribute->name,
                    $objAttribute->insertInDca,
                    PaletteManipulator::{$objAttribute->insertType ?: 'POSITION_APPEND'},
                );
            }
        }

        $objPalette->applyToPalette('default', 'tl_wem_offer');
    }
}
