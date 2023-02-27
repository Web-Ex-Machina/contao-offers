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
        $fields = array();

        foreach ($GLOBALS['TL_FFL'] as $k=>$v) {
            if ($k !== 'text' && $k !== 'select') {
                continue;
            }

            $fields[] = $k;
        }

        return $fields;
    }

    /**
     * Return a list of form fields
     *
     * @return array
     *
     * @todo use palettes instead of fields to group fields by legends
     */
    public function getFields()
    {
        $this->loadDataContainer('tl_wem_offer');
        $fields = array();

        foreach ($GLOBALS['TL_DCA']['tl_wem_offer']['fields'] as $k=>$v) {
            $fields[$k] = $v['label'][0] ?: $k;
        }

        return $fields;
    }
}
