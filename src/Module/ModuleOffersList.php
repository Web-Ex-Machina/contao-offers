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
use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Pagination;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use WEM\OffersBundle\Model\Offer;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "offers list".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModuleOffersList extends ModuleOffers
{
    protected ?array $config = [];

    protected ?int $limit = 0;

    protected ?int $offset = 0;

    protected ?array $options = [];

    protected ?array $filters = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_offerslist';

    private SessionInterface $session;

    // public function __construct($objModule, SessionInterface $session, $strColumn = 'main')
    public function __construct($objModule, $strColumn = 'main')
    {
        parent::__construct($objModule, $strColumn);
        // $this->session = $session;
        $this->session = System::getContainer()->get('session');
    }

    /**
     * Display a wildcard in the back end.
     */
    public function generate(): string
    {
        if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['FMD']['offerslist'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        // Load datacontainer and job feeds
        $this->loadDatacontainer('tl_wem_offer');
        $this->loadLanguageFile('tl_wem_offer');
        $this->offer_feeds = StringUtil::deserialize($this->offer_feeds);

        // Return if there are no archives
        if (empty($this->offer_feeds) || !\is_array($this->offer_feeds)) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        // Init session
        $objSession = $this->session;

        // If we have setup a form, allow module to use it later
        if ($this->offer_applicationForm) {
            $this->blnDisplayApplyButton = true;
        }

        // Catch Ajax requets
        $this->catchAjaxRequests();

        if ($this->offer_applicationForm
            && '' !== $objSession->get('wem_offer')
        ) {
            $strForm = $this->getApplicationForm((int) $objSession->get('wem_offer'));

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

        $this->Template->items = [];
        $this->Template->empty = $GLOBALS['TL_LANG']['WEM']['OFFERS']['empty'];

        // assets
        $strVersion = $this->getCustomPackageVersion('webexmachina/contao-offers');
        $objCssCombiner = new Combiner();
        $objCssCombiner->add('bundles/offers/css/styles.scss', $strVersion);

        $GLOBALS['TL_HEAD'][] = \sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/offers/js/scripts.js';

        // Add pids
        $this->config = ['pid' => $this->offer_feeds, 'published' => 1];

        // Retrieve filters
        if ([] !== $_GET || [] !== $_POST) {
            foreach ($_GET as $f => $v) {
                if (!str_contains($f, 'offer_filter_')) {
                    continue;
                }

                if (Input::get($f)) {
                    $this->config[str_replace('offer_filter_', '', $f)] = Input::get($f);
                }
            }

            foreach (array_keys($_POST) as $f) {
                if (!str_contains($f, 'offer_filter_')) {
                    continue;
                }

                if (Input::post($f)) {
                    $this->config[str_replace('offer_filter_', '', $f)] = Input::post($f);
                }
            }
        }

        // Retrieve filters
        if ($this->offer_addFilters) {
            $this->Template->filters = $this->getFrontendModule($this->offer_filters_module);
        }

        // Get the total number of items
        $intTotal = Offer::countItems($this->config);

        if ($intTotal < 1) {
            return;
        }

        $total = $intTotal - $this->offset;

        // Split the results
        if ($this->perPage > 0 && (!isset($this->limit) || $this->numberOfItems > $this->perPage)) {
            // Adjust the overall limit
            if (isset($this->limit)) {
                $total = min($this->limit, $total);
            }

            // Get the current page
            $id = 'page_n'.$this->id;
            $page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
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
            $objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        $objItems = Offer::findItems($this->config, $this->limit ?: 0, $this->offset ?: 0);

        // Add the articles
        if ($objItems instanceof Collection) {
            $this->Template->items = $this->parseOffers($objItems);
        }

        $this->Template->moduleId = $this->id;

        // Catch auto_item
        if (Input::get('auto_item')) {
            $objOffer = Offer::findItems(['code' => Input::get('auto_item')], 1);

            $this->Template->openModalOnLoad = true;
            $this->Template->offerId = $objOffer->first()->id;
        }
    }
}
