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

use Exception;
use Contao\File;
use Contao\FilesModel;
use Psr\Log\LoggerInterface;
use WEM\OffersBundle\Model\Offer;
use WEM\UtilsBundle\Classes\StringUtil;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StoreFormDataListener
{
    private LoggerInterface $logger;

    private SessionInterface $session;

    public function __construct(SessionInterface $session,LoggerInterface $logger)
    {
        $this->session = $session;
        $this->logger = $logger;
    }

    public function storeFormData($arrSet, $objForm): ?array
    {
        try {
            if ('offer-application' === $objForm->formID) {
                // Unset fields who are not in tl_wem_offer_application table
                $strCode = $arrSet['code'];
                unset($arrSet['recipient'], $arrSet['code'], $arrSet['title'], $arrSet['fdm[first_appearance]'], $arrSet['fdm[first_interaction]'], $arrSet['fdm[current_page]'], $arrSet['fdm[current_page_url]'], $arrSet['fdm[referer_page_url]']);

                $arrSet['pid'] = null;

                if ($objOffer = Offer::findBy('code', $strCode)) {
                    $arrSet['pid'] = $objOffer->next()->current()->id;
                } elseif ($objOffer = Offer::findBy('code', htmlspecialchars($strCode))) {
                    $arrSet['pid'] = $objOffer->next()->current()->id;
                } else {
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
                $objSession = $this->session;
                $objSession->set('wem_offer', '');
            }

            return $arrSet;
        } catch (Exception $exception) {
            $this->logger->log('ERROR',vsprintf(($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['generic'])?:"coucou", [$exception->getMessage(), $exception->getTrace()]),["WEM_OFFERS"]);
        }

        return null;
    }
}
