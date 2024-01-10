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

namespace WEM\OffersBundle\Hooks;

use Contao\System;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeedAttribute;

class LoadDataContainerHook
{
    public function addAttributesToJobDca($strTable)
    {
        try {
            if ('tl_wem_offer' === $strTable) {
                // For everytime we load a tl_wem_offer DCA, we want to load all the existing attributes as fields
                $objAttributes = OfferFeedAttribute::findAll();

                if (!$objAttributes || 0 == $objAttributes->count()) {
                    return;
                }

                while ($objAttributes->next()) {
                    $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$objAttributes->name] = $this->parseDcaAttribute($objAttributes->row());
                }
            }
        } catch (\Exception $e) {
            // @todo Translate error message
            System::log(vsprintf('Exception lancée avec le message %s et la trace %s', [$e->getMessage(), $e->getTrace()]), __METHOD__, 'WEM_OFFERS');
        }
    }

    protected function parseDcaAttribute($row)
    {
        // Generic data
        $data = [
            'label' => [0 => $row['label'] ?: $row['name']],
            'name' => $row['name'],
            'inputType' => $row['type'],
            'eval' => [],
            'sql' => ['name' => $row['name']],
        ];

        // Default settings
        if ($row['default']) {
            $data['default'] = $row['default'];
            $data['sql']['default'] = $row['default'];
        }

        // Maxlength settings
        if ($row['maxlength']) {
            $data['eval']['maxlength'] = (int) $row['maxlength'];
            $data['sql']['length'] = (int) $row['maxlength'];
        }

        // Available for alerts settings
        if ($row['wemoffers_isAvailableForAlerts']) {
            $data['eval']['isAlertCondition'] = true;
        }

        // Available for filters settings
        if ($row['wemoffers_isAvailableForFilters']) {
            $data['eval']['isFilter'] = true;
        }

        // Mandatory settings
        if ($row['mandatory']) {
            $data['eval']['mandatory'] = true;
        }

        // Class settings
        if ($row['class']) {
            $data['eval']['tl_class'] = $row['class'];
        }

        switch ($row['type']) {
            case 'text':
                // Allow HTML settings
                if ($row['allowHtml']) {
                    $data['eval']['allowHtml'] = true;
                }

                $data['sql']['type'] = 'string';
                break;

            case 'select':
                $data['sql']['type'] = 'string';

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                }

                // Options
                $options = deserialize($row['options']);

                if (null !== $options) {
                    $data['options'] = [];
                    foreach ($options as $o) {
                        $data['options'][$o['value']] = $o['label'];
                    }
                }
                break;

            case 'picker':
                // Fkey settings
                if ($row['fkey']) {
                    $data['foreignKey'] = $row['fkey'];
                }

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                    $data['sql']['type'] = 'blob';
                    $data['sql']['default'] = 'NULL';
                    $data['relation'] = ['type'=>'hasMany', 'load'=>'lazy'];
                } else {
                    $data['sql'] = 'int(10) unsigned NOT NULL default 0';
                    $data['relation'] = ['type'=>'hasOne', 'load'=>'lazy'];
                }
                break;

            case 'fileTree':
                // filesOnly settings
                if ($row['filesOnly']) {
                    $data['eval']['filesOnly'] = true;
                }

                // extensions settings
                if ($row['fieldType']) {
                    $data['eval']['fieldType'] = $row['fieldType'];
                }

                // extensions settings
                if ($row['extensions']) {
                    $data['eval']['extensions'] = $row['extensions'];
                }

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                    $data['sql']['type'] = 'blob';
                    $data['sql']['default'] = 'NULL';
                } else {
                    $data['sql']['type'] = 'binary';
                    $data['sql']['length'] = 16;
                    $data['sql']['default'] = 'NULL';
                }
                break;

            case 'listWizard':
                // Allow HTML settings
                if ($row['allowHtml']) {
                    $data['eval']['allowHtml'] = true;
                }
                
                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                }

                $data['sql']['type'] = 'blob';
                $data['sql']['default'] = 'NULL';
                break;
        }

        return $data;
    }
}