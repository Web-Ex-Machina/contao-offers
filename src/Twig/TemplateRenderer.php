<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Twig;

use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\FrontendTemplate;
use Twig\Environment;

/**
 * Render the offer item templates.
 *
 * Template names containing a slash are Twig templates (e.g. "offer/default"),
 * the other ones are legacy PHP templates that might still exist in a website.
 */
class TemplateRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ContextFactory $contextFactory,
    ) {
    }

    public function render(string $template, array $data): string
    {
        if (str_contains($template, '/')) {
            return $this->twig->render(\sprintf('@Contao/%s.html.twig', $template), $this->contextFactory->fromData($data));
        }

        $objTemplate = new FrontendTemplate($template);
        $objTemplate->setData($data);

        if (($data['figure'] ?? null) instanceof Figure) {
            $data['figure']->applyLegacyTemplateData($objTemplate, $data['imagemargin'] ?? null, $data['floating'] ?? null);
        }

        return $objTemplate->parse();
    }
}
