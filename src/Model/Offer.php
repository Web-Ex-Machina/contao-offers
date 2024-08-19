<?php

declare(strict_types=1);

/**
 * Contao Offers for Contao Open Source CMS
 * Copyright (c) 2018-2024 Web ex Machina.
 *
 * @category ContaoBundle
 *
 * @author   Web ex Machina <contact@webexmachina.fr>
 *
 * @see     https://github.com/Web-Ex-Machina/contao-offers/
 */

namespace WEM\OffersBundle\Model;

use Contao\Model\Registry;

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
    public static $arrSearchFields = ['code', 'title', 'teaser'];

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
    public static function findItems($arrConfig = [], $intLimit = 0, $intOffset = 0, $arrOptions = []): ?\Contao\Model\Collection
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
    public static function countItems($arrConfig = [], $arrOptions = []): int
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
    public static function formatColumns($arrConfig): array
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
    public static function formatStatement($strField, $varValue, $strOperator = '='): array
    {
        try {
            $arrColumns = [];
            $t = static::$strTable;
            \Controller::loadDatacontainer($t);

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

                // Wizard for active items
                case 'published':
                    if (1 === $varValue) {
                        $arrColumns[] = "$t.published = 1 AND ($t.start = 0 OR $t.start <= ".time().") AND ($t.stop = 0 OR $t.stop >= ".time().')';
                    } elseif (-1 === $varValue) {
                        $arrColumns[] = "$t.published = '' AND ($t.start = 0 OR $t.start >= ".time().") AND ($t.stop = 0 OR $t.stop <= ".time().')';
                    }
                break;

                // Load parent
                default:
                    if (array_key_exists($strField, $GLOBALS['TL_DCA'][$t]['fields'])) {
                        switch ($GLOBALS['TL_DCA'][$t]['fields'][$strField]['inputType']) {
                            case 'select':
                                if ($GLOBALS['TL_DCA'][$t]['fields'][$strField]['eval']['multiple']) {
                                    $varValue = !is_array($varValue) ? [$varValue] : $varValue;
                                    $arrSubColumns = [];

                                    foreach ($varValue as $subValue) {
                                        $arrSubColumns[] = sprintf("$t.$strField LIKE '%%;s:%s:\"%s\";%%'", strlen($subValue), $subValue);
                                    }

                                    $arrColumns[] = '('.implode(' OR ', $arrSubColumns).')';
                                } else {
                                    $arrColumns[] = "$t.$strField = '$varValue'";
                                }
                            break;

                            case 'listWizard':
                                $varValue = !is_array($varValue) ? [$varValue] : $varValue;
                                $arrSubColumns = [];
                                foreach($varValue as $subValue){
                                    $arrSubColumns[] = sprintf("$t.$strField LIKE '%%;s:%s:\"%s\";%%'",strlen($subValue),$subValue);
                                }
                                $arrColumns[] = '('.implode(' AND ', $arrSubColumns).')';
                            break;

                            default:
                                $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                        }
                    } else {
                        $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                    }
            }

            return $arrColumns;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Find a single record by its ID or code
     *
     * @param mixed $varId      The ID or code
     * @param array $arrOptions An optional options array
     *
     * @return static The model or null if the result is empty
     */
    public static function findByIdOrCode($varId, array $arrOptions=array())
    {
        $isCode = !preg_match('/^[1-9]\d*$/', $varId);

        // Try to load from the registry
        if (!$isCode && empty($arrOptions))
        {
            $objModel = Registry::getInstance()->fetch(static::$strTable, $varId);

            if ($objModel !== null)
            {
                return $objModel;
            }
        }

        $t = static::$strTable;

        $arrOptions = array_merge
        (
            array
            (
                'limit'  => 1,
                'column' => $isCode ? array("$t.code=?") : array("$t.id=?"),
                'value'  => $varId,
                'return' => 'Model'
            ),
            $arrOptions
        );

        return static::find($arrOptions);
    }

    /**
     * Get offer attributes as array
     * @return array ['attribute_name'=>['label'=>$label, 'raw_value'=>$value,'human_readable_value'=>$human_readable_value]]
     */
    public function getAttributesFull($varAttributes = []): array
    {
        $attributes = [];

        $objAttributes = OfferFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttributes]);

        if ($objAttributes && 0 < $objAttributes->count()) {
            $arrArticleData = $this->row();
            while ($objAttributes->next()) {
                if (array_key_exists($objAttributes->name, $arrArticleData)) {
                    $varValue = $this->getAttributeValue($objAttributes->current());

                    $attributes[$objAttributes->name] = [
                        'label' => $objAttributes->label,
                        'raw_value' => $varValue,
                        'human_readable_value' => $varValue
                    ];
                }
            }
        }

        return $attributes;
    }

    /**
     * Get offer attributes as array
     * @return array ['attribute_label'=>$human_readable_value,...]
     */
    public function getAttributesSimple($varAttributes = []): array
    {
        $attributes = [];

        $objAttributes = OfferFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttributes]);

        if ($objAttributes && 0 < $objAttributes->count()) {
            $arrArticleData = $this->row();
            while ($objAttributes->next()) {
                if (array_key_exists($objAttributes->name, $arrArticleData)) {
                    $attributes[$objAttributes->name] = $this->getAttributeValue($objAttributes->current());
                }
            }
        }

        return $attributes;
    }

    public function getAttributeValue($varAttribute)
    {
        if ("string" === gettype($varAttribute)) {
            $varAttribute = OfferFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttribute], 1);
        }

        switch($varAttribute->type) {
            case "select":
                $arrArticleData = $this->row();
                $options = deserialize($varAttribute->options ?? []);

                if ($varAttribute->multiple) {
                    $arrArticleData[$varAttribute->name] = deserialize($arrArticleData[$varAttribute->name]);
                    $return = [];
                }

                foreach ($options as $option) {
                    if ($varAttribute->multiple && is_array($arrArticleData[$varAttribute->name]) && in_array($option['value'], $arrArticleData[$varAttribute->name])) {
                        $return[] = $option['label'];
                    } else if(!$varAttribute->multiple && $option['value'] === $arrArticleData[$varAttribute->name]) {
                        $return = $option['label'];
                    }
                }

                if ($varAttribute->multiple) {
                    $return = implode(", ", $return);
                }

                return $return;
            break;

            case "picker":
                return $this->getRelated($varAttribute->name);
            break;

            case "fileTree":
                $figureBuilder = \System::getContainer()
                    ->get('contao.image.studio')
                    ->createFigureBuilder()
                    ->setSize($this->size)
                    ->setLightboxGroupIdentifier('lb' . $this->id)
                    ->enableLightbox((bool) $this->fullsize);

                if ($varAttribute->multiple) {
                    $objFiles = \FilesModel::findMultipleByUuids(\StringUtil::deserialize($this->{$varAttribute->name}));

                    if (!$objFiles) {
                        return null;
                    }

                    $arrFiles = [];
                    while ($objFiles->next()) {
                        $figure = $figureBuilder
                            ->fromPath($objFiles->path)
                            ->build();

                        $arrFiles[] = $figure->getLegacyTemplateData();
                    }

                    return $arrFiles ?: null;
                }

                $objFile = \FilesModel::findByUuid($this->{$varAttribute->name});

                $figure = $figureBuilder
                    ->fromPath($objFile->path)
                    ->build();

                return $figure->getLegacyTemplateData() ?: null;
            break;

            case "listWizard":
                return $this->{$varAttribute->name} ? implode(',',deserialize($this->{$varAttribute->name})) : '';
            break;

            default:
                return $this->{$varAttribute->name};
        }
    }
}
