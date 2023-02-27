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

namespace WEM\OffersBundle\Hooks;

use WEM\OffersBundle\Model\Application;
use Exception;
use Contao\Form;
use Contao\System;

class ProcessFormDataHook
{
    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ) {
        try {
            if ('offer-application' === $form->formID) {
                // get the last submitted application
                $objApplication = Application::findItems([], 1, 0, ['order'=>'tstamp DESC']);
                if ($objApplication) {
                    $objApplication = $objApplication->next()->current();
                    $fieldsManagedByPdm = Application::getPersonalDataFieldsNames();
                    foreach ($fieldsManagedByPdm as $field) {
                        $objApplication->markModified($field);
                    }
                    $objApplication->save();
                }
            }
        } catch (Exception $e) {
            // @todo Translate error message
            System::log(vsprintf('Exception lancée avec le message %s et la trace %s', [$e->getMessage(), $e->getTrace()]), __METHOD__, 'WEM_JOBOFFERS');
        }
    }
}
