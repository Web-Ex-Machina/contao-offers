<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Model;

use Contao\Model\Collection;

/**
 * Reads and writes items.
 */
class OfferFeedAttribute extends \WEM\UtilsBundle\Model\Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_offer_feed_attribute';

    /**
     * Find items, depends on the arguments.
     *
     * @throws \Exception
     */
    public static function findItems(
        array $arrConfig = [],
        int $intLimit = 0,
        int $intOffset = 0,
        array $arrOptions = []
    ): ?Collection {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if ($intLimit > 0) {
            $arrOptions['limit'] = $intLimit;
        }

        if ($intOffset > 0) {
            $arrOptions['offset'] = $intOffset;
        }

        $arrOptions['order'] ??= $t . '.createdAt DESC';

        if ($arrColumns === []) {
            return static::findAll($arrOptions);
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Generic statements format.
     *
     * @param string $strField    [Column to format]
     * @param mixed  $varValue    [Value to use]
     * @param string $strOperator [Operator to use, default "="]
     */
    public static function formatStatement(string $strField, $varValue, string $strOperator = '='): array
    {
        $arrColumns = [];
        $t = static::$strTable;

        switch ($strField) {
            // Search by pid
            case 'pid':
                if (\is_array($varValue)) {
                    $arrColumns[] = $t . '.pid IN(' . implode(',', array_map(intval(...), $varValue)) . ')';
                } else {
                    $arrColumns[] = $t . '.pid = ' . (int) $varValue;
                }

                break;

                // Search by name
            case 'name':
                // Attribute names are field names, drop anything else to keep the query safe
                $names = array_filter((array) $varValue, static fn ($name): bool => \is_string($name) && preg_match('/^\w+$/', $name) === 1);
                $arrColumns[] = $names === [] ? '1 = 0' : $t . ".name IN('" . implode("','", $names) . "')";

                break;
        }

        return $arrColumns;
    }
}
