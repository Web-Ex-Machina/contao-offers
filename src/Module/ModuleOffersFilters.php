<?php

declare(strict_types=1);

namespace WEM\OffersBundle\Module;

use Contao\BackendTemplate;
use Contao\Combiner;
use Contao\Input;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use WEM\OffersBundle\Model\Offer;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "offers filters".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModuleOffersFilters extends ModuleOffers
{
    /**
     * List filters.
     */
    protected array $filters = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_offersfilters';

    /**
     * Display a wildcard in the back end.
     */
    public function generate(): string
    {
        if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['FMD']['offersfilters'][0]).' ###';
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
        // Catch Ajax requets
        $this->catchAjaxRequests();

        // assets
        $strVersion = $this->getCustomPackageVersion('webexmachina/contao-offers');
        $objCssCombiner = new Combiner();
        $objCssCombiner->add('bundles/offers/css/styles.scss', $strVersion);

        $GLOBALS['TL_HEAD'][] = sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/offers/js/scripts.js';

        // Retrieve filters
        $this->buildFilters();

        $this->Template->filters = $this->filters;
        $this->Template->moduleId = $this->id;
    }

    /**
     * Retrieve list filters.
     *
     * @return void Manipule array of available filters, parsed
     */
    protected function buildFilters(): void
    {
        // Retrieve and format dropdowns filters
        $filters = StringUtil::deserialize($this->offer_filters);

        if (\is_array($filters) && $filters !== []) {
            foreach ($filters as $f) {
                $field = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$f];
                $fName = sprintf('offer_filter_%s%s', $f, $field['eval']['multiple'] ? '[]' : '');

                $filter = [
                    'type' => $field['inputType'],
                    'name' => $fName,
                    'label' => $field['label'][0] ?: $GLOBALS['TL_LANG']['tl_wem_offer'][$f][0],
                    'value' => Input::get($fName) ?: '',
                    'options' => [],
                    'multiple' => $field['eval']['multiple'] ?? false,
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
                                        'selected' => (null !== Input::get($fName) && (Input::get($fName) === $subValue || (\is_array(Input::get($fName)) && \in_array($subValue, Input::get($fName), true)))),
                                    ];
                                }
                            } else {
                                $filter['options'][] = [
                                    'value' => $value,
                                    'label' => $label,
                                    'selected' => (null !== Input::get($fName) && (Input::get($fName) === $value || (\is_array(Input::get($fName)) && \in_array($value, Input::get($fName), true)))),
                                ];
                            }
                        }

                        break;

                    case 'listWizard':
                        $objOptions = Offer::findItemsGroupByOneField($f);

                        if ($objOptions) {
                            $filter['type'] = 'select';
                            if ($filter['multiple']) {
                                $filter['name'] .= '[]';
                            }

                            while ($objOptions->next()) {
                                if (!$objOptions->{$f}) {
                                    continue;
                                }

                                $subOptions = StringUtil::deserialize($objOptions->{$f});
                                foreach ($subOptions as $subOption) {
                                    $filter['options'][$subOption] = [
                                        'value' => $subOption,
                                        'label' => $subOption,
                                        'selected' => !$filter['multiple']
                                            ? (null !== Input::get($fName) && Input::get($fName) === $subOption)
                                            : (null !== Input::get($fName) && \in_array($subOption, Input::get($f ?? []), true)),
                                    ];
                                }
                            }
                        }

                        break;

                    case 'text':
                    default:
                        $objOptions = Offer::findItemsGroupByOneField($f);

                        if ($objOptions && 0 < $objOptions->count()) {
                            $filter['type'] = 'select';
                            while ($objOptions->next()) {
                                if (!$objOptions->{$f}) {
                                    continue;
                                }

                                $filter['options'][] = [
                                    'value' => $objOptions->{$f},
                                    'label' => $objOptions->{$f},
                                    'selected' => (null !== Input::get($fName) && Input::get($fName) === $objOptions->{$f}),
                                ];
                            }
                        }

                        break;
                }

                if ('select' === $filter['type'] && 1 >= \count($filter['options'])) {
                    continue;
                }

                $this->filters[] = $filter;
            }
        }

        // Add fulltext search if asked
        if ($this->offer_addSearch) {
            $this->filters[] = [
                'type' => 'text',
                'name' => 'offer_filter_search',
                'label' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['search'],
                'placeholder' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['searchPlaceholder'],
                'value' => Input::get('offer_filter_search') ?: '',
            ];
        }
    }
}
