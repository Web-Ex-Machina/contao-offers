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
class ModuleOffersList extends ModuleOffers
{
    /**
     * List config.
     */
    protected $config = [];

    /**
     * List limit.
     */
    protected $limit = 0;

    /**
     * List offset.
     */
    protected $offset = 0;

    /**
     * List options.
     */
    protected $options = [];

    /**
     * List filters.
     */
    protected $filters = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_offerslist';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');
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
        $this->offer_feeds = \StringUtil::deserialize($this->offer_feeds);

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
                    case 'seeDetails':
                        if (!\Input::post('offer')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'offer'));
                        }
                        $objItem = OfferModel::findByPk(\Input::post('offer'));

                        $this->offer_template = 'offer_details';
                        echo \Haste\Util\InsertTag::replaceRecursively($this->parseOffer($objItem));
                        exit;
                    break;

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
            $objOffer = Offer::findItems(['code' => Input::get('auto_item')], 1);

            $this->Template->openModalOnLoad = true;
            $this->Template->offerId = $objOffer->first()->id;
        }
    }

    /**
     * Retrieve list filters.
     *
     * @return array [Array of available filters, parsed]
     */
    protected function buildFilters()
    {
        if (!$this->offer_addFilters) {
            return;
        }

        // Retrieve and format dropdowns filters
        $filters = deserialize($this->offer_filters);
        if (\is_array($filters) && !empty($filters)) {
            foreach ($filters as $f) {
                $field = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$f];

                $filter = [
                    'type' => $field['inputType'],
                    'name' => $field['eval']['multiple'] ? $f.'[]' : $f,
                    'label' => $field['label'][0] ?: $GLOBALS['TL_LANG']['tl_wem_offer'][$f][0],
                    'value' => \Input::get($f) ?: '',
                    'options' => [],
                    'multiple' => $field['eval']['multiple'] ? true : false,
                ];

                switch ($field['inputType']) {
                    case 'select':
                        if (\is_array($field['options_callback'])) {
                            $strClass = $field['options_callback'][0];
                            $strMethod = $field['options_callback'][1];

                            $this->import($strClass);
                            $options = $this->$strClass->$strMethod($this);
                        } elseif (\is_callable($field['options_callback'])) {
                            $options = $field['options_callback']($this);
                        } elseif (\is_array($field['options'])) {
                            $options = $field['options'];
                        }

                        foreach ($options as $value => $label) {
                            if (\is_array($label)) {
                                foreach ($label as $subValue => $subLabel) {
                                    $filter['options'][$value]['options'][] = [
                                        'value' => $subValue,
                                        'label' => $subLabel,
                                        'selected' => (null !== \Input::get($f) && (\Input::get($f) === $subValue || (\is_array(\Input::get($f)) && \in_array($subValue, \Input::get($f), true)))),
                                    ];
                                }
                            } else {
                                $filter['options'][] = [
                                    'value' => $value,
                                    'label' => $label,
                                    'selected' => (null !== \Input::get($f) && (\Input::get($f) === $value || (\is_array(\Input::get($f)) && \in_array($value, \Input::get($f), true)))),
                                ];
                            }
                        }

                        break;

                    case 'listWizard':
                        $objOptions = OfferModel::findItemsGroupByOneField($f);

                        if ($objOptions) {
                            $filter['type'] = 'select';
                            if ($filter['multiple']) {
                                $filter['name'] .= '[]';
                            }
                            while ($objOptions->next()) {
                                if (!$objOptions->{$f}) {
                                    continue;
                                }

                                $subOptions = deserialize($objOptions->{$f});
                                foreach ($subOptions as $subOption) {
                                    $filter['options'][$subOption] = [
                                        'value' => $subOption,
                                        'label' => $subOption,
                                        'selected' => !$filter['multiple']
                                            ? (null !== \Input::get($f) && \Input::get($f) === $subOption)
                                            : (null !== \Input::get($f) && \in_array($subOption, \Input::get($f ?? []), true)),
                                    ];
                                }
                            }
                        }
                        break;

                    case 'text':
                    default:
                        $objOptions = OfferModel::findItemsGroupByOneField($f);

                        if ($objOptions && 0 < $objOptions->count()) {
                            $filter['type'] = 'select';
                            while ($objOptions->next()) {
                                if (!$objOptions->{$f}) {
                                    continue;
                                }

                                $filter['options'][] = [
                                    'value' => $objOptions->{$f},
                                    'label' => $objOptions->{$f},
                                    'selected' => (null !== \Input::get($f) && \Input::get($f) === $objOptions->{$f}),
                                ];
                            }
                        }
                        break;
                }

                if ('select' === $filter['type'] && 1 >= \count($filter['options'])) {
                    continue;
                }

                if (null !== \Input::get($f) && '' !== \Input::get($f)) {
                    $this->config[$f] = \Input::get($f);
                }

                $this->filters[] = $filter;
            }
        }

        // Add fulltext search if asked
        if ($this->offer_addSearch) {
            $this->filters[] = [
                'type' => 'text',
                'name' => 'search',
                'label' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['search'],
                'placeholder' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['searchPlaceholder'],
                'value' => \Input::get('search') ?: '',
            ];

            if ('' !== \Input::get('search') && null !== \Input::get('search')) {
                $this->config['search'] = StringUtil::formatKeywords(\Input::get('search'));
            }
        }
    }
}
