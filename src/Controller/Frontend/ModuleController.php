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
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Frontend;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\AlertCondition;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeed;
use WEM\OffersBundle\Twig\TemplateRenderer;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Common functions for offers frontend modules.
 */
abstract class ModuleController extends AbstractFrontendModuleController
{
    public const CATEGORY = 'wem-offers';

    public const DEFAULT_OFFER_TEMPLATE = 'offer/default';

    public const DETAILS_OFFER_TEMPLATE = 'offer/details';

    protected ModuleModel $model;

    /**
     * Application form display mode, forwarded to the module template.
     */
    protected string|null $formDisplay = null;

    public function __construct(
        protected readonly NotificationCenter $notificationCenter,
        protected readonly ContaoCsrfTokenManager $csrfTokenManager,
        protected readonly InsertTagParser $insertTagParser,
        protected readonly Studio $studio,
        protected readonly ContentUrlGenerator $contentUrlGenerator,
        protected readonly TemplateRenderer $templateRenderer,
    ) {
    }

    /**
     * Register the bundle assets on the page.
     */
    protected function addAssets(): void
    {
        $GLOBALS['TL_CSS']['wem_offers'] = 'bundles/offers/css/styles.scss|static';
        $GLOBALS['TL_JAVASCRIPT']['wem_offers'] = 'bundles/offers/js/scripts.js|static';
    }

    /**
     * Intercept the AJAX requests targeting this module and stop the page rendering
     * by sending the response directly.
     */
    protected function catchAjaxRequests(Request $request): void
    {
        if (! $request->request->get('TL_AJAX') || (int) $this->model->id !== (int) $request->request->get('module')) {
            return;
        }

        try {
            $response = $this->handleAjaxRequest($request);
        } catch (\Exception $exception) {
            $response = new JsonResponse([
                'status' => 'error',
                'msg' => $exception->getMessage(),
            ]);
        }

        if ($response instanceof JsonResponse) {
            $data = json_decode((string) $response->getContent(), true);
            $data['rt'] = $this->csrfTokenManager->getDefaultTokenValue();
            $response->setData($data);
        }

        throw new ResponseException($response);
    }

