<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2019-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-job-offers
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\OffersBundle\Module;

use Contao\BackendTemplate;
use Contao\Combiner;
use Contao\Input;
use Contao\PageModel;
use Contao\Validator;
use NotificationCenter\Model\Notification; // TODO
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\AlertCondition;
use WEM\OffersBundle\Model\Offer as OfferModel;
use WEM\OffersBundle\Model\OfferFeed;
use WEM\UtilsBundle\Classes\StringUtil;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Front end module "offers alert".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModuleOffersAlert extends ModuleOffers
{

    private CsrfTokenManagerInterface $csrfTokenManager;

    private string $csrfTokenName;
    private ContentUrlGenerator $urlGenerator;
    public function __construct(
        ContentUrlGenerator $urlGenerator, $objModule,
        CsrfTokenManagerInterface $csrfTokenManager,$csrfTokenName,
        $strColumn = 'main'
    )
    {
        parent::__construct($objModule, $strColumn);
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->urlGenerator = $urlGenerator;
    }

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

    /**
     * Display a wildcard in the back end.
     */
    public function generate(): string
    {
        if (TL_MODE === 'BE') {
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
     * @throws ExceptionInterface
     */
    protected function compile(): void
    {
        // Catch Ajax requets
        if (Input::post('TL_AJAX') && (int) $this->id === (int) Input::post('module')) {
            try {
                switch (Input::post('action')) {
                    case 'subscribe':
                        // Check if we have a valid email
                        if (!Input::post('email') || !Validator::isEmail(Input::post('email'))) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['invalidEmail']);
                        }

                        // Check if we have conditions
                        $arrConditions = [];
                        if (Input::post('conditions')) {
                            foreach (Input::post('conditions') as $c => $v) {
                                $arrConditions[$c] = $v;
                            }
                        }

                        // Check if we already have an existing alert with this email and this conditions
                        if (0 < Alert::countItems(
                            ['email' => Input::post('email'), 'feed' => $this->offer_feed, 'conditions' => $arrConditions, 'active' => 1]
                        )) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['alertAlreadyExists']);
                        }

                        // The alert might be inactive, so instead of delete it
                        // and create a new alert, try to retrieve an existing but disable one
                        $objAlert = Alert::findItems(
                            ['email' => Input::post('email'), 'feed' => $this->offer_feed, 'conditions' => $arrConditions, 'active' => 0],
                            1
                        );

                        if (!$objAlert) {
                            $objAlert = new Alert();
                            $objAlert->createdAt = time();
                        }

                        $objAlert->tstamp = time();
                        $objAlert->lastJob = time();
                        $objAlert->activatedAt = 0;
                        $objAlert->email = Input::post('email');
                        $objAlert->frequency = Input::post('frequency') ?: 'daily'; // @todo -> add default frequency as setting
                        $objAlert->token = StringUtil::generateToken(); // @todo -> add code system to confirm requests as alternatives to links/token
                        $objAlert->feed = $this->offer_feed; // @todo -> build a multi feed alert
                        $objAlert->moduleOffersAlert = $this->id;
                        $objAlert->language = $GLOBALS['TL_LANGUAGE'];
                        $objAlert->save();

                        foreach ($arrConditions as $c => $v) {
                            $objAlertCondition = new AlertCondition();
                            $objAlertCondition->tstamp = time();
                            $objAlertCondition->createdAt = time();
                            $objAlertCondition->pid = $objAlert->id;
                            $objAlertCondition->field = $c;
                            $objAlertCondition->value = $v;
                            $objAlertCondition->save();
                        }

                        // Build and send a notification
                        $arrTokens = $this->getNotificationTokens($objAlert);
                        $objNotification = Notification::findByPk($this->offer_ncSubscribe); //TODO : notification
                        $objNotification->send($arrTokens);

                        // Write the response
                        $arrResponse = [
                            'status' => 'success',
                            'msg' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['alertCreated'],
                        ];
                    break;

                    case 'unsubscribe':
                        // Check if we have a valid email
                        if (!Input::post('email') || !Validator::isEmail(Input::post('email'))) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['invalidEmail']);
                        }

                        $objAlert = Alert::findItems(['email' => Input::post('email'), 'feed' => $this->offer_feed], 1);

                        // Check if the alert exists or if the alert is already active
                        if (!$objAlert) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['alertDoesNotExists']);
                        }

                        // Generate a token for this request
                        $objAlert->token = StringUtil::generateToken(); // @todo -> add code system to confirm requests as alternatives to links/token
                        $objAlert->save();

                        // Check if the alert was not activated
                        $arrTokens = $this->getNotificationTokens($objAlert);
                        $objNotification = Notification::findByPk($this->offer_ncUnsubscribe); //TODO : notification
                        $objNotification->send($arrTokens);

                        // Write the response
                        $arrResponse = [
                            'status' => 'success',
                            'msg' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['requestSent'],
                        ];
                    break;

                    default:
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['unknownRequest'], Input::post('action')));
                }
            } catch (\Exception $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getMessage(), 'trace' => $e->getTrace()];
            }

            // Add Request Token to JSON answer and return
            $arrResponse['rt'] = $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue();
            echo json_encode($arrResponse);
            die;
        }

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
                    if (!$objAlert) {
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

        // Retrieve and send the page for GDPR compliance
        if ($this->offer_pageGdpr && $objGdprPage = PageModel::findByPk($this->offer_pageGdpr)) {
            $this->Template->gdprPage =$this->urlGenerator->generate($objGdprPage);
        }

        // assets
        $strVersion = $this->getCustomPackageVersion('webexmachina/contao-offers');
        $objCssCombiner = new Combiner();
        $objCssCombiner->add('bundles/offers/css/styles.scss', $strVersion);

        $GLOBALS['TL_HEAD'][] = sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());
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
        if (\is_array($conditions) && $conditions !== []) {
            foreach ($conditions as $c) {
                $condition = [
                    'type' => $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['inputType'],
                    'name' => $c,
                    'label' => $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['label'][0] ?: $GLOBALS['TL_LANG']['tl_wem_offer'][$c][0],
                    'value' => Input::get($c) ?: '',
                    'options' => [],
                    'multiple' => (bool) $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['eval']['multiple'],
                ];

                switch ($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['inputType']) {
                    case 'select':
                        if (\is_array($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback'])) {
                            $strClass = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback'][0];
                            $strMethod = $GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback'][1];

                            $this->import($strClass);
                            $options = $this->$strClass->$strMethod($this);
                        } elseif (\is_callable($GLOBALS['TL_DCA']['tl_wem_offer']['fields'][$c]['options_callback'])) {
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
                        $objOptions = OfferModel::findItemsGroupByOneField($c);

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

    /**
     * Build Notification Tokens.
     * @throws ExceptionInterface
     */
    protected function getNotificationTokens(Alert $objAlert): array
    {
        $arrTokens = [];

        $objFeed = OfferFeed::findByPk($objAlert->feed);
        foreach ($objFeed->row() as $strKey => $varValue) {
            $arrTokens['feed_'.$strKey] = $varValue;
        }

        foreach ($objAlert->row() as $strKey => $varValue) {
            $arrTokens['subscription_'.$strKey] = $varValue;
        }

        if ($this->offer_pageSubscribe && $objSubscribePage = PageModel::findByPk($this->offer_pageSubscribe)) {
            $arrTokens['link_subscribe'] = $this->urlGenerator->generate($objSubscribePage, ['wem_action'=>'subscribe','token'=>$objAlert->token]);
        }

        if ($this->offer_pageUnsubscribe && $objSubscribePage = PageModel::findByPk($this->offer_pageUnsubscribe)) {
            $arrTokens['link_unsubscribe'] = $this->urlGenerator->generate($objSubscribePage, ['wem_action'=>'unsubscribe']);
            $arrTokens['link_unsubscribeConfirm'] = $this->urlGenerator->generate($objSubscribePage, ['wem_action'=>'unsubscribe','token'=>$objAlert->token]);
        }

        $arrTokens['recipient_email'] = $objAlert->email;

        $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        return $arrTokens;
    }
}
