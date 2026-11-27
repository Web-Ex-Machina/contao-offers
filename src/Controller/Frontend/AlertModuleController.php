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
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\Offer;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(self::TYPE, category: self::CATEGORY, template: 'mod_offersalert')]
class AlertModuleController extends ModuleController
{
    public const TYPE = 'offersalert';

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;

        // Return if there are no archives
        if (! $model->offer_feed) {
            return new Response();
        }

        Controller::loadDataContainer(Offer::getTable());
        System::loadLanguageFile(Offer::getTable());

        $this->catchAjaxRequests($request);
        $this->addAssets();

        $action = $request->query->get('wem_action');
        $token = (string) $request->query->get('token');

        try {
            // Catch subscribe confirmation
            if ($action === 'subscribe' && $token !== '') {
                $this->activateAlert($token);
                $template->isRequest = true;
                $template->message = $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['alertActivated'];

                return $template->getResponse();
            }

            // Catch unsubscribe confirmation
            if ($action === 'unsubscribe' && $token !== '') {
                $this->deleteAlert($token);
                $template->isRequest = true;
                $template->message = $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['alertDeleted'];

                return $template->getResponse();
            }
        } catch (\Exception $exception) {
            $template->error = true;
            $template->message = $exception->getMessage();
        }

        if ($action === 'unsubscribe') {
            $template->unsubscribe = true;
            $template->unsubscribeLbl = $GLOBALS['TL_LANG']['WEM']['OFFERS']['unsubscribeLbl'] ?? 'Supprimer mon alerte emploi';
        }

        $template->conditions = $this->buildConditions($request);
        $template->moduleId = $model->id;
        $template->rt = $this->csrfTokenManager->getDefaultTokenValue();

        // Retrieve and send the page for GDPR compliance
        if ($model->offer_pageGdpr && ($objGdprPage = PageModel::findById($model->offer_pageGdpr))) {
            $template->gdprPage = $this->contentUrlGenerator->generate($objGdprPage, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $template->getResponse();
    }

    /**
     * @throws \Exception
     */
    protected function activateAlert(string $token): void
    {
        $objAlerts = Alert::findItems([
            'feed' => $this->model->offer_feed,
            'token' => $token,
            'active' => 0,
        ], 1);
        $objAlert = $objAlerts instanceof Collection ? $objAlerts->current() : null;

        // Check if the alert exists or if the alert is already active
        if (! $objAlert instanceof Alert || (int) $objAlert->activatedAt > 0) {
            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['invalidLink']);
        }

        // Do not activate alerts created more than one hour ago
        if (strtotime('-1 hour') > (int) $objAlert->tstamp) {
            $objAlert->delete();

            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['expiredLink']);
        }

        $objAlert->tstamp = time();
        $objAlert->activatedAt = time();
        $objAlert->save();
    }

    /**
     * @throws \Exception
     */
    protected function deleteAlert(string $token): void
    {
        $objAlerts = Alert::findItems([
            'feed' => $this->model->offer_feed,
            'token' => $token,
        ], 1);

        if (! $objAlerts instanceof Collection) {
            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['invalidLink']);
        }

        $objAlerts->current()->delete();
    }

    /**
     * Retrieve alert available conditions.
     */
    protected function buildConditions(Request $request): array
    {
        $conditions = [];

        foreach (StringUtil::deserialize($this->model->offer_conditions, true) as $c) {
            $field = $GLOBALS['TL_DCA'][Offer::getTable()]['fields'][$c] ?? null;

            if ($field === null) {
                continue;
            }

            $condition = [
                'type' => $field['inputType'],
                'name' => $c,
                'label' => ($field['label'][0] ?? null) ?: ($GLOBALS['TL_LANG'][Offer::getTable()][$c][0] ?? $c),
                'value' => $request->query->get($c) ?: '',
                'options' => [],
                'multiple' => isset($field['eval']['multiple']),
            ];

            if ($field['inputType'] === 'select') {
                $callback = $field['options_callback'] ?? null;

                if (\is_array($callback)) {
                    $options = System::importStatic($callback[0])->{$callback[1]}();
                } elseif (\is_callable($callback)) {
                    $options = $callback();
                } else {
                    $options = $field['options'] ?? [];
                }

                foreach ($options ?? [] as $value => $label) {
                    $condition['options'][] = [
                        'value' => $value,
                        'label' => $label,
                    ];
                }
            } else {
                // Keep it because it works, but it should not be used...
                $values = Offer::findItemsGroupByOneField($c)->fetchEach($c);

                if ($values !== []) {
                    $condition['type'] = 'select';

                    foreach ($values as $value) {
                        $condition['options'][] = [
                            'value' => $value,
                            'label' => $value,
                        ];
                    }
                }
            }

            $conditions[] = $condition;
        }

        return $conditions;
    }
}
