<?php

declare(strict_types=1);

/**
 * Personal Data Manager for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-smartgear
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/personal-data-manager/
 */

namespace WEM\OffersBundle\EventListener;

use Contao\Database;
use Contao\Dbafs;
use Contao\Files;
use Contao\Form;
use Contao\FilesModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use Psr\Log\LogLevel;
use WEM\OffersBundle\Model\Application;
use WEM\UtilsBundle\Classes\Encryption;
use Contao\CoreBundle\Monolog\ContaoContext;
use WEM\OffersBundle\Model\Offer;

class ProcessFormDataListener
{
    protected Encryption $encryption;

    public function __construct(Encryption $encryption)
    {
        $this->encryption = $encryption;
    }

    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        try {
            // If we find the submitted form in the offers modules & it has a PID, we need to process it
            if(0 < ModuleModel::countBy('offer_applicationForm', $form->id)
                && array_key_exists('pid', $submittedData)
                && !empty($submittedData['pid'])
            ) {
                $objOffer = Offer::findByPk($submittedData['pid']);

                // Do not process if the offer has not been found
                // Do not send an Exception though, let the others listeners do their job
                if (!$objOffer) {
                    return;
                }

                $objDb = Database::getInstance();

                // Add the application to the bundle
                $objApplication = new Application();
                $objApplication->createdAt = time();
                $objApplication->tstamp = time();

                // Loop on form data
                foreach ($submittedData as $c => $v) {
                    // Skip if the column does not exist in tl_wem_offer_application
                    if (!$objDb->fieldExists($c, 'tl_wem_offer_application')) {
                        continue;
                    }

                    $objApplication->{$c} = $v;
                }

                $objApplication->save();

                // Loop on files
                if (is_array($files) && $files !== []) {
                    foreach ($files as $name => $file) {
                        // Do not process files we cannot link to the application
                        if (!$objDb->fieldExists($name, 'tl_wem_offer_application')) {
                            continue;
                        }

                        // Process file
                        $objFile = $this->moveFile($name, $file, $objOffer, $objApplication);

                        // Link the file to the application
                        $objApplication->{$name} = $objFile->uuid;
                    }
                }

                // Apply PDM system
                $fieldsManagedByPdm = (new Application())->getPersonalDataFieldsNames();
                foreach ($fieldsManagedByPdm as $field) {
                    $objApplication->markModified($field);
                }

                // Finally save the model again
                $objApplication->save();

                // Clean session
                $objSession = System::getContainer()->get('request_stack')->getSession();
                $objSession->set('wem_offer', '');
            }
        } catch (Exception $exception) {
            System::getContainer()->get('monolog.logger.contao')->log(LogLevel::INFO, vsprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['generic'], [$exception->getMessage(), $exception->getTrace()]), ['contao' => new ContaoContext(__METHOD__, 'WEM_OFFERS')]);
        }
    }

    // Move file into a subfolder with a clearer name
    // Rule: files/applications/{offer_code}/{lastname_firstname}/{lastname_firstname_name}.{extension}
    protected function moveFile($name, array $file, $objOffer, $objApplication): ?FilesModel
    {
        $chunks = explode('.', $file['full_path']);
        $ext = end($chunks);

        $strFolder = sprintf(
            'files/applications/%s/%s_%s',
            $objOffer->code,
            StringUtil::prepareSlug($objApplication->lastname),
            StringUtil::prepareSlug($objApplication->firstname)
        );

        Files::getInstance()->mkdir($strFolder);

        $strFile = sprintf(
            '%s_%s_%s.%s',
            StringUtil::prepareSlug($objApplication->lastname),
            StringUtil::prepareSlug($objApplication->firstname),
            $name,
            $ext
        );

        Files::getInstance()->move_uploaded_file($file['tmp_name'], $strFolder . '/' . $strFile);
        return Dbafs::addResource($strFolder . '/' . $strFile);
    }
}
