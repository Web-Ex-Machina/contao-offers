<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Model;

use Contao\Controller;
use Contao\Date;
use Contao\FilesModel;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Contao\System;
use WEM\UtilsBundle\Classes\StringUtil;
use WEM\UtilsBundle\Model\Model;

/**
 * Reads and writes items.
 */
class Offer extends Model
{
    /**
     * Search fields.
     */
    public static $arrSearchFields = ['code', 'title', 'teaser'];

    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_offer';

    /**
     * Format ItemModel columns.
     *
     * @return array [The Model columns]
     */
    public static function formatColumns(array $arrConfig): array
    {
        $arrColumns = [];

        // Filter on the current locale unless a language has been given (null means every language)
        if (! \array_key_exists('lang', $arrConfig)) {
            $arrConfig['lang'] = System::getContainer()->get('request_stack')->getCurrentRequest()?->getLocale();
        }

        foreach ($arrConfig as $c => $v) {
            $arrColumns = array_merge($arrColumns, static::formatStatement($c, $v));
        }

        return $arrColumns;
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
        Controller::loadDatacontainer($t);
        switch ($strField) {
            // Search by id
            case 'id':
                if (\is_array($varValue)) {
                    $arrColumns[] = $t . '.id IN(' . implode(',', array_map(intval(...), $varValue)) . ')';
                } else {
                    $arrColumns[] = $t . '.id = ' . (int) $varValue;
                }

                break;

                // Search by pid
            case 'pid':
                if (\is_array($varValue)) {
                    $arrColumns[] = $t . '.pid IN(' . implode(',', array_map(intval(...), $varValue)) . ')';
                } else {
                    $arrColumns[] = $t . '.pid = ' . (int) $varValue;
                }

                break;

                // Search by language, skipped when no language is given
            case 'lang':
                if ($varValue !== null && $varValue !== '') {
                    $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                }

                break;
                // Search by country
            case 'country':
                $arrColumns[] = $t . ".countries LIKE '%%" . $varValue . "%'";
                break;

                // Search for recipient not present in the subtable lead
            case 'published':
                if ($varValue === 1) {
                    $time = Date::floorToMinute();
                    $arrColumns[] = \sprintf("(%s.start='' OR %s.start<='%s') AND (%s.stop='' OR %s.stop>'", $t, $t, $time, $t, $t) . ($time + 60) . \sprintf("') AND %s.published='1'", $t);
                }

                break;

                // Wizard for active items
            case 'active':
                if ($varValue === 1) {
                    $arrColumns[] = \sprintf('%s.published = 1 AND (%s.start = 0 OR %s.start <= ', $t, $t, $t) . time() . \sprintf(') AND (%s.stop = 0 OR %s.stop >= ', $t, $t) . time() . ')';
                } elseif ($varValue === -1) {
                    $arrColumns[] = \sprintf("%s.published = '' AND (%s.start = 0 OR %s.start >= ", $t, $t, $t) . time() . \sprintf(') AND (%s.stop = 0 OR %s.stop <= ', $t, $t) . time() . ')';
                }

                break;

                // Load parent
            default:
                if (\array_key_exists($strField, $GLOBALS['TL_DCA'][$t]['fields'])) {
                    switch ($GLOBALS['TL_DCA'][$t]['fields'][$strField]['inputType']) {
                        case 'select':
                            if ($GLOBALS['TL_DCA'][$t]['fields'][$strField]['eval']['multiple'] ?? false) {
                                $varValue = \is_array($varValue) ? $varValue : [$varValue];
                                $arrSubColumns = [];

                                foreach ($varValue as $subValue) {
                                    $arrSubColumns[] = \sprintf(\sprintf('%s.%s LIKE \'%%%%;s:%%s:"%%s";%%%%\'', $t, $strField), \strlen($subValue), $subValue);
                                }

                                $arrColumns[] = '(' . implode(' OR ', $arrSubColumns) . ')';
                            } else {
                                $arrColumns[] = \sprintf("%s.%s = '%s'", $t, $strField, $varValue);
                            }

                            break;

                        case 'listWizard':
                            $varValue = \is_array($varValue) ? $varValue : [$varValue];
                            $arrSubColumns = [];
                            foreach ($varValue as $subValue) {
                                $arrSubColumns[] = \sprintf(\sprintf('%s.%s LIKE \'%%%%;s:%%s:"%%s";%%%%\'', $t, $strField), \strlen($subValue), $subValue);
                            }

                            $arrColumns[] = '(' . implode(' AND ', $arrSubColumns) . ')';
                            break;

                        default:
                            $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                    }
                } else {
                    $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                }
        }

        return $arrColumns;
    }

