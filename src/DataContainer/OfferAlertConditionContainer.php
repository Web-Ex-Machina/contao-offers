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

use Contao\DataContainer;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\AlertCondition;

class OfferAlertConditionContainer extends \Backend
{
    public function __construct()
    {
        Parent::__construct();
    }

    /**
     * Design each row of the DCA.
     *
     * @param array $row
     * @return string
     */
    public function listItems(array $row): string
    {
        return sprintf(
            '%s  = %s',
            $row['field'],
            $row['value']
        );
    }

    /**
     * Retrieve the available fields for alerts condition (limited to the alert feed)
     *
     * @param DataContainer $dc
     * @return array
     * @throws \Exception
     */
    public function getFieldChoices(DataContainer $dc): array
    {
        if (!$dc->activeRecord->pid) {
            return [];
        }


        $objAlert = Alert::findByPk($dc->activeRecord->pid);

        if (!$objAlert) {
            throw new \Exception("No alert found"); // @todo translation
        }

        $arrFields = [];
        $this->loadDataContainer('tl_wem_offer');
        $this->loadLanguageFile('tl_wem_offer');

        foreach ($GLOBALS['TL_DCA']['tl_wem_offer']['fields'] as $name => $field) {
            if (true === $field['eval']['wemoffers_isAvailableForAlerts']) {
                $arrFields[$name] = $field['label'][0];
            }
        }

        return $arrFields;
    }

    /**
     * Retrieve the available values for alerts condition (limited to the alert condition field)
     *
     * @param DataContainer $dc
     * @return void
     */
    public function getValueChoices(DataContainer $dc): void
    {
        // keep the default behaviour if there is no field selected
        if ("" === $dc->id) {
            exit();
        }

        $objCondition = AlertCondition::findByPk($dc->id);

        if (!$objCondition || "" === $objCondition->field) {
            exit();
        }

        $this->loadDataContainer('tl_wem_offer');
        $this->loadLanguageFile('tl_wem_offer');

        // Update the DCA according to the field found
        $field = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$objCondition->field];
        $GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value']['inputType'] = $field['inputType'];

        if ("select" == $field['inputType']) {
            $GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value']['options'] = $field['options'];
            $GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value']['options_callback'] = $field['options_callback'];
            $GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value']['eval']['multiple'] = $field['eval']['multiple'];
            $GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value']['eval']['chosen'] = $field['eval']['chosen'];
            $GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value']['eval']['includeBlankOption'] = $field['eval']['includeBlankOption'];
        }

        $GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value']['eval']['maxlength'] = $field['eval']['maxlength'];
        $GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value']['eval']['rgxp'] = $field['eval']['rgxp'];
    }
}
