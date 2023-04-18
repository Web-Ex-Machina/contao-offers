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

/**
 * Reads and writes items.
 */
class Offer extends \WEM\UtilsBundle\Model\Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_offer';

    /**
     * Search fields.
     *
     * @var array
     */
    public static $arrSearchFields = ['code', 'title', 'text'];

    /**
     * Find items, depends on the arguments.
     *
     * @param array
     * @param int
     * @param int
     * @param array
     *
     * @return Collection
     */
    public static function findItems($arrConfig = [], $intLimit = 0, $intOffset = 0, $arrOptions = [])
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
            $arrOptions['order'] = "$t.createdAt DESC";
        }

        if (empty($arrColumns)) {
            return static::findAll($arrOptions);
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Count items, depends on the arguments.
     *
     * @param array
     * @param array
     *
     * @return int
     */
    public static function countItems($arrConfig = [], $arrOptions = [])
    {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if (empty($arrColumns)) {
            return static::countAll($arrOptions);
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }

    /**
     * Format ItemModel columns.
     *
     * @param [Array] $arrConfig [Configuration to format]
     *
     * @return [Array] [The Model columns]
     */
    public static function formatColumns($arrConfig)
    {
        try {
            $t = static::$strTable;
            $arrColumns = [];

            foreach ($arrConfig as $c => $v) {
                $arrColumns = array_merge($arrColumns, static::formatStatement($c, $v));
            }

            return $arrColumns;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Generic statements format.
     *
     * @param string $strField    [Column to format]
     * @param mixed  $varValue    [Value to use]
     * @param string $strOperator [Operator to use, default "="]
     *
     * @return array
     */
    public static function formatStatement($strField, $varValue, $strOperator = '=')
    {
        try {
            $arrColumns = [];
            $t = static::$strTable;

            switch ($strField) {
                // Search by pid
                case 'pid':
                    if (\is_array($varValue)) {
                        $arrColumns[] = "$t.pid IN(".implode(',', array_map('\intval', $varValue)).')';
                    } else {
                        $arrColumns[] = $t.'.pid = '.$varValue;
                    }
                break;

                // Search by country
                case 'country':
                    $arrColumns[] = "$t.countries LIKE '%%".$varValue."%'";
                break;

                // Search for recipient not present in the subtable lead
                case 'published':
                    if (1 === $varValue) {
                        $time = \Date::floorToMinute();
                        $arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'".($time + 60)."') AND $t.published='1'";
                    }
                break;

                // Load parent
                default:
                    $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
            }

            return $arrColumns;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
