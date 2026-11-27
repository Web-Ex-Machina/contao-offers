<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

class OfferFeedAttributeContainer
{
    /**
     * Format items list.
     */
    #[AsCallback(table: 'tl_wem_offer_feed_attribute', target: 'list.sorting.child_record')]
    public function listItems(array $r): string
    {
        return \sprintf('%s <span class="label-info">[%s]</span>', $r['name'], $r['label']);
    }

    /**
     * Return a list of form fields.
     */
    #[AsCallback(table: 'tl_wem_offer_feed_attribute', target: 'fields.type.options')]
    public function getFieldOptions(): array
    {
        return ['text', 'textarea', 'select', 'picker', 'fileTree', 'listWizard'];
    }

    /**
     * Return the fields and legends of the offer palette.
     */
    #[AsCallback(table: 'tl_wem_offer_feed_attribute', target: 'fields.insertInDca.options')]
    public function getFieldsAndLegends(): array
    {
        Controller::loadDataContainer('tl_wem_offer');
        $arrOptions = [];

        foreach (explode(';', (string) $GLOBALS['TL_DCA']['tl_wem_offer']['palettes']['default']) as $chunk) {
            foreach (explode(',', $chunk) as $w) {
                $w = trim($w);

                if ($w === '') {
                    continue;
                }

                if (str_contains($w, '_legend')) {
                    $arrOptions['legends'][] = trim(str_replace(['{', '}', ':hide', ':collapsed', '_hidden'], '', $w));
                    continue;
                }

                $arrOptions['fields'][] = $w;
                $arrOptions['fields'] = [...$arrOptions['fields'], ...$this->getFieldsFromSubpalette($w)];
            }
        }

        return $arrOptions;
    }

    /**
     * Retrieve fields from subpalette.
     */
    protected function getFieldsFromSubpalette(string $f): array
    {
        $subpalette = $GLOBALS['TL_DCA']['tl_wem_offer']['subpalettes'][$f] ?? null;

        if ($subpalette === null) {
            return [];
        }

        $arrFields = [];

        foreach (explode(',', $subpalette) as $s) {
            $arrFields = [...$arrFields, trim($s), ...$this->getFieldsFromSubpalette(trim($s))];
        }

        return $arrFields;
    }
}
