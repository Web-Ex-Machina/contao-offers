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
use Contao\Input;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\System;
// TODO
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\Offer;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "offers alert".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModuleOffersAlert extends ModuleOffers
{
    /**
     * List conditions.
     */
    protected array $conditions = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_offersalert';

    public function __construct($objModule, $strColumn = 'main')
    {
        parent::__construct($objModule, $strColumn);
    }

    /**
     * Display a wildcard in the back end.
     */
    public function generate(): string
    {
        if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['FMD']['offersalert'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        // Load datacontainer and offers languages
        $this->loadDatacontainer('tl_wem_offer');
        $this->loadLanguageFile('tl_wem_offer');

        // Return if there are no archives
        if (!$this->offer_feed) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     *
     * @throws ExceptionInterface
     */
    protected function compile(): void
    {
        // Catch Ajax requets
        $this->catchAjaxRequests();

        // Catch Subscribe GET request
        if (Input::get('token') && 'subscribe' === Input::get('wem_action')) {
            try {
                $objAlert = Alert::findItems(['feed' => $this->offer_feed, 'token' => Input::get('token'), 'active' => false], 1);

                // Check if the alert exists or if the alert is already active
                if (!$objAlert || 0 < $objAlert->activatedAt) {
                    throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['invalidLink']);
                }

                // Check if the alert is expired (we do not want to activate alerts created more than one hour ago)
                if (strtotime('-1 hour') > $objAlert->tstamp) {
                    $objAlert->delete();

                    throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['expiredLink']);
                }

                // Update the alert
                $objAlert->tstamp = time();
                $objAlert->activatedAt = time();
                // $objAlert->token = '';
                $objAlert->save();

                // Build a message
                $this->Template->isRequest = true;
                $this->Template->message = $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['alertActivated'];

                return;
            } catch (\Exception $e) {
                $this->Template->error = true;
                $this->Template->message = $e->getMessage();
                $this->Template->trace = $e->getTraceAsString();
            }
        }

        // Catch Unsubscribe GET request
        if ('unsubscribe' === Input::get('wem_action')) {
            if (Input::get('token')) {
                try {
                    $objAlert = Alert::findItems(['feed' => $this->offer_feed, 'token' => Input::get('token')], 1);

                    // Check if the alert exists or if the alert is already active
                    if (!$objAlert instanceof Collection) {
                        throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['invalidLink']);
                    }

                    // Delete the alert
                    $objAlert->delete();

                    // Build a message
                    $this->Template->isRequest = true;
                    $this->Template->message = $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['alertDeleted'];

                    return;
                } catch (\Exception $e) {
                    $this->Template->error = true;
                    $this->Template->message = $e->getMessage();
                    $this->Template->trace = $e->getTraceAsString();
                }
            } else {
                $this->Template->unsubscribe = true;
                $this->Template->unsubscribeLbl = 'Supprimer mon alerte emploi';
            }
        }

        // Retrieve and format conditions
        $this->buildConditions();
        $this->Template->conditions = $this->conditions;
        $this->Template->moduleId = $this->id;
        $this->Template->rt = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        // Retrieve and send the page for GDPR compliance
        if ($this->offer_pageGdpr && $objGdprPage = PageModel::findByPk($this->offer_pageGdpr)) {
            // $this->Template->gdprPage =$this->urlGenerator->generate($objGdprPage);
            $this->Template->gdprPage = $objGdprPage->getAbsoluteUrl();
        }

        // assets
        $strVersion = $this->getCustomPackageVersion('webexmachina/contao-offers');
        $objCssCombiner = new Combiner();
        $objCssCombiner->add('bundles/offers/css/styles.scss', $strVersion);

        $GLOBALS['TL_HEAD'][] = \sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/offers/js/scripts.js';
    }

    /**
     * Retrieve alert available conditions.
     *
     * @throws \Exception
     */
    protected function buildConditions(): void
    {
        // Retrieve and format dropdowns conditions
        $conditions = StringUtil::deserialize($this->offer_conditions);
        if (\is_array($conditions) && [] !== $conditions) {
            foreach ($conditions as $c) {
                $condition = [
                    'type' => $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['inputType'],
                    'name' => $c,
                    'label' => $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['label'][0] ?: $GLOBALS['TL_LANG']['tl_wem_offer'][$c][0],
                    'value' => Input::get($c) ?: '',
                    'options' => [],
                    'multiple' => isset($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['eval']['multiple']),
                ];

                switch ($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['inputType']) {
                    case 'select':
                        if (isset($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback']) && \is_array($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback'])) {
                            $strClass = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback'][0];
                            $strMethod = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback'][1];

                            $this->import($strClass);
                            $options = $this->$strClass->$strMethod($this);
                        } elseif (isset($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback']) && \is_callable($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback'])) {
                            $options = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback']($this);
                        } elseif (\is_array($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options'])) {
                            $options = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options'];
                        }

                        foreach ($options as $value => $label) {
                            $condition['options'][] = [
                                'value' => $value,
                                'label' => $label,
                            ];
                        }

                        break;

                        // Keep it because it works but it should not be used...
                    case 'text':
                    default:
                        $objOptions = Offer::findItemsGroupByOneField($c);

                        if ($objOptions && 0 < $objOptions->count()) {
                            $condition['type'] = 'select';
                            while ($objOptions->next()) {
                                $condition['options'][] = [
                                    'value' => $objOptions->{$c},
                                    'label' => $objOptions->{$c},
                                ];
                            }
                        }

                        break;
                }

                $this->conditions[] = $condition;
            }
        }
    }
}
