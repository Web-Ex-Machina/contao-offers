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

namespace WEM\OffersBundle\Module;

use Contao\BackendTemplate;
use Contao\Combiner;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
use Contao\PageModel;
use Contao\RequestToken;
use Contao\Session;
use Contao\System;
use WEM\OffersBundle\Model\Offer as OfferModel;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "offers list".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModuleOffersReader extends ModuleOffers
{
    /**
     * Offer
     * 
     * @var OfferModel
     */
    protected $objOffer = null;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_offersreader';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['FMD']['offersreader'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        $this->offer = OfferModel::findByIdOrCode(Input::get('auto_item'));

        if (!$this->offer) {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        // Init countries
        System::getCountries();

        // Init session
        $objSession = Session::getInstance();

        // If we have setup a form, allow module to use it later
        if ($this->offer_applicationForm) {
            $this->blnDisplayApplyButton = true;
        }

        if ($this->overviewPage)
        {
            $this->Template->referer = PageModel::findById($this->overviewPage)->getFrontendUrl();
            $this->Template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['newsOverview'];
        }

        // Catch Ajax requets
        if (Input::post('TL_AJAX') && (int) $this->id === (int) Input::post('module')) {
            try {
                switch (Input::post('action')) {
                    case 'apply':
                        if (!Input::post('offer')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'offer'));
                        }

                        // Put the offer in session
                        $objSession->set('wem_offer', Input::post('offer'));

                        echo \Haste\Util\InsertTag::replaceRecursively($this->getApplicationForm(Input::post('offer')));
                        exit;
                    break;

                    default:
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['unknownRequest'], Input::post('action')));
                }
            } catch (\Exception $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getResponse(), 'trace' => $e->getTrace()];
            }

            // Add Request Token to JSON answer and return
            $arrResponse['rt'] = RequestToken::get();
            echo json_encode($arrResponse);
            exit;
        }

        if ($this->offer_applicationForm
            && '' !== $objSession->get('wem_offer')
        ) {
            $strForm = $this->getApplicationForm($objSession->get('wem_offer'));

            // Fetch the application form if defined
            if (Input::post('FORM_SUBMIT')) {
                try {
                    $this->Template->openModalOnLoad = true;
                    $this->Template->openModalOnLoadContent = json_encode($strForm);
                } catch (\Exception $e) {
                    $this->Template->openModalOnLoad = true;
                    $this->Template->openModalOnLoadContent = json_encode('"'.$e->getResponse().'"');
                }
            }
        }

        global $objPage;

        $objPage->pageTitle = $this->offer->title . ' | ' . $this->offer->code;
        $objPage->description = StringUtil::substr($this->offer->teaser, 300);

        // assets
        $strVersion = $this->getCustomPackageVersion('webexmachina/contao-offers');
        $objCssCombiner = new Combiner();
        $objCssCombiner->add('bundles/offers/css/styles.scss', $strVersion);

        $GLOBALS['TL_HEAD'][] = sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/offers/js/scripts.js';

        // Add the articles
        $this->Template->offer = $this->parseOffer($this->offer);
        $this->Template->moduleId = $this->id;
    }

    /**
     * Parse and return an application form for a job.
     *
     * @param int    $intId       [Job ID]
     * @param string $strTemplate [Template name]
     *
     * @return string
     */
    protected function getApplicationForm($intId, $strTemplate = 'offer_apply')
    {
        if (!$this->offer_applicationForm) {
            return '';
        }

        $strForm = $this->getForm($this->offer_applicationForm);

        $objItem = OfferModel::findByPk($intId);

        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->id = $objItem->id;
        $objTemplate->code = $objItem->code;
        $objTemplate->title = $objItem->title;
        $objTemplate->recipient = $GLOBALS['TL_ADMIN_EMAIL'];
        $objTemplate->time = time();
        $objTemplate->token = RequestToken::get();
        $objTemplate->form = $strForm;

        return $objTemplate->parse();
    }
}
