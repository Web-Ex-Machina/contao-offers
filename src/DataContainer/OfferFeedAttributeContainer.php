<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-job-offers
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\OffersBundle\DataContainer;

class OfferFeedAttributeContainer extends \Backend
{
    /**
     * Format items list.
     *
     * @param array $r
     *
     * @return string
     */
    public function listItems($r)
    {
        return sprintf(
            '%s <span style="color:#888">[%s]</span>',
            $r['name'],
            $r['label']
        );
    }

    /**
     * Return a list of form fields
     *
     * @return array
     */
    public function getFieldOptions()
    {
        return ['text', 'select', 'picker', 'fileTree', 'listWizard'];
    }

    /**
     * Return a list of form fields
     *
     * @return array
     */
    public function getFieldsAndLegends()
    {
        $this->loadDataContainer('tl_wem_offer');
        $arrOptions = array();

        $strPalette = $GLOBALS['TL_DCA']['tl_wem_offer']['palettes']['default'];
        $arrChunks = explode(';', $strPalette);

        if (empty($arrChunks)) {
            return $arrOptions;
        }

        foreach ($arrChunks as $c) {
            $arrWidgets = explode(',', $c);

            if (empty($arrWidgets)) {
                continue;
            }

            foreach ($arrWidgets as $w) {
                if (false !== strpos($w, "_legend")) {
                    $arrOptions['legends'][] = trim(str_replace(["{", "}", "_hidden"], ["", "", ""], $w));
                    continue;
                }

                $arrOptions['fields'][] = trim($w);

                $arrSubfields = $this->getFieldsFromSubpalette($w);

                if(!empty($arrSubfields)) {
                    $arrOptions['fields'] = array_merge($arrOptions['fields'], $arrSubfields);
                }
            }
        }

        return $arrOptions;
    }

    /**
     * Retrieve fields from subpalette
     * 
     * @param  string $f
     * 
     * @return array
     */
    protected function getFieldsFromSubpalette($f)
    {
        $arrFields = [];

        if (array_key_exists('subpalettes', $GLOBALS['TL_DCA']['tl_wem_offer']) && array_key_exists($f, $GLOBALS['TL_DCA']['tl_wem_offer']['subpalettes'])) {
            $arrSubfields = explode(',', $GLOBALS['TL_DCA']['tl_wem_offer']['subpalettes'][$f]);

            if (empty($arrSubfields)) {
                return $arrFields;
            }

            foreach($arrSubfields as $s) {
                $arrFields[] = trim($s);

                $arrSubfields = $this->getFieldsFromSubpalette($s);

                if(!empty($arrSubfields)) {
                    $arrFields = array_merge($arrFields, $arrSubfields);
                }
            }
        }

        return $arrFields;
    }
}
