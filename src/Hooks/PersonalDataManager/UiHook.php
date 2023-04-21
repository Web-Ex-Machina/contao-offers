<?php

namespace WEM\OffersBundle\Hooks\PersonalDataManager;

use Symfony\Contracts\Translation\TranslatorInterface;
use WEM\OffersBundle\Model\Application;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Classes\FileUtil;
use WEM\PersonalDataManagerBundle\Model\PersonalData;
use WEM\PersonalDataManagerBundle\Service\PersonalDataManagerUi;
use Contao\Date;
use Contao\Config;
use Contao\Model;
use Contao\File;
use Contao\FilesModel;
use Contao\Validator;

class UiHook{
	
    /** @var TranslatorInterface */
    protected $translator;
    /** @var personalDataManagerUi */
    protected $personalDataManagerUi;

    public function __construct(
        TranslatorInterface $translator,
        personalDataManagerUi $personalDataManagerUi
    ) {
        $this->translator = $translator;
        $this->personalDataManagerUi = $personalDataManagerUi;
    }

    public function renderSingleItemTitle(int $pid, string $ptable, string $email, array $personalDatas, Model $originalModel, string $buffer): string
    {
        switch ($ptable) {
            case Application::getTable():
                $buffer = 'Candidature';
            break;
        }

        return $buffer;
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
                        $objOffer = Offer::findOneBy('id', $pid);
                        // $objFeed = $objOffer->getRelated('pid');
                        $buffer = '['.$objOffer->code.'] '.$objOffer->title;
                    break;
                    case 'status':
                    	$buffer = $this->translator->trans('tl_wem_offer_application.status.'.$value,[],'contao_default');
                    break;
                    case 'createdAt':
                        $buffer = Date::parse(Config::get('datimFormat'), (int) $value);
                    break;
                    case 'cv':
                    case 'applicationLetter':
                    // dump($buffer);
                    // dump(Validator::isStringUuid($buffer));
                        if (Validator::isStringUuid($buffer)) {
                            $objFileModel = FilesModel::findByUuid($buffer);
                            if (!$objFileModel) {
                                $buffer = $this->translator->trans('WEMSG.FDM.PDMUI.fileNotFound', [], 'contao_default');
                            } else {
                                $buffer = $objFileModel->name;
                            }
                        }
                        // $buffer = 'A file';
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