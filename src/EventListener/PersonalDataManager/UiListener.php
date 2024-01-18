<?php

namespace WEM\OffersBundle\EventListener\PersonalDataManager;

use Symfony\Contracts\Translation\TranslatorInterface;
use WEM\OffersBundle\Model\Application;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Classes\FileUtil;
use WEM\PersonalDataManagerBundle\Model\PersonalData;
use WEM\PersonalDataManagerBundle\Service\PersonalDataManagerUi;
use Contao\System;
use Contao\Date;
use Contao\Config;
use Contao\Model;
use Contao\File;
use Contao\FilesModel;
use Contao\Validator;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UiListener
{	
    /** @var TranslatorInterface */
    protected $translator;

    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;

    /** @var string */
    private $csrfTokenName;

    /** @var personalDataManagerUi */
    protected $personalDataManagerUi;

    public function __construct(
        TranslatorInterface $translator,
        CsrfTokenManagerInterface $csrfTokenManager,
        string $csrfTokenName,
        personalDataManagerUi $personalDataManagerUi
    ) {
        $this->translator = $translator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->personalDataManagerUi = $personalDataManagerUi;
    }

    public function renderSingleItemTitle(int $pid, string $ptable, string $email, array $personalDatas, Model $originalModel, string $buffer): string
    {
        switch ($ptable) {
            case Application::getTable():
                $buffer = $this->translator->trans('WEM.OFFERS.PDMUI.offerApplicationHeaderTitle', [], 'contao_default');

            break;
        }

        return $buffer;
    }

    public function buildSingleItemButtons(int $pid, string $ptable, string $email, $nothing, array $personalDatas, Model $originalModel, array $buttons): array
    {
        switch ($ptable) {
            case Application::getTable():
                $buttons['show'] = sprintf(
                    '<a href="%s" title="%s" class="pdm-button pdm-button_show pdm-item__button_show">%s</a>',
                    sprintf('%s?do=wem-offers&table=tl_wem_offer_application&id=%s&act=edit&rt=%s',System::getContainer()->getParameter('contao.backend.route_prefix'),$pid, $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue()),
                    $this->translator->trans('WEM.OFFERS.PDMUI.offerApplicationHeaderButtonShowTitle', [], 'contao_default'),
                    $this->translator->trans('WEM.OFFERS.PDMUI.offerApplicationHeaderButtonShow', [], 'contao_default')
                );
            break;
        }

        return $buttons;
    }

    public function renderSingleItemBodyOriginalModelSingle(int $pid, string $ptable, string $email, string $field, $value, array $personalDatas, Model $originalModel, string $buffer): string
    {
        switch ($ptable) {
            case Application::getTable():
                switch ($field) {
                    case 'id':
                    case 'tstamp':
                        $buffer = '';
                    break;
                }
            break;
        }

        return $buffer;
    }

    public function renderSingleItemBodyOriginalModelSingleFieldValue(int $pid, string $ptable, string $email, string $field, $value, array $personalDatas, Model $originalModel, string $buffer): string
    {
        switch ($ptable) {
            case Application::getTable():
                switch ($field) {
                    case 'pid':
                        $objOffer = Offer::findOneBy('id', $value);
                        $buffer = sprintf(
                            '<a href="%s" title="%s">[%s] %s</a>',
                            sprintf('%s?do=wem-offers&table=tl_wem_offer&id=%s&act=edit&rt=%s',System::getContainer()->getParameter('contao.backend.route_prefix'),$pid, $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue()),
                            $this->translator->trans('WEM.OFFERS.PDMUI.offerApplicationOfferLinkShowTitle', [], 'contao_default'),
                            $objOffer->code,
                            $objOffer->title
                        );
                    break;
                    case 'status':
                    	$buffer = $this->translator->trans('tl_wem_offer_application.status.'.$value,[],'contao_default');
                    break;
                    case 'createdAt':
                        $buffer = Date::parse(Config::get('datimFormat'), (int) $value);
                    break;
                    case 'cv':
                    case 'applicationLetter':
                        // if (Validator::isStringUuid($buffer)) { // for an unknown reason, the $buffer isn't considered as a UUID
                        if (!empty($buffer)) {
                            $objFileModel = FilesModel::findByUuid($buffer);
                            if (!$objFileModel) {
                                $buffer = $this->translator->trans('WEM.OFFERS.PDMUI.fileNotFound', [], 'contao_default');
                            } else {
                                $buffer = $objFileModel->name;
                            }
                        }else{
                            $buffer = $this->translator->trans('WEM.OFFERS.PDMUI.noFileUploaded', [], 'contao_default');
                        }
                    break;
                }
            break;
        }

        return $buffer;
    }

    public function renderSingleItemBodyPersonalDataSingleFieldValue(int $pid, string $ptable, string $email, PersonalData $personalData, array $personalDatas, Model $originalModel, string $buffer): string
    {
    	switch ($ptable) {
            case Application::getTable():
                switch ($personalData->field) {
                    case 'cv':
                    case 'applicationLetter':
                        $buffer = 'A file'; // @todo : update when those file will be tagged as containing personal data
                    break;
                }
            break;
        }

        return $buffer;
    }

    public function renderSingleItemBodyPersonalDataSingleFieldLabel(int $pid, string $ptable, string $email, PersonalData $personalData, array $personalDatas, Model $originalModel, string $buffer): string
    {
        switch ($ptable) {
            case Application::getTable():
                $buffer = $personalData->field_label ?? $buffer;
            break;
        }

        return $buffer;
    }

    public function renderSingleItemBodyPersonalDataSingle(int $pid, string $ptable, string $email, PersonalData $personalData, array $personalDatas, Model $originalModel, string $buffer): string
    {
        switch ($ptable) {
            case Application::getTable():
                $buffer = $this->personalDataManagerUi->formatSingleItemBodyPersonalDataSingle((int) $personalData->pid, $personalData->ptable, $email, $personalData, $personalDatas, $originalModel);
            break;
        }

        return $buffer;
    }

    public function buildSingleItemBodyPersonalDataSingleButtons(int $pid, string $ptable, string $email, PersonalData $personalData, array $personalDatas, Model $originalModel, ?File $file, array $buttons): array
    {
        if ($file) {
            if (FileUtil::isDisplayableInBrowser($file) && !array_key_exists('show',$buttons)) {
                $buttons['show'] = sprintf('<br /><a href="%s" class="pdm-button pdm-button_show_file pdm-item__personal_data_single__button_show_file" target="_blank" data-path="%s">%s</a>',
                                            $this->personalDataManagerUi->getUrl(),
                                            $file->path,
                                            $this->translator->trans('WEM.OFFERS.PDMUI.buttonShowFile', [], 'contao_default')
                                        );
            }
            if(!array_key_exists('download',$buttons)){
	            $buttons['download'] = sprintf('<br /><a href="%s" class="pdm-button pdm-button_download_file pdm-item__personal_data_single__button_download_file" target="_blank" data-path="%s">%s</a>',
	                                            $this->personalDataManagerUi->getUrl(),
	                                            $file->path,
	                                            $this->translator->trans('WEM.OFFERS.PDMUI.buttonDownloadFile', [], 'contao_default')
	                                        );
	        }
        }

        return $buttons;
    }
}