<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\EventListener\Form;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Input;
use Contao\ModuleModel;
use Contao\Widget;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\OffersBundle\Model\Offer;

/**
 * Fill the "pid" field of the application forms with the current offer.
 */
#[AsHook('loadFormField')]
class LoadFormFieldListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if ($widget->name !== 'pid' || ModuleModel::countBy('offer_applicationForm', $form->id) === 0) {
            return $widget;
        }

        // The offer comes from the reader URL or from the session when the form is loaded in a modal
        $request = $this->requestStack->getCurrentRequest();
        $varOffer = Input::get('auto_item') ?? ($request?->hasSession() ? $request->getSession()->get('wem_offer') : null);

        if ($varOffer && ($objOffer = Offer::findByIdOrCode((string) $varOffer)) instanceof Offer) {
            $widget->value = $objOffer->id;
        }

        return $widget;
    }
}
