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

use NotificationCenter\Model\Notification;
use Patchwork\Utf8;
use WEM\JobOffersBundle\Model\Alert;
use WEM\JobOffersBundle\Model\AlertCondition;
use WEM\JobOffersBundle\Model\Job as JobModel;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "offers alert".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModuleJobOffersAlert extends ModuleJobOffers
{
    /**
     * List conditions.
     */
    protected $conditions = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_jobsalert';

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

        // Return if there are no archives
        if (!$this->job_feed) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        // Catch Ajax requets
        if (\Input::post('TL_AJAX')) {
            try {
                switch (\Input::post('action')) {
                    case 'subscribe':
                        // Check if we have a valid email
                        if (!\Input::post('email') || !\Validator::isEmail(\Input::post('email'))) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['invalidEmail']);
                        }

                        // Check if we have conditions
                        $arrConditions = [];
                        if (\Input::post('conditions')) {
                            foreach (\Input::post('conditions') as $c => $v) {
                                $arrConditions[$c] = $v;
                            }
                        }

                        // Check if we already have an existing alert with this email and this conditions
                        if (0 < Alert::countItems(['email' => \Input::post('email'), 'feed' => $this->job_feed, 'conditions' => $arrConditions])) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['alertAlreadyExists']);
                        }

                        // Save as many alerts as they are feeds set up for this module
                        $objAlert = new Alert();
                        $objAlert->tstamp = time();
                        $objAlert->lastJob = time();
                        $objAlert->createdAt = time();
                        $objAlert->activatedAt = 0;
                        $objAlert->name = \Input::post('name') ?: '';
                        $objAlert->position = \Input::post('position') ?: '';
                        $objAlert->phone = \Input::post('phone') ?: '';
                        $objAlert->email = \Input::post('email');
                        $objAlert->frequency = \Input::post('frequency') ?: 'daily'; // @todo -> add default frequency as setting
                        $objAlert->token = StringUtil::generateToken(); // @todo -> add code system to confirm requests as alternatives to links/token
                        $objAlert->feed = $this->job_feed; // @todo -> build a multi feed alert
                        $objAlert->save();

                        if (!empty($arrConditions)) {
                            foreach ($arrConditions as $c => $v) {
                                $objAlertCondition = new AlertCondition();
                                $objAlertCondition->tstamp = time();
                                $objAlertCondition->createdAt = time();
                                $objAlertCondition->pid = $objAlert->id;
                                $objAlertCondition->field = $c;
                                $objAlertCondition->value = $v;
                                $objAlertCondition->save();
                            }
                        }

                        // Build and send a notification
                        $arrTokens = $this->getNotificationTokens();
                        $objNotification = Notification::findByPk($this->job_ncSubscribe);
                        $objNotification->send($arrTokens);

                        // Write the response
                        $arrResponse = [
                            'status' => 'success',
                            'message' => $GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['MSG']['alertCreated'],
                        ];
                    break;

                    case 'unsubscribe':
                        // Check if we have a valid email
                        if (!\Input::post('email') || !\Validator::isEmail(\Input::post('email'))) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['invalidEmail']);
                        }

                        $objAlert = Alert::findItems(['email' => \Input::post('email'), 'feed' => $this->job_feed], 1);

                        // Check if the alert exists or if the alert is already active
                        if (!$objAlert) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['alertDoesNotExists']);
                        }

                        // Check if the alert was not activated
                        $arrTokens = $this->getNotificationTokens();
                        $objNotification = Notification::findByPk($this->job_ncUnsubscribe);
                        $objNotification->send($arrTokens);

                        // Write the response
                        $arrResponse = [
                            'status' => 'success',
                            'message' => $GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['MSG']['requestSent'],
                        ];
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

        // Catch Subscribe GET request
        if (\Input::get('token') && 'subscribe' === \Input::get('action')) {
            try {
                $objAlert = Alert::findItems(['feed' => $this->job_feed, 'token' => \Input::get('token')], 1);

                // Check if the alert exists or if the alert is already active
                if (!$objAlert || 0 < $objAlert->activatedAt) {
                    throw new \Exception($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['invalidLink']);
                }

                // Check if the alert is expired (we do not want to activate alerts created more than one hour ago)
                if (strtotime('-1 hour ago') > $objAlert->createdAt) {
                    $objAlert->delete();

                    throw new \Exception($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['expiredLink']);
                }

                // Update the alert
                $objAlert->tstamp = time();
                $objAlert->activatedAt = time();
                $objAlert->save();

                // Build a message
                $this->Template->isRequest = true;
                $this->Template->message = $GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['MSG']['alertActivated'];

                return;
            } catch (\Exception $e) {
                $this->Template->error = true;
                $this->Template->message = $e->getMessage();
                $this->Template->trace = $e->getTraceAsString();
            }
        }

        // Catch Unsubscribe GET request
        if (\Input::get('token') && 'unsubscribe' === \Input::get('action')) {
            try {
                $objAlert = Alert::findItems(['feed' => $this->job_feed, 'token' => \Input::get('token')], 1);

                // Check if the alert exists or if the alert is already active
                if (!$objAlert) {
                    throw new \Exception($GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['ERROR']['invalidLink']);
                }

                // Delete the alert
                $objAlert->delete();

                // Build a message
                $this->Template->isRequest = true;
                $this->Template->message = $GLOBALS['TL_LANG']['WEM']['JOBOFFERS']['MSG']['alertDeleted'];

                return;
            } catch (\Exception $e) {
                $this->Template->error = true;
                $this->Template->message = $e->getMessage();
                $this->Template->trace = $e->getTraceAsString();
            }
        }

        // Retrieve and format conditions
        $this->buildConditions();
        $this->Template->conditions = $this->conditions;

        // Retrieve and send the page for GDPR compliance
        if ($this->job_pageGdpr && $objGdprPage = \PageModel::findByPk($this->job_pageGdpr)) {
            $this->Template->gdprPage = $objGdprPage->getFrontendUrl();
        }
    }

    /**
     * Retrieve alert available conditions.
     *
     * @return array [Array of available conditions, parsed]
     */
    protected function buildConditions()
    {
        // Retrieve and format dropdowns conditions
        $conditions = deserialize($this->job_conditions);
        if (is_array($conditions) && !empty($conditions)) {
            foreach ($conditions as $c) {
                $condition = [
                    'type' => $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['inputType'],
                    'name' => $c,
                    'label' => $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['label'][0] ?: $GLOBALS['TL_LANG']['tl_wem_job'][$c][0],
                    'value' => \Input::get($c) ?: '',
                    'options' => [],
                    'multiple' => $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['eval']['multiple'] ? true : false,
                ];

                switch ($GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['inputType']) {
                    case 'select':
                        if (\is_array($GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['options_callback'])) {
                            $strClass = $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['options_callback'][0];
                            $strMethod = $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['options_callback'][1];

                            $this->import($strClass);
                            $options = $this->$strClass->$strMethod($this);
                        } elseif (\is_callable($GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['options_callback'])) {
                            $options = $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['options_callback']($this);
                        } elseif (\is_array($GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['options'])) {
                            $options = $GLOBALS['TL_DCA']['tl_wem_job']['fields'][$c]['options'];
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
                        $objOptions = JobModel::findItemsGroupByOneField($c);

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
     *
     * @param Alert $objAlert
     *
     * @return array
     */
    protected function getNotificationTokens($objAlert)
    {
        $arrTokens = [];

        $objFeed = JobFeed::findByPk($objAlert->feed);
        foreach ($objFeed->row() as $strKey => $varValue) {
            $arrTokens['jobfeed_'.$strKey] = $varValue;
        }

        foreach ($objAlert->row() as $strKey => $varValue) {
            $arrTokens['subscription_'.$strKey] = $varValue;
        }

        if ($this->job_pageSubscribe && $objSubscribePage = \PageModel::findByPk($this->job_pageSubscribe)) {
            $arrTokens['link_subscribe'] = $objSubscribePage->getFrontendUrl().'?action=subscribe&token='.$objAlert->token;
        }

        if ($this->job_pageUnsubscribe && $objSubscribePage = \PageModel::findByPk($this->job_pageUnsubscribe)) {
            $arrTokens['link_unsubscribe'] = $objSubscribePage->getFrontendUrl().'?action=unsubscribe&token='.$objAlert->token;
        }

        $arrTokens['recipient_name'] = $objAlert->name;
        $arrTokens['recipient_position'] = $objAlert->position;
        $arrTokens['recipient_phone'] = $objAlert->phone;
        $arrTokens['recipient_email'] = $objAlert->email;

        return $arrTokens;
    }
}
