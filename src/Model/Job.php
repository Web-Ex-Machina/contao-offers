<?php

namespace WEM\JobOffersBundle\Model;

use Contao\Model;

/**
 * Reads and writes items
 */
class Job extends Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_pzl_job';

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
            $arrOptions['order'] = "$t.createdAt DESC";
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

        if ($arrConfig['title']) {
            $arrColumns[] = $t.'.title = "'.$arrConfig['title'].'"';
        }

        if ($arrConfig['location']) {
            $arrColumns[] = "$t.location = '".$arrConfig['location']."'";
        }

        if ($arrConfig['published'] === 1) {
            $time = \Date::floorToMinute();
            $arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
        }

        if ($arrConfig["not"]) {
            $arrColumns[] = $arrConfig["not"];
        }

        return $arrColumns;
    }
}
