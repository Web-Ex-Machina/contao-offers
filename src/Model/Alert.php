<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Model;

use Contao\Model;
use Contao\Model\Collection;
use WEM\UtilsBundle\Model\Model as BaseModel;

/**
 * Reads and writes items.
 */
class Alert extends BaseModel
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_offer_alert';

    /**
     * Find items, depends on the arguments.
     *
     * @return Collection<Alert>|null
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
     * Count items, depends on the arguments.
     */
    public static function countItems(array $arrConfig = [], array $arrOptions = []): int
    {
        $arrColumns = static::formatColumns($arrConfig);

        if ($arrColumns === []) {
            return static::countAll();
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }

    /**
     * Format ItemModel columns.
     *
     * @return array The Model columns
     */
    public static function formatColumns(array $arrConfig): array
    {
        $arrColumns = [];
        foreach ($arrConfig as $c => $v) {
            $arrColumns = array_merge($arrColumns, static::formatStatement($c, $v));
        }

        return $arrColumns;
    }

    /**
     * Generic statements format.
     *
     * @param string $strField    Column to format
     * @param mixed  $varValue    Value to use
     * @param string $strOperator Operator to use, default "="
     */
    public static function formatStatement(string $strField, $varValue, string $strOperator = '='): array
    {
        $arrColumns = [];
        $t = static::$strTable;
        switch ($strField) {
            // Search by feed
            case 'feed':
                if (\is_array($varValue)) {
                    $arrColumns[] = $t . '.feed IN(' . implode(',', array_map(intval(...), $varValue)) . ')';
                } else {
                    $arrColumns[] = $t . '.feed = ' . (int) $varValue;
                }

                break;

                // Search by conditions (value needs to be an array)
            case 'conditions':
                foreach ($varValue as $c => $v) {
                    // The conditions come from the frontend, keep the query safe
                    if (preg_match('/^\w+$/', (string) $c) !== 1) {
                        continue;
                    }

                    $arrColumns[] = \sprintf(
                        "%s.id IN (SELECT twoac.pid FROM tl_wem_offer_alert_condition AS twoac WHERE twoac.field = '%s' AND twoac.value = '%s')",
                        $t,
                        $c,
                        addslashes(\is_array($v) ? serialize($v) : (string) $v),
                    );
                }

                break;

                // Active alert means activatedAt > 0
            case 'active':
                if ($varValue === 1) {
                    $arrColumns[] = $t . '.activatedAt > 0';
                } elseif ($varValue === 0) {
                    $arrColumns[] = $t . '.activatedAt = 0';
                }

                break;

                // Load parent
            default:
                $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
        }

        return $arrColumns;
    }
}
