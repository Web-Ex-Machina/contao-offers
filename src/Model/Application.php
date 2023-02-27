<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-job-offers
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\OffersBundle\Model;

use Contao\Model;
use WEM\PersonalDataManagerBundle\Model\Traits\PersonalDataTrait as PDMTrait;

/**
 * Reads and writes items.
 */
class Application extends Model
{
    use PDMTrait;
    protected static $personalDataFieldsNames = [
        'firstname',
        'lastname',
        'phone',
        'street',
        'postal',
        'city',
        'country',
        'comments',
    ];
    protected static $personalDataFieldsDefaultValues = [
        'firstname' => 'managed_by_pdm',
        'lastname' => 'managed_by_pdm',
        'phone' => 'nc',
        'street' => 'managed_by_pdm',
        'postal' => 'nc',
        'city' => 'managed_by_pdm',
        'country' => '0',
        'comments' => 'managed_by_pdm',
    ];
    protected static $personalDataFieldsAnonymizedValues = [
        'firstname' => 'anonymized',
        'lastname' => 'anonymized',
        'phone' => 'anonymized',
        'street' => 'anonymized',
        'postal' => 'anonymized',
        'city' => 'anonymized',
        'country' => '0',
        'comments' => 'anonymized',
    ];
    protected static $personalDataPidField = 'id';
    protected static $personalDataEmailField = 'email';
    protected static $personalDataPtable = 'tl_wem_offer_application';
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_offer_application';

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
            $arrOptions['order'] = "$t.tstamp DESC";
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
        $t = static::$strTable;
        $arrColumns = [];

        if ($arrConfig['not']) {
            $arrColumns[] = $arrConfig['not'];
        }

        return $arrColumns;
    }
}
