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

namespace WEM\JobOffersBundle\Hooks;

class StoreFormDataHook
{
    public function storeFormData($arrSet, $objForm)
    {
        try {
            if ('job-offer-application' === $objForm->formID) {
                // Unset fields who are not in tl_wem_job_application table
                unset($arrSet['recipient'], $arrSet['code'], $arrSet['title']);

                // Convert files path into uuid
                if ($arrSet['cv'] && $objFile = \FilesModel::findOneByPath($arrSet['cv'])) {
                    $arrSet['cv'] = $objFile->uuid;
                }
                if ($arrSet['applicationLetter'] && $objFile = \FilesModel::findOneByPath($arrSet['applicationLetter'])) {
                    $arrSet['applicationLetter'] = $objFile->uuid;
                }

                // Clean the session
                $objSession = \Session::getInstance();
                $objSession->set('wem_job_offer', '');
            }

            return $arrSet;
        } catch (\Exception $e) {
            // @todo Translate error message
            \System::log(vsprintf('Exception lancée avec le message %s et la trace %s', [$e->getMessage(), $e->getTrace()]), __METHOD__, 'WEM_JOBOFFERS');
        }
    }
}
