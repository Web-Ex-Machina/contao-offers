<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina.
 *
 * @category ContaoBundle
 *
 * @author   Web ex Machina <contact@webexmachina.fr>
 *
 * @see     https://github.com/Web-Ex-Machina/contao-job-offers/
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
     * @param array $arrConfig
     * @param int $intLimit
     * @param int $intOffset
     * @param array $arrOptions
     *
     * @return Model|Model[]|Collection
     */
    public static function findItems(
        array $arrConfig = [], int $intLimit = 0,
        int $intOffset = 0, array $arrOptions = []
    ): ?Collection
    {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if ($intLimit > 0) {
            $arrOptions['limit'] = $intLimit;
        }

        if ($intOffset > 0) {
            $arrOptions['offset'] = $intOffset;
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = $t . '.createdAt DESC';
        }

        if ($arrColumns === []) {
            return static::findAll($arrOptions);
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Count items, depends on the arguments.
     *
     * @param array $arrConfig
     * @param array $arrOptions
     * @return int
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
     * @param array $arrConfig
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
                    $arrColumns[] = $t . '.feed IN('.implode(',', array_map('\intval', $varValue)).')';
                } else {
                    $arrColumns[] = $t.'.feed = '.$varValue;
                }

            break;

            // Search by conditions (value needs to be an array)
            case 'conditions':
                foreach ($varValue as $c => $v) {
                    $arrColumns[] = sprintf(
                        sprintf("%s.id IN (SELECT twoac.pid FROM tl_wem_offer_alert_condition AS twoac WHERE twoac.field = '%%s' AND twoac.value = '%%s')", $t),
                        $c,
                        $v
                    );
                }

            break;

            // Active alert means activatedAt > 0
            case 'active':
                if (1 === $varValue) {
                    $arrColumns[] = $t . '.activatedAt > 0';
                } elseif (0 === $varValue) {
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
