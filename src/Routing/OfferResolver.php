<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\PageModel;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeed;

class OfferResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (! $content instanceof Offer) {
            return null;
        }

        $feed = $this->framework->getAdapter(OfferFeed::class)->findById($content->pid);
        $page = $this->framework->getAdapter(PageModel::class)->findById((int) $feed?->jumpTo);

        return ContentUrlResult::resolve($page);
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (! $content instanceof Offer) {
            return [];
        }

        return [
            'parameters' => '/' . ($content->code ?: $content->id),
        ];
    }
}