    /**
     * Find a single record by its ID or code.
     *
     * @param int|string $varId      The ID or code
     * @param array $arrOptions An optional options array
     *
     * @return static|null The model or null if the result is empty
     */
    public static function findByIdOrCode(int|string $varId, array $arrOptions = []): static|null
    {
        $isCode = ! preg_match('/^[1-9]\d*$/', (string) $varId);

        // Try to load from the registry
        if (! $isCode && $arrOptions === []) {
            $objModel = Registry::getInstance()->fetch(static::$strTable, (int) $varId);

            if ($objModel instanceof static) {
                return $objModel;
            }
        }

        $t = static::$strTable;

        $arrOptions = array_merge(
            [
                'limit' => 1,
                'column' => $isCode ? [$t . '.code=?'] : [$t . '.id=?'],
                'value' => $varId,
                'return' => 'Model',
            ],
            $arrOptions
        );

        $objModel = static::find($arrOptions);

        return $objModel instanceof static ? $objModel : null;
    }

    /**
     * Get offer attributes as array.
     *
     * @throws \Exception
     *
     * @return array ['attribute_name'=>['label'=>$label, 'raw_value'=>$value,'human_readable_value'=>$human_readable_value]]
     */
    public function getAttributesFull($varAttributes = []): array
    {
        $attributes = [];

        $objAttributes = OfferFeedAttribute::findItems(array_filter([
            'pid' => $this->pid,
            'name' => $varAttributes,
        ]));

        if ($objAttributes instanceof \Contao\Model\Collection && $objAttributes->count() > 0) {
            $arrArticleData = $this->row();
            while ($objAttributes->next()) {
                if (\array_key_exists($objAttributes->name, $arrArticleData)) {
                    $varValue = $this->getAttributeValue($objAttributes->current());

                    $attributes[$objAttributes->name] = [
                        'label' => $objAttributes->label,
                        'raw_value' => $varValue,
                        'human_readable_value' => $varValue,
                    ];
                }
            }
        }

        return $attributes;
    }

    /**
     * Get offer attributes as array.
     *
     * @throws \Exception
     *
     * @return array ['attribute_label'=>$human_readable_value,...]
     */
    public function getAttributesSimple($varAttributes = []): array
    {
        $attributes = [];

        $objAttributes = OfferFeedAttribute::findItems(array_filter([
            'pid' => $this->pid,
            'name' => $varAttributes,
        ]));

        if ($objAttributes instanceof \Contao\Model\Collection && $objAttributes->count() > 0) {
            $arrArticleData = $this->row();
            while ($objAttributes->next()) {
                if (\array_key_exists($objAttributes->name, $arrArticleData)) {
                    $attributes[$objAttributes->name] = $this->getAttributeValue($objAttributes->current());
                }
            }
        }

        return $attributes;
    }

    /**
     * TODO : this fonction return too many different value type.
     *
     * @throws \Exception
     *
     * @return array|Collection|mixed|string|Offer|null
     */
    public function getAttributeValue($varAttribute)
    {
        if (\is_string($varAttribute)) {
            $varAttribute = OfferFeedAttribute::findItems([
                'pid' => $this->pid,
                'name' => $varAttribute,
            ], 1)?->first();
        }

        if ($varAttribute === null) {
            return null;
        }

        switch ($varAttribute->type) {
            case 'select':
                $arrArticleData = $this->row();
                $options = StringUtil::deserialize($varAttribute->options, true);

                $return = $varAttribute->multiple ? [] : '';

                if ($varAttribute->multiple) {
                    $arrArticleData[$varAttribute->name] = StringUtil::deserialize($arrArticleData[$varAttribute->name]);
                }

                foreach ($options as $option) {
                    if ($varAttribute->multiple && \is_array($arrArticleData[$varAttribute->name]) && \in_array($option['value'], $arrArticleData[$varAttribute->name], true)) {
                        $return[] = $option['label'];
                    } elseif (! $varAttribute->multiple && $option['value'] === $arrArticleData[$varAttribute->name]) {
                        $return = $option['label'];
                    }
                }

                if ($varAttribute->multiple) {
                    $return = implode(', ', $return);
                }

                return $return;

            case 'picker':
                return $this->getRelated($varAttribute->name);

            case 'fileTree':
                $figureBuilder = System::getContainer()
                    ->get('contao.image.studio')
                    ->createFigureBuilder()
                    ->setSize($this->size)
                    ->setLightboxGroupIdentifier('lb' . $this->id)
                    ->enableLightbox((bool) $this->fullsize)
                ;

                if ($varAttribute->multiple) {
                    $objFiles = FilesModel::findMultipleByUuids(StringUtil::deserialize($this->{$varAttribute->name}));

                    if (! $objFiles) {
                        return null;
                    }

                    $arrFiles = [];
                    while ($objFiles->next()) {
                        $figure = $figureBuilder
                            ->fromPath($objFiles->path)
                            ->build()
                        ;

                        $arrFiles[] = $figure->getLegacyTemplateData();
                    }

                    return $arrFiles ?: null;
                }

                $objFile = FilesModel::findByUuid($this->{$varAttribute->name});

                if (! $objFile instanceof FilesModel) {
                    return null;
                }

                return $figureBuilder->fromPath($objFile->path)->build()->getLegacyTemplateData() ?: null;

            case 'listWizard':
                return $this->{$varAttribute->name} ? implode(',', StringUtil::deserialize($this->{$varAttribute->name})) : '';

            default:
                return $this->{$varAttribute->name};
        }
    }
}