    /**
     * @throws \Exception
     */
    protected function handleAjaxRequest(Request $request): Response
    {
        $action = (string) $request->request->get('action');

        return match ($action) {
            'countOffers' => $this->countOffers($request),
            'seeDetails' => $this->seeDetails($request),
            'apply' => $this->apply($request),
            'subscribe' => $this->subscribe($request),
            'unsubscribe' => $this->unsubscribe($request),
            default => throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['unknownRequest'], $action)),
        };
    }

    protected function countOffers(Request $request): JsonResponse
    {
        $c = [
            'published' => 1,
        ];

        if ($this->model->offer_feeds) {
            $c['pid'] = StringUtil::deserialize($this->model->offer_feeds);
        }

        foreach ($request->request->all('filters') as $f => $v) {
            if (! str_contains((string) $f, 'offer_filter_')) {
                continue;
            }

            $c[str_replace('offer_filter_', '', (string) $f)] = $v;
        }

        return new JsonResponse([
            'status' => 'success',
            'count' => Offer::countItems($c),
        ]);
    }

    /**
     * @throws \Exception
     */
    protected function seeDetails(Request $request): Response
    {
        $objItem = Offer::findByPk($this->getRequiredPostValue($request, 'offer'));

        if (! $objItem instanceof Offer) {
            throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'offer'));
        }

        $this->model->offer_template = self::DETAILS_OFFER_TEMPLATE;

        return new Response($this->insertTagParser->replace($this->parseOffer($objItem)));
    }

    /**
     * @throws \Exception
     */
    protected function apply(Request $request): Response
    {
        $offerId = (int) $this->getRequiredPostValue($request, 'offer');

        // Put the offer in session
        $request->getSession()->set('wem_offer', $offerId);

        return new Response($this->insertTagParser->replace($this->getApplicationForm($offerId)));
    }

    /**
     * @throws \Exception
     */
    protected function subscribe(Request $request): JsonResponse
    {
        $email = $this->getValidEmail($request);
        // Conditions with several values are stored serialized
        $arrConditions = array_map(
            static fn ($v) => \is_array($v) ? serialize(array_values($v)) : $v,
            $request->request->all('conditions'),
        );

        // Check if we already have an existing alert with this email and this conditions
        if (Alert::countItems([
            'email' => $email,
            'feed' => $this->model->offer_feed,
            'conditions' => $arrConditions,
            'active' => 1,
        ]) > 0) {
            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['alertAlreadyExists']);
        }

        // The alert might be inactive, so instead of delete it and create a new alert,
        // try to retrieve an existing but disabled one
        $objAlerts = Alert::findItems([
            'email' => $email,
            'feed' => $this->model->offer_feed,
            'conditions' => $arrConditions,
            'active' => 0,
        ], 1);
        $objAlert = $objAlerts instanceof Collection ? $objAlerts->current() : null;

        if (! $objAlert instanceof Alert) {
            $objAlert = new Alert();
            $objAlert->createdAt = time();
        }

        $objAlert->tstamp = time();
        $objAlert->lastJob = time();
        $objAlert->activatedAt = 0;
        $objAlert->email = $email;
        $objAlert->frequency = $request->request->get('frequency') ?: 'daily'; // @todo -> add default frequency as setting
        $objAlert->token = StringUtil::generateToken(); // @todo -> add code system to confirm requests as alternatives to links/token
        $objAlert->feed = $this->model->offer_feed; // @todo -> build a multi feed alert
        $objAlert->moduleOffersAlert = $this->model->id;
        $objAlert->language = $request->getLocale();
        $objAlert->save();

        foreach ($arrConditions as $field => $value) {
            // An existing alert might already have this condition
            if (AlertCondition::countItems([
                'pid' => $objAlert->id,
                'field' => $field,
                'value' => $value,
            ]) > 0) {
                continue;
            }

            $objAlertCondition = new AlertCondition();
            $objAlertCondition->tstamp = time();
            $objAlertCondition->createdAt = time();
            $objAlertCondition->pid = $objAlert->id;
            $objAlertCondition->field = $field;
            $objAlertCondition->value = $value;
            $objAlertCondition->save();
        }

        if ($this->model->offer_ncSubscribe) {
            $this->notificationCenter->sendNotification((int) $this->model->offer_ncSubscribe, $this->getNotificationTokens($objAlert), $request->getLocale());
        }

        return new JsonResponse([
            'status' => 'success',
            'msg' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['alertCreated'],
        ]);
    }

    /**
     * @throws \Exception
     */
    protected function unsubscribe(Request $request): JsonResponse
    {
        $email = $this->getValidEmail($request);
        $objAlerts = Alert::findItems([
            'email' => $email,
            'feed' => $this->model->offer_feed,
        ], 1);

        // Check if the alert exists
        if (! $objAlerts instanceof Collection) {
            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['alertDoesNotExists']);
        }

        /** @var Alert $objAlert */
        $objAlert = $objAlerts->current();

        // Generate a token for this request
        $objAlert->token = StringUtil::generateToken(); // @todo -> add code system to confirm requests as alternatives to links/token
        $objAlert->save();

        if ($this->model->offer_ncUnsubscribe) {
            $this->notificationCenter->sendNotification((int) $this->model->offer_ncUnsubscribe, $this->getNotificationTokens($objAlert), $request->getLocale());
        }

        return new JsonResponse([
            'status' => 'success',
            'msg' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['requestSent'],
        ]);
    }

    /**
     * Parse one or more items and return them as array.
     *
     * @param Collection<Offer> $objItems
     *
     * @throws \Exception
     */
    protected function parseOffers(Collection $objItems): array
    {
        $limit = $objItems->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrItems = [];

        foreach ($objItems as $objItem) {
            $arrItems[] = $this->parseOffer($objItem, ((++$count === 1) ? ' first' : '') . (($count === $limit) ? ' last' : '') . ((0 === ($count % 2)) ? ' odd' : ' even'), $count);
        }

        return $arrItems;
    }

    /**
     * Parse an item and return it as string.
     *
     * @throws \Exception
     */
    protected function parseOffer(Offer $objItem, string $strClass = '', int $intCount = 0): string
    {
        $id = (int) $objItem->id;
        $data = $objItem->row();

        if ($objItem->cssClass) {
            $strClass = ' ' . $objItem->cssClass . $strClass;
        }

        $data['model'] = $objItem;
        $data['class'] = $strClass;
        $data['count'] = $intCount;

        // Add the meta information
        $data['date'] = (int) $objItem->date;
        $data['timestamp'] = $objItem->date;
        $data['datetime'] = date('Y-m-d\TH:i:sP', (int) $objItem->date);

        // Add an image, use the offer title as alternative text unless the metadata are overwritten
        $data['alt'] = $objItem->overwriteMeta ? $objItem->alt : $objItem->title;
        $data['figure'] = $objItem->addImage && $objItem->singleSRC
            ? $this->studio
                ->createFigureBuilder()
                ->from($objItem->singleSRC)
                ->setSize($objItem->size)
                ->setOverwriteMetadata(new Metadata(array_filter([
                    Metadata::VALUE_ALT => $data['alt'],
                    Metadata::VALUE_TITLE => $objItem->overwriteMeta ? $objItem->imageTitle : null,
                    Metadata::VALUE_URL => $objItem->overwriteMeta ? $objItem->imageUrl : null,
                    Metadata::VALUE_CAPTION => $objItem->overwriteMeta ? $objItem->caption : null,
                ])))
                ->enableLightbox((bool) $objItem->fullsize)
                ->buildIfResourceExists()
            : null;

        // Retrieve item teaser
        $data['hasTeaser'] = (bool) $objItem->teaser;
        $data['teaser'] = $objItem->teaser ? StringUtil::encodeEmail($objItem->teaser) : '';

        // Retrieve item content
        $data['text'] = static function () use ($id): string {
            $strText = '';

            foreach (ContentModel::findPublishedByPidAndTable($id, Offer::getTable()) ?? [] as $objElement) {
                $strText .= Controller::getContentElement($objElement);
            }

            return $strText;
        };

        $data['hasText'] = static fn (): bool => ContentModel::countPublishedByPidAndTable($id, Offer::getTable()) > 0;

        // Retrieve item attributes
        $data['blnDisplayAttributes'] = (bool) $this->model->offer_displayAttributes;
        $data['attributes'] = $this->model->offer_displayAttributes && $this->model->offer_attributes !== null
            ? $objItem->getAttributesFull(StringUtil::deserialize($this->model->offer_attributes, true))
            : [];

        // Notice the template if we want/can display apply button
        $data['canApply'] = (bool) $this->model->offer_applicationForm;
        $data['blnDisplayApplyButton'] = $data['canApply'];
        $data['formDisplay'] = $this->model->offer_applicationFormDisplay;
        $data['applyUrl'] = null;
        $data['form'] = null;

        if ($data['canApply']) {
            $this->formDisplay = $this->model->offer_applicationFormDisplay;

            if ($this->model->offer_applicationFormDisplay === 'modal') {
                $data['applyUrl'] = Frontend::addToUrl('apply=' . $objItem->id, true, ['offer']);
            } else {
                $data['form'] = $this->getApplicationForm($id);
            }
        }

        // Link to the overview page
        $overviewPage = $this->model->overviewPage ? PageModel::findById($this->model->overviewPage) : null;
        $data['referer'] = $overviewPage instanceof PageModel ? $this->contentUrlGenerator->generate($overviewPage) : null;
        $data['back'] = $this->model->customLabel ?: ($GLOBALS['TL_LANG']['MSC']['newsOverview'] ?? '');

        // Notice the template if we want to display the text
        $data['blnDisplayText'] = (bool) $this->model->offer_displayTeaser;
        $data['detailsUrl'] = $this->model->offer_displayTeaser ? null : Frontend::addToUrl('seeDetails=' . $objItem->id, true, ['offer']);

        // Parse the URL if we have a jumpTo configured
        $data['jumpTo'] = OfferFeed::findById($objItem->pid)?->jumpTo ? $this->contentUrlGenerator->generate($objItem) : null;

        return $this->templateRenderer->render($this->model->offer_template ?: self::DEFAULT_OFFER_TEMPLATE, $data);
    }

    /**
     * Parse and return an application form for an offer.
     */
    protected function getApplicationForm(int $intId, string $strTemplate = 'offer/apply'): string
    {
        if (! $this->model->offer_applicationForm) {
            return '';
        }

        $objItem = Offer::findById($intId);

        if (! $objItem instanceof Offer) {
            return '';
        }

        return $this->templateRenderer->render($strTemplate, [
            'id' => $objItem->id,
            'code' => $objItem->code,
            'title' => $objItem->title,
            'recipient' => $this->getAdminEmail(),
            'time' => time(),
            'token' => $this->csrfTokenManager->getDefaultTokenValue(),
            'form' => Controller::getForm($this->model->offer_applicationForm),
        ]);
    }

    /**
     * Restore the application form after a submission in a modal.
     */
    protected function addApplicationFormToTemplate(Request $request, FragmentTemplate $template): void
    {
        if (! $this->model->offer_applicationForm || ! $request->hasSession()) {
            return;
        }

        $offerId = (int) $request->getSession()->get('wem_offer');

        if ($offerId === 0 || ! $request->request->get('FORM_SUBMIT')) {
            return;
        }

        $template->openModalOnLoad = true;
        $template->openModalOnLoadContent = json_encode($this->getApplicationForm($offerId));
    }

    /**
     * Build notification tokens for an alert.
     */
    protected function getNotificationTokens(Alert $objAlert): array
    {
        $arrTokens = [];

        foreach (OfferFeed::findById($objAlert->feed)?->row() ?? [] as $strKey => $varValue) {
            $arrTokens['feed_' . $strKey] = $varValue;
        }

        foreach ($objAlert->row() as $strKey => $varValue) {
            $arrTokens['subscription_' . $strKey] = $varValue;
        }

        if ($this->model->offer_pageSubscribe && ($objSubscribePage = PageModel::findById($this->model->offer_pageSubscribe))) {
            $arrTokens['link_subscribe'] = $this->contentUrlGenerator->generate($objSubscribePage, [], UrlGeneratorInterface::ABSOLUTE_URL) . '?wem_action=subscribe&token=' . $objAlert->token;
        }

        if ($this->model->offer_pageUnsubscribe && ($objUnsubscribePage = PageModel::findById($this->model->offer_pageUnsubscribe))) {
            $url = $this->contentUrlGenerator->generate($objUnsubscribePage, [], UrlGeneratorInterface::ABSOLUTE_URL);
            $arrTokens['link_unsubscribe'] = $url . '?wem_action=unsubscribe';
            $arrTokens['link_unsubscribeConfirm'] = $url . '?wem_action=unsubscribe&token=' . $objAlert->token;
        }

        $arrTokens['recipient_email'] = $objAlert->email;
        $arrTokens['admin_email'] = $this->getAdminEmail();

        return $arrTokens;
    }

    protected function getAdminEmail(): string
    {
        return (string) ($this->getPageModel()?->adminEmail ?: Config::get('adminEmail'));
    }

    /**
     * Retrieve the filters sent through the query string or the POST data.
     */
    protected function getFiltersFromRequest(Request $request): array
    {
        $filters = [];

        foreach ([...$request->query->all(), ...$request->request->all()] as $f => $v) {
            if (! str_contains((string) $f, 'offer_filter_') || empty($v)) {
                continue;
            }

            $filters[str_replace('offer_filter_', '', (string) $f)] = $v;
        }

        return $filters;
    }

    /**
     * @throws \Exception
     */
    private function getRequiredPostValue(Request $request, string $key): string
    {
        $value = (string) $request->request->get($key);

        if ($value === '') {
            throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], $key));
        }

        return $value;
    }

    /**
     * @throws \Exception
     */
    private function getValidEmail(Request $request): string
    {
        $email = (string) $request->request->get('email');

        if (! Validator::isEmail($email)) {
            throw new \Exception($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['invalidEmail']);
        }

        return $email;
    }
}
