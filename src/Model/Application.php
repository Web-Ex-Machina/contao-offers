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
     * @return Model|Model\Collection|null
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

        $arrOptions['order'] ??= $t . '.tstamp DESC';

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
     * @return array  The Model columns
     */
    public static function formatColumns(array $arrConfig): array
    {
        $arrColumns = [];

        if (! empty($arrConfig['not'])) {
            $arrColumns[] = $arrConfig['not'];
        }

        return $arrColumns;
    }
}
