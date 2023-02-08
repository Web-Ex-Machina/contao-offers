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

namespace WEM\JobOffersBundle\Hooks;

use WEM\JobOffersBundle\Model\Job;
use WEM\JobOffersBundle\Model\JobFeedAttribute;

class LoadDataContainerHook
{
    public function addAttributesToJobDca($strTable)
    {
        try {
            if ('tl_wem_job' === $strTable) {
                // For everytime we load a tl_wem_job DCA, we want to load all the existing attributes as fields
                $objAttributes = JobFeedAttribute::findAll();

                if (!$objAttributes || 0 == $objAttributes->count()) {
                    return;
                }

                while ($objAttributes->next()) {
                    $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$objAttributes->name] = [
                        'label' => [0 => $objAttributes->label],
                        'default' => $objAttributes->value ?: '',
                        'inputType' => $objAttributes->type,
                        'eval' => [
                            'tl_class' => $objAttributes->class,
                            'mandatory' => $objAttributes->mandatory ? true : false,
                            'wemjoboffers_isAvailableForAlerts' => $objAttributes->isAlertCondition ? true : false,
                            'wemjoboffers_isAvailableForFilters' => $objAttributes->isFilter ? true : false,
                        ],
                        'sql' => ['name' => $objAttributes->name, 'type' => 'string', 'length' => 255, 'default' => $objAttributes->value ?: ''],
                    ];

                    if ('select' == $objAttributes->type) {
                        $options = deserialize($objAttributes->options);

                        if (null !== $options) {
                            $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$objAttributes->name]['options'] = [];
                            foreach ($options as $o) {
                                $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$objAttributes->name]['options'][$o['value']] = $o['label'];
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // @todo Translate error message
            \System::log(vsprintf('Exception lancée avec le message %s et la trace %s', [$e->getMessage(), $e->getTrace()]), __METHOD__, 'WEM_JOBOFFERS');
        }
    }
}
