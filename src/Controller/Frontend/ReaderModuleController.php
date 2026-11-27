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
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Twig\TemplateRenderer;

#[AsFrontendModule(self::TYPE, category: self::CATEGORY)]
class ReaderModuleController extends ModuleController
{
    public const TYPE = 'offersreader';

    public function __construct(
        NotificationCenter $notificationCenter,
        ContaoCsrfTokenManager $csrfTokenManager,
        InsertTagParser $insertTagParser,
        Studio $studio,
        ContentUrlGenerator $contentUrlGenerator,
        TemplateRenderer $templateRenderer,
        private readonly ResponseContextAccessor $responseContextAccessor,
        private readonly HtmlDecoder $htmlDecoder,
    ) {
        parent::__construct($notificationCenter, $csrfTokenManager, $insertTagParser, $studio, $contentUrlGenerator, $templateRenderer);
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;
        $autoItem = Input::get('auto_item');
        $offer = $autoItem !== null ? Offer::findByIdOrCode($autoItem) : null;

        if (! $offer instanceof Offer) {
            throw new PageNotFoundException('Page not found: ' . $request->getUri());
        }

        Controller::loadDataContainer(Offer::getTable());
        System::loadLanguageFile(Offer::getTable());

        $this->catchAjaxRequests($request);
        $this->addApplicationFormToTemplate($request, $template);
        $this->addAssets();

        if ($model->overviewPage && ($overviewPage = PageModel::findById($model->overviewPage))) {
            $template->referer = $this->contentUrlGenerator->generate($overviewPage);
            $template->back = $model->customLabel ?: $GLOBALS['TL_LANG']['MSC']['newsOverview'];
        }

        // Overwrite the page metadata
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if ($responseContext?->has(HtmlHeadBag::class)) {
            $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
            $htmlHeadBag->setTitle(trim($offer->title . ' | ' . $offer->code, ' |'));

            if ($offer->teaser) {
                $htmlHeadBag->setMetaDescription(mb_substr($this->htmlDecoder->htmlToPlainText($offer->teaser), 0, 300));
            }
        }

        $template->offer = $this->parseOffer($offer);
        $template->formDisplay = $this->formDisplay;
        $template->moduleId = $model->id;

        return $template->getResponse();
    }
}
