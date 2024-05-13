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
    protected static array $personalDataFieldsNames = [
        'firstname',
        'lastname',
        'phone',
        'street',
        'postal',
        'city',
        'country',
        'comments',
    ];

    protected static array $personalDataFieldsDefaultValues = [
        'firstname' => 'managed_by_pdm',
        'lastname' => 'managed_by_pdm',
        'phone' => 'nc',
        'street' => 'managed_by_pdm',
        'postal' => 'nc',
        'city' => 'managed_by_pdm',
        'country' => '0',
        'comments' => 'managed_by_pdm',
    ];

    protected static array $personalDataFieldsAnonymizedValues = [
        'firstname' => 'anonymized',
        'lastname' => 'anonymized',
        'phone' => 'anonymized',
        'street' => 'anonymized',
        'postal' => 'anonymized',
        'city' => 'anonymized',
        'country' => '0',
        'comments' => 'anonymized',
    ];

    protected static string $personalDataPidField = 'id';

    protected static string $personalDataEmailField = 'email';

    protected static string $personalDataPtable = 'tl_wem_offer_application';

    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_offer_application';

    /**
     * Find items, depends on the arguments.
     *
     * @return Model|Model[]|Model\Collection
     */
    public static function findItems(array $arrConfig = [], int $intLimit = 0, int $intOffset = 0, array $arrOptions = [])
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
            $arrOptions['order'] = $t . '.tstamp DESC';
        }

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
     * @return array [Array] [The Model columns]
     */
    public static function formatColumns(array $arrConfig): array
    {
        $arrColumns = [];

        if ($arrConfig['not']) {
            $arrColumns[] = $arrConfig['not'];
        }

        return $arrColumns;
    }
}
