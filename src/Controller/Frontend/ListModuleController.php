<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Controller\Frontend;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\Pagination;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\OffersBundle\Model\Offer;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(self::TYPE, category: self::CATEGORY, template: 'mod_offerslist')]
class ListModuleController extends ModuleController
{
    public const TYPE = 'offerslist';

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;
        $feeds = StringUtil::deserialize($model->offer_feeds, true);

        // Return if there are no archives
        if ($feeds === []) {
            return new Response();
        }

        Controller::loadDataContainer(Offer::getTable());
        System::loadLanguageFile(Offer::getTable());

        $this->catchAjaxRequests($request);
        $this->addApplicationFormToTemplate($request, $template);
        $this->addAssets();

        $limit = null;
        $offset = (int) $model->skipFirst;

        // Maximum number of items
        if ($model->numberOfItems > 0) {
            $limit = (int) $model->numberOfItems;
        }

        $template->items = [];
        $template->empty = $GLOBALS['TL_LANG']['WEM']['OFFERS']['empty'];
        $template->moduleId = $model->id;

        $config = [
            'pid' => $feeds,
            'published' => 1,
            ...$this->getFiltersFromRequest($request),
        ];

        // Retrieve filters
        if ($model->offer_addFilters && $model->offer_filters_module) {
            $template->filters = Controller::getFrontendModule($model->offer_filters_module);
        }

        // Get the total number of items
        $intTotal = Offer::countItems($config);

        if ($intTotal < 1) {
            return $template->getResponse();
        }

        $total = $intTotal - $offset;

        // Split the results
        if ($model->perPage > 0 && ($limit === null || $model->numberOfItems > $model->perPage)) {
            // Adjust the overall limit
            if ($limit !== null) {
                $total = min($limit, $total);
            }

            // Get the current page
            $id = 'page_n' . $model->id;
            $page = (int) ($request->query->get($id) ?? 1);

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $model->perPage), 1)) {
                throw new PageNotFoundException('Page not found: ' . $request->getUri());
            }

            // Set limit and offset
            $limit = (int) $model->perPage;
            $offset += (max($page, 1) - 1) * $model->perPage;
            $skip = (int) $model->skipFirst;

            // Overall limit
            if ($offset + $limit > $total + $skip) {
                $limit = $total + $skip - $offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $model->perPage, Config::get('maxPaginationLinks'), $id);
            $template->pagination = $objPagination->generate("\n  ");
        }

        $objItems = Offer::findItems($config, $limit ?: 0, $offset ?: 0);

        if ($objItems instanceof Collection) {
            $template->items = $this->parseOffers($objItems);
        }

        $template->formDisplay = $this->formDisplay;

        // Open the offer given as auto_item
        if ($autoItem = Input::get('auto_item')) {
            $objOffer = Offer::findByIdOrCode($autoItem);

            if ($objOffer instanceof Offer) {
                $template->openModalOnLoad = true;
                $template->offerId = $objOffer->id;
            }
        }

        return $template->getResponse();
    }
}
