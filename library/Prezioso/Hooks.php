<?php

/**
 * Prezioso Extension for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace Prezioso;

use Exception;
use Contao\Input;

use Prezioso\Model\Job as JobModel;

/**
 * Extension functions
 */
class Hooks extends \Controller
{
	public function storeFormData($arrSet, $objForm){
		try{
			if("job-offer-application" == $objForm->formID){
				// Unset fields who are not in tl_pzl_job_application table
				unset($arrSet['recipient']);
				unset($arrSet['code']);
				unset($arrSet['title']);

				// Convert files path into uuid
				if($arrSet['cv'] && $objFile = \FilesModel::findOneByPath($arrSet['cv']))
					$arrSet['cv'] = $objFile->uuid;
				if($arrSet['applicationLetter'] && $objFile = \FilesModel::findOneByPath($arrSet['applicationLetter']))
					$arrSet['applicationLetter'] = $objFile->uuid;
			}
			
			return $arrSet;
		}
		catch(Exception $e){
			\System::log(vsprintf("Exception lancée avec le message %s et la trace %s", [$e->getMessage(), $e->getTrace()]), __METHOD__, "WEM_FCN");
		}
	}
}