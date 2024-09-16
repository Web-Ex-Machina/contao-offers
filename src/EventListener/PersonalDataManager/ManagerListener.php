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

namespace WEM\OffersBundle\EventListener\PersonalDataManager;

use Contao\System;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use WEM\OffersBundle\Model\Application;
use WEM\PersonalDataManagerBundle\Service\PersonalDataManager;
use WEM\ContaoFormDataManagerBundle\Model\FormStorage;
use WEM\PersonalDataManagerBundle\Service\PersonalDataManagerUi;

class ManagerListener
{
    /** @var personalDataManagerUi */
    protected PersonalDataManager $personalDataManager;

    private CsrfTokenManagerInterface $csrfTokenManager;

    private string $csrfTokenName;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        string $csrfTokenName,
        PersonalDataManager $personalDataManager
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->personalDataManager = $personalDataManager;
    }

    public function getHrefByPidAndPtableAndEmail(int $pid, string $ptable, string $email, string $href): string
    {
        switch ($ptable) {
            case Application::getTable():
                $href = sprintf('%s?do=wem-offers&table=tl_wem_offer_application&id=%s&act=edit&rt=%s', System::getContainer()->getParameter('contao.backend.route_prefix'), $pid, $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue());
            break;
            case FormStorage::getTable():
                $href = sprintf('%s?do=form&table=tl_sm_form_storage&id=%s&act=edit&rt=%s', System::getContainer()->getParameter('contao.backend.route_prefix'), $pid, $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue());
            break;
        }

        return $href;
    }
}
