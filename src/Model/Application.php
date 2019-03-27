<?php

namespace WEM\JobOffersBundle\Model;

use Contao\Model;

/**
 * Reads and writes items
 */
class Application extends Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_wem_job_application';

    /**
     * Find items, depends on the arguments
     * @param Array
     * @param Int
     * @param Int
     * @param Array
     * @return Collection
     */
    public static function findItems($arrConfig = array(), $intLimit = 0, $intOffset = 0, $arrOptions = array())
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
            $arrOptions['order'] = "$t.tstamp DESC";
        }

        if (empty($arrColumns)) {
            return static::findAll($arrOptions);
        } else {
            return static::findBy($arrColumns, null, $arrOptions);
        }
    }

    /**
     * Count items, depends on the arguments
     * @param Array
     * @param Array
     * @return Integer
     */
    public static function countItems($arrConfig = array(), $arrOptions = array())
    {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if (empty($arrColumns)) {
            return static::countAll($arrOptions);
        } else {
            return static::countBy($arrColumns, null, $arrOptions);
        }
    }

    /**
     * Format ItemModel columns
     * @param  [Array] $arrConfig [Configuration to format]
     * @return [Array]            [The Model columns]
     */
    public static function formatColumns($arrConfig)
    {
        $t = static::$strTable;
        $arrColumns = array();

        if ($arrConfig["not"]) {
            $arrColumns[] = $arrConfig["not"];
        }

        return $arrColumns;
    }
}
