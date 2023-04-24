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

use Exception;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\Session;
use Contao\File;
use Contao\System;
use WEM\OffersBundle\Model\Offer;

class StoreFormDataHook
{
    public function storeFormData($arrSet, $objForm)
    {
        try {
            if ('offer-application' === $objForm->formID) {
                // Unset fields who are not in tl_wem_offer_application table
                $strCode = $arrSet['code'];
                unset($arrSet['recipient'], $arrSet['code'], $arrSet['title'], $arrSet['fdm[first_appearance]'], $arrSet['fdm[first_interaction]'], $arrSet['fdm[current_page]'], $arrSet['fdm[current_page_url]'], $arrSet['fdm[referer_page_url]']);

                $arrSet['pid'] = null;

                // @todo : receive "Annonce WEM Test #2" when in DB is stored as "Annonce WEM Test &#35;2"
                if ($objOffer = Offer::findBy('code', $strCode)) {
                    $arrSet['pid'] = $objOffer->next()->current()->id;
                // }elseif($objOffer = Offer::findBy('code', html_entity_decode($strCode))){
                //     $arrSet['pid'] = $objOffer->next()->current()->id;
                // }elseif($objOffer = Offer::findBy('code', htmlentities($strCode))){
                //     $arrSet['pid'] = $objOffer->next()->current()->id;
                // }elseif($objOffer = Offer::findBy('code', htmlspecialchars($strCode))){
                //     $arrSet['pid'] = $objOffer->next()->current()->id;
                // }elseif($objOffer = Offer::findBy('code', mb_convert_encoding($strCode,'UTF-8'))){
                //     $arrSet['pid'] = $objOffer->next()->current()->id;
                // }elseif($objOffer = Offer::findBy('code', \Contao\StringUtil::specialchars($strCode))){
                //     $arrSet['pid'] = $objOffer->next()->current()->id;
                // }elseif($objOffer = Offer::findBy('code', \Contao\StringUtil::specialcharsUrl($strCode))){
                    // $arrSet['pid'] = $objOffer->next()->current()->id;
                }elseif($objOffer = Offer::findBy('code', str_replace('#','&#35;',$strCode))){
                    $arrSet['pid'] = $objOffer->next()->current()->id;
                }else{
                    throw new Exception('Unable to retrieve offer');
                }

                // do something for countries
                $arrSet['country'] = strtolower($arrSet['country']);
                $arrSet['createdAt'] = time();

                // Convert files path into uuid
                if ($arrSet['cv'] && $objFile = FilesModel::findOneByPath($arrSet['cv'])) {
                    $arrSet['cv'] = $objFile->uuid;

                    // Move file into a subfolder with a clearer name
                    // Rule: {form_folder}/{offer_code}/{cv_lastname_firtname}
                    $strNewName = sprintf(
                        '%s/cv_%s_%s_%s.%s',
                        $strCode,
                        StringUtil::generateAlias($arrSet['lastname']),
                        StringUtil::generateAlias($arrSet['firstname']),
                        date('Y-m-d_H-i'),
                        $objFile->extension
                    );
                    $strFilename = str_replace($objFile->name, $strNewName, $objFile->path);

                    $objFile = new File($objFile->path);
                    $objFile->renameTo($strFilename);
                }
                if ($arrSet['applicationLetter'] && $objFile = FilesModel::findOneByPath($arrSet['applicationLetter'])) {
                    $arrSet['applicationLetter'] = $objFile->uuid;

                    // Move file into a subfolder with a clearer name
                    // Rule: {form_folder}/{offer_code}/{cv_lastname_firtname}
                    $strNewName = sprintf(
                        '%s/al_%s_%s_%s.%s',
                        $strCode,
                        StringUtil::generateAlias($arrSet['lastname']),
                        StringUtil::generateAlias($arrSet['firstname']),
                        date('Y-m-d_H-i'),
                        $objFile->extension
                    );
                    $strFilename = str_replace($objFile->name, $strNewName, $objFile->path);

                    $objFile = new File($objFile->path);
                    $objFile->renameTo($strFilename);
                }

                // Clean the session
                $objSession = Session::getInstance();
                $objSession->set('wem_offer', '');
            }

            return $arrSet;
        } catch (Exception $e) {
            // @todo Translate error message
            System::log(vsprintf('Exception lancée avec le message %s et la trace %s', [$e->getMessage(), $e->getTrace()]), __METHOD__, 'WEM_OFFERS');
        }
    }
}
