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

use Contao\Combiner;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
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
            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['FMD']['offersreader'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        // Init countries
        \System::getCountries();

        // Init session
        $objSession = \Session::getInstance();

        // If we have setup a form, allow module to use it later
        if ($this->offer_applicationForm) {
            $this->blnDisplayApplyButton = true;
        }

        // Catch Ajax requets
        if (\Input::post('TL_AJAX') && (int) $this->id === (int) \Input::post('module')) {
            try {
                switch (\Input::post('action')) {
                    case 'apply':
                        if (!\Input::post('offer')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'offer'));
                        }

                        // Put the offer in session
                        $objSession->set('wem_offer', \Input::post('offer'));

                        echo \Haste\Util\InsertTag::replaceRecursively($this->getApplicationForm(\Input::post('offer')));
                        exit;
                    break;

                    default:
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['unknownRequest'], \Input::post('action')));
                }
            } catch (\Exception $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getResponse(), 'trace' => $e->getTrace()];
            }

            // Add Request Token to JSON answer and return
            $arrResponse['rt'] = \RequestToken::get();
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
        $this->limit = null;
        $this->offset = (int) $this->skipFirst;

        // Maximum number of items
        if ($this->numberOfItems > 0) {
            $this->limit = $this->numberOfItems;
        }

        $this->Template->articles = [];
        $this->Template->empty = $GLOBALS['TL_LANG']['WEM']['OFFERS']['empty'];

        // assets
        $strVersion = $this->getCustomPackageVersion('webexmachina/contao-offers');
        $objCssCombiner = new Combiner();
        $objCssCombiner->add('bundles/offers/css/styles.scss', $strVersion);

        $GLOBALS['TL_HEAD'][] = sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/offers/js/scripts.js';

        // Add pids
        $this->config = ['pid' => $this->offer_feeds, 'published' => 1];

        // Retrieve filters
        $this->buildFilters();
        $this->Template->filters = $this->filters;

        // Get the total number of items
        $intTotal = OfferModel::countItems($this->config);

        if ($intTotal < 1) {
            return;
        }

        $total = $intTotal - $offset;

        // Split the results
        if ($this->perPage > 0 && (!isset($this->limit) || $this->numberOfItems > $this->perPage)) {
            // Adjust the overall limit
            if (isset($this->limit)) {
                $total = min($this->limit, $total);
            }

            // Get the current page
            $id = 'page_n'.$this->id;
            $page = \Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.\Environment::get('uri'));
            }

            // Set limit and offset
            $this->limit = $this->perPage;
            $this->offset += (max($page, 1) - 1) * $this->perPage;
            $skip = (int) $this->skipFirst;

            // Overall limit
            if ($this->offset + $this->limit > $total + $skip) {
                $this->limit = $total + $skip - $this->offset;
            }

            // Add the pagination menu
            $objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        $objArticles = OfferModel::findItems($this->config, ($this->limit ?: 0), ($this->offset ?: 0));

        // Add the articles
        if (null !== $objArticles) {
            $this->Template->articles = $this->parseOffers($objArticles);
        }

        $this->Template->moduleId = $this->id;

        // Catch auto_item
        if (Input::get('auto_item')) {
            $objOffer = OfferModel::findItems(['code' => Input::get('auto_item')], 1);

            $this->Template->openModalOnLoad = true;
            $this->Template->offerId = $objOffer->first()->id;
        }
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

        $objTemplate = new \FrontendTemplate($strTemplate);
        $objTemplate->id = $objItem->id;
        $objTemplate->code = $objItem->code;
        $objTemplate->title = $objItem->title;
        $objTemplate->recipient = $GLOBALS['TL_ADMIN_EMAIL'];
        $objTemplate->time = time();
        $objTemplate->token = \RequestToken::get();
        $objTemplate->form = $strForm;

        return $objTemplate->parse();
    }
}
