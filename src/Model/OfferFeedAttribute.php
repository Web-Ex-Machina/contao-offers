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
     * @param array $arrConfig
     * @param int $intLimit
     * @param int $intOffset
     * @param array $arrOptions
     *
     * @return Model|Collection|null
     * @throws \Exception
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

        if (empty($arrColumns)) {
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
                    $arrColumns[] = $t . '.pid IN('.implode(',', array_map('\intval', $varValue)).')';
                } else {
                    $arrColumns[] = $t.'.pid = '.$varValue;
                }

            break;

            // Search by name
            case 'name':
                if (\is_array($varValue)) {
                    $arrColumns[] = $t . ".name IN('".implode("','", $varValue)."')";
                } else {
                    $arrColumns[] = $t.'.name = "'.$varValue.'"';
                }

            break;
        }

        return $arrColumns;
    }
}
