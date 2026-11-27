<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\EventListener\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\OffersBundle\Model\Offer;

/**
 * Example: {{countoffers::1,2,3}}.
 */
#[AsInsertTag('countoffers')]
class CountOffersInsertTagListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $c = [
            'pid' => array_map(intval(...), explode(',', (string) $insertTag->getParameters()->get(0))),
            'published' => 1,
        ];

        // Retrieve filters
        if (($request = $this->requestStack->getCurrentRequest()) instanceof \Symfony\Component\HttpFoundation\Request) {
            foreach ([...$request->query->all(), ...$request->request->all()] as $f => $v) {
                if (str_contains((string) $f, 'offer_filter_') && ! empty($v)) {
                    $c[str_replace('offer_filter_', '', (string) $f)] = $v;
                }
            }
        }

        return new InsertTagResult((string) Offer::countItems($c));
    }
}
