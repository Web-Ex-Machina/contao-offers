<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina.
 *
 * @category ContaoBundle
 *
 * @author   Web ex Machina <contact@webexmachina.fr>
 *
 * @see     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\JobOffersBundle\Module;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
use Patchwork\Utf8;
use WEM\JobOffersBundle\Model\Job as JobModel;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "offers list".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModuleJobOffersList extends ModuleJobOffers
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
    protected $strTemplate = 'mod_jobslist';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['jobslist'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        // Load bundles, datacontainer and job feeds
        $this->bundles = \System::getContainer()->getParameter('kernel.bundles');
        $this->loadDatacontainer('tl_wem_job');
        $this->loadLanguageFile('tl_wem_job');
        $this->job_feeds = \StringUtil::deserialize($this->job_feeds);

        // Return if there are no archives
        if (empty($this->job_feeds) || !\is_array($this->job_feeds)) {
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
        if ($this->job_applicationForm) {
            $this->blnDisplayApplyButton = true;
        }

        // Catch Ajax requets
        if (\Input::post('TL_AJAX')) {
            try {
                switch (\Input::post('action')) {
                    case 'seeDetails':
                        if (!\Input::post('job')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['argumentMissing'], 'job'));
                        }
                        $objJob = JobModel::findByPk(\Input::post('job'));

                        $this->job_template = 'job_details';
                        echo \Haste\Util\InsertTag::replaceRecursively($this->parseArticle($objJob));
                        die;
                    break;

                    case 'apply':
                        if (!\Input::post('job')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['argumentMissing'], 'job'));
                        }

                        // Put the job in session
                        $objSession->set('wem_job_offer', \Input::post('job'));

                        echo \Haste\Util\InsertTag::replaceRecursively($this->getApplicationForm(\Input::post('job')));
                        die;
                    break;

                    default:
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['unknownRequest'], \Input::post('action')));
                }
            } catch (\Exception $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getResponse(), 'trace' => $e->getTrace()];
            }

            // Add Request Token to JSON answer and return
            $arrResponse['rt'] = \RequestToken::get();
            echo json_encode($arrResponse);
            die;
        }

        if ($this->job_applicationForm
            && '' !== $objSession->get('wem_job_offer')
        ) {
            $strForm = $this->getApplicationForm($objSession->get('wem_job_offer'));

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
        $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

        // Add pids
        $this->config = ['pid' => $this->job_feeds, 'published' => 1];

        // Retrieve filters
        $this->buildFilters();
        $this->Template->filters = $this->filters;

        // Get the total number of items
        $intTotal = JobModel::countItems($this->config);

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

        $objArticles = JobModel::findItems($this->config, ($this->limit ?: 0), ($this->offset ?: 0));

        // Add the articles
        if (null !== $objArticles) {
            $this->Template->articles = $this->parseJobOffers($objArticles);
        }
    }

    /**
     * Parse and return an application form for a job.
     *
     * @param int    $intJob      [Job ID]
     * @param string $strTemplate [Template name]
     *
     * @return string
     */
    protected function getApplicationForm($intJob, $strTemplate = 'job_apply')
    {
        $strForm = $this->getForm($this->job_applicationForm);

        $objJob = JobModel::findByPk($intJob);

        $objTemplate = new \FrontendTemplate($strTemplate);
        $objTemplate->id = $objJob->id;
        $objTemplate->code = $objJob->code;
        $objTemplate->title = $objJob->title;
        $objTemplate->recipient = $objJob->hrEmail ?: $GLOBALS['TL_ADMIN_EMAIL'];
        $objTemplate->time = time();
        $objTemplate->token = \RequestToken::get();
        $objTemplate->form = $strForm;

        return $objTemplate->parse();
    }

    /**
     * Retrieve list filters.
     *
     * @return array [Array of available filters, parsed]
     */
    protected function buildFilters()
    {
        if (!$this->job_addFilters) {
            return;
        }

        // Retrieve and format dropdowns filters
        $filters = deserialize($this->job_filters);
        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $f) {
                $filter = [
                    'type' => $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['inputType'],
                    'name' => $f,
                    'label' => $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['label'][0] ?: $GLOBALS['TL_LANG']['tl_wem_job'][$f][0],
                    'value' => \Input::get($f) ?: '',
                    'options' => [],
                    'multiple' => $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['eval']['multiple'] ? true : false,
                ];

                switch ($GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['inputType']) {
                    case 'select':
                        if (\is_array($GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['options_callback'])) {
                            $strClass = $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['options_callback'][0];
                            $strMethod = $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['options_callback'][1];

                            $this->import($strClass);
                            $options = $this->$strClass->$strMethod($this);
                        } elseif (\is_callable($GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['options_callback'])) {
                            $options = $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['options_callback']($this);
                        } elseif (\is_array($GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['options'])) {
                            $options = $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$f]['options'];
                        }

                        foreach ($options as $value => $label) {
                            $filter['options'][] = [
                                'value' => $value,
                                'label' => $label,
                                'selected' => (\Input::get($f) == $value || (is_array(\Input::get($f)) && in_array($value, \Input::get($f)))),
                            ];
                        }
                        break;

                    case 'text':
                    default:
                        $objOptions = JobModel::findItemsGroupByOneField($f);

                        if ($objOptions && 0 < $objOptions->count()) {
                            $filter['type'] = 'select';
                            while ($objOptions->next()) {
                                $filter['options'][] = [
                                    'value' => $objOptions->{$f},
                                    'label' => $objOptions->{$f},
                                    'selected' => \Input::get($f) == $objOptions->{$f},
                                ];
                            }
                        }

                        if ('' != \Input::get($f)) {
                            $this->config[$f] = \Input::get($f);
                        }
                        break;
                }

                $this->filters[] = $filter;
            }
        }

        // Add fulltext search if asked
        if ($this->job_addSearch) {
            $this->filters[] = [
                'type' => 'text',
                'name' => 'search',
                'label' => $GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['search'],
                'placeholder' => $GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['searchPlaceholder'],
                'value' => \Input::get('search') ?: '',
            ];

            if ('' != \Input::get('search')) {
                $this->config['search'] = StringUtil::formatKeywords(\Input::get('search'));
            }
        }
    }
}
