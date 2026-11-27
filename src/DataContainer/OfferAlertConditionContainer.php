<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\StringUtil;

class OfferAlertConditionContainer
{
    /**
     * Design each row of the DCA.
     */
    #[AsCallback(table: 'tl_wem_offer_alert_condition', target: 'list.sorting.child_record')]
    public function listItems(array $row): string
    {
        // Multiple values are stored serialized
        return \sprintf('%s = %s', $row['field'], implode(', ', StringUtil::deserialize($row['value'], true)));
    }
}
