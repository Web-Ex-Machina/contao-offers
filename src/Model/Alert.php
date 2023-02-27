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
class Alert extends \WEM\UtilsBundle\Model\Model
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
                // Search by feed
                case 'feed':
                    if (\is_array($varValue)) {
                        $arrColumns[] = "$t.feed IN(".implode(',', array_map('\intval', $varValue)).')';
                    } else {
                        $arrColumns[] = $t.'.feed = '.$varValue;
                    }
                break;

                // Search by conditions (value needs to be an array)
                case 'conditions':
                    foreach ($varValue as $c => $v) {
                        $arrColumns[] = sprintf(
                            "$t.id IN (SELECT twjac.pid FROM tl_wem_offer_alert_condition AS twjac WHERE twoac.field = '%s' AND twoac.value = '%s')",
                            $c,
                            $v
                        );
                    }
                break;

                // Active alert means activatedAt > 0
                case 'active':
                    if (1 === $varValue) {
                        $arrColumns[] = "$t.activatedAt > 0";
                    } elseif (0 === $varValue) {
                        $arrColumns[] = "$t.activatedAt = 0";
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
