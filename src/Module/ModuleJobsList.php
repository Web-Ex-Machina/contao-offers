<?php

namespace WEM\JobOffersBundle\Module;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
use Patchwork\Utf8;

use Prezioso\Model\Job as JobModel;

/**
 * Front end module "offers list".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModuleJobsList extends \Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_jobslist';

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['jobslist'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {
        $strForm = $this->getForm(1);

        if (Input::get('apply') && !Input::post('FORM_SUBMIT')) {
            $objJob = JobModel::findByPk(Input::get('apply'));

            $objTemplate = new \FrontendTemplate('job_apply');
            $objTemplate->id =  $objJob->id;
            $objTemplate->code =  $objJob->code;
            $objTemplate->title =  $objJob->title;
            $objTemplate->recipient =  $objJob->recipient ?: $GLOBALS['TL_ADMIN_EMAIL'];
            $objTemplate->time =  time();
            $objTemplate->token =  \RequestToken::get();
            $objTemplate->form = $strForm;
            
            echo $objTemplate->parse();
            die;
        }

        global $objPage;
        $limit = null;
        $offset = (int) $this->skipFirst;

        // Maximum number of items
        if ($this->numberOfItems > 0) {
            $limit = $this->numberOfItems;
        }

        $arrConfig = ["published"=>1];
        $this->Template->articles = array();
        $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

        // Get the available filters
        $objJobFilters = JobModel::findItems(["published"=>1]);
        if ($objJobFilters && 0 < $objJobFilters->count()) {
            $arrJobFilters = [];
            $arrLocationFilters = [];
            while ($objJobFilters->next()) {
                if (!in_array($objJobFilters->title, $arrJobFilters)) {
                    $arrJobFilters[] = $objJobFilters->title;
                }

                if (!in_array($objJobFilters->location, $arrLocationFilters)) {
                    $arrLocationFilters[] = $objJobFilters->location;
                }
            }
            $this->Template->jobFilters = $arrJobFilters;
            $this->Template->locationFilters = $arrLocationFilters;
        }

        // Add job to the config if there is a filter
        if (\Input::get('job')) {
            $arrConfig['title'] = \Input::get('job');
        }
        
        // Add area to the config if there is a filter
        if (\Input::get('location')) {
            $arrConfig['location'] = \Input::get('location');
        }

        // Get the total number of items
        $intTotal = JobModel::countItems($arrConfig);

        if ($intTotal < 1) {
            return;
        }

        $total = $intTotal - $offset;

        // Split the results
        if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage)) {
            // Adjust the overall limit
            if (isset($limit)) {
                $total = min($limit, $total);
            }

            // Get the current page
            $id = 'page_n' . $this->id;
            $page = \Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total/$this->perPage), 1)) {
                throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
            }

            // Set limit and offset
            $limit = $this->perPage;
            $offset += (max($page, 1) - 1) * $this->perPage;
            $skip = (int) $this->skipFirst;

            // Overall limit
            if ($offset + $limit > $total + $skip) {
                $limit = $total + $skip - $offset;
            }

            // Add the pagination menu
            $objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        $objArticles = JobModel::findItems($arrConfig, ($limit ?: 0), ($offset ?: 0));

        // Add the articles
        if ($objArticles !== null) {
            $this->Template->articles = $this->parseArticles($objArticles);
        }

        // Load JS
        $objCombiner = new \Combiner();
        $objCombiner->add("system/modules/prezioso/assets/js/mod_joblist.js", time());
        $GLOBALS["TL_JQUERY"][] = '<script src="https://www.google.com/recaptcha/api.js"></script>';
        $GLOBALS["TL_JQUERY"][] = sprintf('<script src="%s"></script>', $objCombiner->getCombinedFile());
    }

    /**
     * Parse one or more items and return them as array
     *
     * @param Model\Collection $objArticles
     * @param boolean          $blnAddArchive
     *
     * @return array
     */
    protected function parseArticles($objArticles, $blnAddArchive = false)
    {
        $limit = $objArticles->count();

        if ($limit < 1) {
            return array();
        }

        $count = 0;
        $arrArticles = array();

        while ($objArticles->next()) {
            /** @var NewsModel $objArticle */
            $objArticle = $objArticles->current();

            $arrArticles[] = $this->parseArticle($objArticle, $blnAddArchive, ((++$count == 1) ? ' first' : '') . (($count == $limit) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even'), $count);
        }

        return $arrArticles;
    }

    /**
     * Parse an item and return it as string
     *
     * @param NewsModel $objArticle
     * @param boolean   $blnAddArchive
     * @param string    $strClass
     * @param integer   $intCount
     *
     * @return string
     */
    protected function parseArticle($objArticle, $blnAddArchive = false, $strClass = '', $intCount = 0)
    {
        $objTemplate = new \FrontendTemplate($this->job_template);
        $objTemplate->setData($objArticle->row());

        if ($objArticle->cssClass != '') {
            $strClass = ' ' . $objArticle->cssClass . $strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount; // see #5708

        // Add the meta information
        $objTemplate->date = $arrMeta['createdAt'];
        $objTemplate->timestamp = $objArticle->date;
        $objTemplate->datetime = date('Y-m-d\TH:i:sP', $objArticle->date);

        $objTemplate->applyUrl = $this->addToUrl("apply=".$objArticle->id, true, ["job"]);

        // Tag the response
        if (\System::getContainer()->has('fos_http_cache.http.symfony_response_tagger')) {
            /** @var ResponseTagger $responseTagger */
            $responseTagger = \System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(array('contao.db.tl_pzl_job.' . $objArticle->id));
        }

        return $objTemplate->parse();
    }
}
