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

use Contao\Config;
use Contao\ContentModel;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Module;
use Contao\PageModel;
use Contao\System;
use Contao\Validator;
use NotificationCenter\Model\Notification;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\AlertCondition;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeed;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Common functions for job offers modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModuleOffers extends Module
{
    protected function catchAjaxRequests(): void
    {
        if (Input::post('TL_AJAX') && (int) $this->id === (int) Input::post('module')) {
            $objSession = System::getContainer()->get('request_stack')->getSession();
            try {
                switch (Input::post('action')) {
                    case 'countOffers':
                        $c['published'] = 1;

                        if ($this->offer_feeds) {
                            $c['pid'] = StringUtil::deserialize($this->offer_feeds);
                        }

                        // Retrieve filters
                        if (Input::post('filters')) {
                            foreach (Input::post('filters') as $f => $v) {
                                if (!str_contains($f, 'offer_filter_')) {
                                    continue;
                                }

                                $c[str_replace('offer_filter_', '', $f)] = $v;
                            }
                        }

                        $intCount = Offer::countItems($c);

                        // Write the response
                        $arrResponse = [
                            'status' => 'success',
                            'count' => $intCount,
                        ];
                        break;

                    case 'seeDetails':
                        if (!Input::post('offer')) {
                            throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'offer'));
                        }

                        $objItem = Offer::findByPk(Input::post('offer'));

                        $this->offer_template = 'offer_details';
                        echo System::getContainer()->get('contao.insert_tag.parser')->replace($this->parseOffer($objItem));
                        exit;

                    case 'apply':
                        if (!Input::post('offer')) {
                            throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'offer'));
                        }

                        // Put the offer in session
                        $objSession->set('wem_offer', Input::post('offer'));
                        echo System::getContainer()->get('contao.insert_tag.parser')->replace($this->getApplicationForm(Input::post('offer')));
                        exit;

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

                        if (!$objAlert instanceof Collection) {
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
                        $objNotification = Notification::findByPk($this->offer_ncSubscribe);
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
                        if (!$objAlert instanceof Collection) {
                            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['alertDoesNotExists']);
                        }

                        // Generate a token for this request
                        $objAlert->token = StringUtil::generateToken(); // @todo -> add code system to confirm requests as alternatives to links/token
                        $objAlert->save();

                        // Check if the alert was not activated
                        $arrTokens = $this->getNotificationTokens($objAlert);
                        $objNotification = Notification::findByPk($this->offer_ncUnsubscribe);
                        $objNotification->send($arrTokens);

                        // Write the response
                        $arrResponse = [
                            'status' => 'success',
                            'msg' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['requestSent'],
                        ];
                        break;

                    default:
                        throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['unknownRequest'], Input::post('action')));
                }
            } catch (\Exception $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getMessage(), 'trace' => $e->getTrace()];
            }

            // Add Request Token to JSON answer and return
            $arrResponse['rt'] = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
            echo json_encode($arrResponse);
            exit;
        }
    }

    /**
     * Parse one or more items and return them as array.
     *
     * @throws \Exception
     */
    protected function parseOffers(Collection $objItems, bool $blnAddArchive = false): array
    {
        $limit = $objItems->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrItems = [];

        while ($objItems->next()) {
            $objItem = $objItems->current();

            $arrItems[] = $this->parseOffer($objItem, $blnAddArchive, ((1 === ++$count) ? ' first' : '').(($count === $limit) ? ' last' : '').((0 === ($count % 2)) ? ' odd' : ' even'), $count);
        }

        return $arrItems;
    }

    /**
     * Parse an item and return it as string.
     *
     * @throws \Exception
     */
    protected function parseOffer(Offer $objItem, bool $blnAddArchive = false, string $strClass = '', int $intCount = 0): string
    {
        $objTemplate = new FrontendTemplate($this->offer_template);
        $objTemplate->setData($objItem->row());

        if ('' !== $objItem->cssClass) {
            $strClass = ' '.$objItem->cssClass.$strClass;
        }

        $objTemplate->model = $objItem;
        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount; // see #5708

        // Add the meta information
        $objTemplate->date = (int) $objItem->date;
        $objTemplate->timestamp = $objItem->date;
        $objTemplate->datetime = date('Y-m-d\TH:i:sP', (int) $objItem->date);

        // Add an image
        if ($objItem->addImage) {
            $figure = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->from($objItem->singleSRC)
                ->setSize($objItem->size)
                ->enableLightbox((bool) $objItem->fullsize)
                ->buildIfResourceExists()
            ;

            if (null !== $figure) {
                $figure->applyLegacyTemplateData($objTemplate, $objItem->imagemargin, $objItem->floating);
            }
        }

        // Retrieve item teaser
        if ($objItem->teaser) {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = StringUtil::encodeEmail($objItem->teaser);
        }

        // Retrieve item content
        $id = $objItem->id;

        $objTemplate->text = function () use ($id): string {
            $strText = '';
            $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_offer');

            if (null !== $objElement) {
                while ($objElement->next()) {
                    $strText .= $this->getContentElement($objElement->current());
                }
            }

            return $strText;
        };

        $objTemplate->hasText = static fn (): bool => ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_wem_offer') > 0;

        // Retrieve item attributes
        $objTemplate->blnDisplayAttributes = (bool) $this->offer_displayAttributes;

        if ((bool) $this->offer_displayAttributes && null !== $this->offer_attributes) {
            $objTemplate->attributes = $objItem->getAttributesFull(StringUtil::deserialize($this->offer_attributes));
        }

        // Notice the template if we want/can display apply button
        if ($this->offer_applicationForm) {
            $objTemplate->canApply = true;
            $this->Template->formDisplay = $this->offer_applicationFormDisplay;

            if ('modal' === $this->offer_applicationFormDisplay) {
                $objTemplate->applyUrl = $this->addToUrl('apply='.$objItem->id, true, ['offer']);
            } else {
                $strForm = $this->getApplicationForm($objItem->id);
                $objTemplate->form = $strForm;
            }
        }

        // Notice the template if we want to display the text
        if ($this->offer_displayTeaser) {
            $objTemplate->blnDisplayText = true;
        } else {
            $objTemplate->detailsUrl = $this->addToUrl('seeDetails='.$objItem->id, true, ['offer']);
        }

        // Parse the URL if we have a jumpTo configured
        if ($objTarget = $objItem->getRelated('pid')->getRelated('jumpTo')) {
            $params = (Config::get('useAutoItem') ? '/' : '/items/').($objItem->code ?: $objItem->id);
            $objTemplate->jumpTo = $objTarget->getFrontendUrl($params);
        }

        return $objTemplate->parse();
    }

    /**
     * Parse and return an application form for a job.
     */
    protected function getApplicationForm(int $intId, string $strTemplate = 'offer_apply'): string
    {
        if (!$this->offer_applicationForm) {
            return '';
        }

        $strForm = $this->getForm($this->offer_applicationForm);

        $objItem = Offer::findByPk($intId);

        if (!$objItem) {
            return '';
        }

        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->id = $objItem->id;
        $objTemplate->code = $objItem->code;
        $objTemplate->title = $objItem->title;
        $objTemplate->recipient = $GLOBALS['TL_ADMIN_EMAIL'];
        $objTemplate->time = time();
        $objTemplate->token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        $objTemplate->form = $strForm;

        return $objTemplate->parse();
    }

    /**
     * Build Notification Tokens.
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
            $arrTokens['link_subscribe'] = $objSubscribePage->getAbsoluteUrl().'?wem_action=subscribe&token='.$objAlert->token;
        }

        if ($this->offer_pageUnsubscribe && $objSubscribePage = PageModel::findByPk($this->offer_pageUnsubscribe)) {
            $arrTokens['link_unsubscribe'] = $objSubscribePage->getAbsoluteUrl().'?wem_action=unsubscribe';
            $arrTokens['link_unsubscribeConfirm'] = $objSubscribePage->getAbsoluteUrl().'?wem_action=unsubscribe&token='.$objAlert->token;
        }

        $arrTokens['recipient_email'] = $objAlert->email;

        $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        return $arrTokens;
    }

    /**
     * Get a package's version.
     *
     * @param string $package The package name
     *
     * @return string|null The package version if found, null otherwise
     */
    protected function getCustomPackageVersion(string $package): ?string
    {
        $packages = json_decode(file_get_contents('./../vendor/composer/installed.json'));

        foreach ($packages->packages as $p) {
            $p = (array) $p;
            if ($package === $p['name']) {
                return $p['version'];
            }
        }

        return null;
    }
}
