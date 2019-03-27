<?php

namespace WEM\JobOffersBundle\Hooks;

class StoreFormDataHook
{
    public function storeFormData($arrSet, $objForm)
    {
        try {
            if ("job-offer-application" == $objForm->formID) {
                // Unset fields who are not in tl_wem_job_application table
                unset($arrSet['recipient']);
                unset($arrSet['code']);
                unset($arrSet['title']);

                // Convert files path into uuid
                if ($arrSet['cv'] && $objFile = \FilesModel::findOneByPath($arrSet['cv'])) {
                    $arrSet['cv'] = $objFile->uuid;
                }
                if ($arrSet['applicationLetter'] && $objFile = \FilesModel::findOneByPath($arrSet['applicationLetter'])) {
                    $arrSet['applicationLetter'] = $objFile->uuid;
                }
            }
            
            return $arrSet;
        } catch (\Exception $e) {
            // @todo Translate error message
            \System::log(vsprintf("Exception lancée avec le message %s et la trace %s", [$e->getMessage(), $e->getTrace()]), __METHOD__, "WEM_JOBOFFERS");
        }
    }
}
