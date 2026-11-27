<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Controller\Frontend;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\OffersBundle\Model\Offer;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(self::TYPE, category: self::CATEGORY)]
class FiltersModuleController extends ModuleController
{
    public const TYPE = 'offersfilters';

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;

        Controller::loadDataContainer(Offer::getTable());
        System::loadLanguageFile(Offer::getTable());

        $this->catchAjaxRequests($request);
        $this->addAssets();

        $template->filters = $this->buildFilters($request);
        $template->moduleId = $model->id;

        // Define where the form is redirected
        if ($model->jumpTo && ($page = PageModel::findById($model->jumpTo))) {
            $template->formAction = $this->contentUrlGenerator->generate($page);
        } else {
            $template->formAction = $request->getRequestUri();
        }

        return $template->getResponse();
    }

    /**
     * Retrieve the list filters.
     */
    protected function buildFilters(Request $request): array
    {
        $filters = [];

        foreach (StringUtil::deserialize($this->model->offer_filters, true) as $f) {
            $field = $GLOBALS['TL_DCA'][Offer::getTable()]['fields'][$f] ?? null;

            if ($field === null) {
                continue;
            }

            $multiple = (bool) ($field['eval']['multiple'] ?? false);
            $fName = \sprintf('offer_filter_%s', $f);
            $current = $request->query->all()[$fName] ?? null;

            $isSelected = static fn ($value): bool => $current !== null && (\is_array($current) ? \in_array((string) $value, $current, true) : (string) $value === $current);

            $filter = [
                'type' => $field['inputType'],
                'name' => $fName . ($multiple ? '[]' : ''),
                'label' => ($field['label'][0] ?? null) ?: ($GLOBALS['TL_LANG'][Offer::getTable()][$f][0] ?? $f),
                'value' => $current ?: '',
                'options' => [],
                'multiple' => $multiple,
            ];

            switch ($field['inputType']) {
                case 'select':
                    foreach ($this->getFieldOptions($field) as $value => $label) {
                        if (\is_array($label)) {
                            foreach ($label as $subValue => $subLabel) {
                                $filter['options'][$value]['options'][] = [
                                    'value' => $subValue,
                                    'label' => $subLabel,
                                    'selected' => $isSelected($subValue),
                                ];
                            }
                        } else {
                            $filter['options'][] = [
                                'value' => $value,
                                'label' => $label,
                                'selected' => $isSelected($value),
                            ];
                        }
                    }

                    break;

                case 'listWizard':
                    $filter['type'] = 'select';

                    foreach (Offer::findItemsGroupByOneField($f)->fetchEach($f) as $value) {
                        foreach (StringUtil::deserialize($value, true) as $subOption) {
                            $filter['options'][$subOption] = [
                                'value' => $subOption,
                                'label' => $subOption,
                                'selected' => $isSelected($subOption),
                            ];
                        }
                    }

                    break;

                case 'text':
                default:
                    $values = array_filter(Offer::findItemsGroupByOneField($f)->fetchEach($f));

                    if ($values !== []) {
                        $filter['type'] = 'select';

                        foreach ($values as $value) {
                            $filter['options'][] = [
                                'value' => $value,
                                'label' => $value,
                                'selected' => $isSelected($value),
                            ];
                        }
                    }

                    break;
            }

            if ($filter['type'] === 'select' && \count($filter['options']) <= 1) {
                continue;
            }

            $filters[] = $filter;
        }

        // Add fulltext search if asked
        if ($this->model->offer_addSearch) {
            $filters[] = [
                'type' => 'text',
                'name' => 'offer_filter_search',
                'label' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['search'],
                'placeholder' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['searchPlaceholder'],
                'value' => $request->query->get('offer_filter_search') ?: '',
            ];
        }

        return $filters;
    }

    /**
     * Resolve the options of a DCA select field.
     */
    protected function getFieldOptions(array $field): array
    {
        $callback = $field['options_callback'] ?? null;

        if (\is_array($callback)) {
            return System::importStatic($callback[0])->{$callback[1]}() ?? [];
        }

        if (\is_callable($callback)) {
            return $callback() ?? [];
        }

        return $field['options'] ?? [];
    }
}
