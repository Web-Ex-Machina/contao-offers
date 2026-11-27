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
use Contao\DataContainer;
use Contao\System;
use WEM\OffersBundle\Model\AlertCondition;
use WEM\OffersBundle\Model\Offer;

class OfferAlertConditionContainer
{
    /**
     * Design each row of the DCA.
     */
    #[AsCallback(table: 'tl_wem_offer_alert_condition', target: 'list.sorting.child_record')]
    public function listItems(array $row): string
    {
        return \sprintf('%s = %s', $row['field'], $row['value']);
    }

    /**
     * Retrieve the available fields for alerts conditions.
     */
    #[AsCallback(table: 'tl_wem_offer_alert_condition', target: 'fields.field.options')]
    public function getFieldChoices(): array
    {
        Controller::loadDataContainer(Offer::getTable());
        System::loadLanguageFile(Offer::getTable());

        $arrFields = [];

        foreach ($GLOBALS['TL_DCA'][Offer::getTable()]['fields'] as $name => $field) {
            if (true === ($field['eval']['isAlertCondition'] ?? false)) {
                $arrFields[$name] = ($field['label'][0] ?? null) ?: $name;
            }
        }

        return $arrFields;
    }

    /**
     * Adjust the "value" widget to the selected offer field.
     */
    #[AsCallback(table: 'tl_wem_offer_alert_condition', target: 'config.onload')]
    public function adjustValueField(DataContainer|null $dc = null): void
    {
        if (! $dc?->id) {
            return;
        }

        $objCondition = AlertCondition::findById($dc->id);

        if (! $objCondition instanceof AlertCondition || ! $objCondition->field) {
            return;
        }

        Controller::loadDataContainer(Offer::getTable());
        System::loadLanguageFile(Offer::getTable());

        $field = $GLOBALS['TL_DCA'][Offer::getTable()]['fields'][$objCondition->field] ?? null;

        if ($field === null) {
            return;
        }

        $value = &$GLOBALS['TL_DCA']['tl_wem_offer_alert_condition']['fields']['value'];
        $value['inputType'] = $field['inputType'];

        if ($field['inputType'] === 'select') {
            $value['options'] = $field['options'] ?? null;
            $value['options_callback'] = $field['options_callback'] ?? null;

            foreach (['multiple', 'chosen', 'includeBlankOption'] as $key) {
                $value['eval'][$key] = $field['eval'][$key] ?? false;
            }
        }

        foreach (['maxlength', 'rgxp'] as $key) {
            if (isset($field['eval'][$key])) {
                $value['eval'][$key] = $field['eval'][$key];
            }
        }
    }
}
