<?php

declare(strict_types=1);

namespace WEM\OffersBundle\EventListener;

use Contao\Form;
use Contao\Input;
use Contao\ModuleModel;
use Contao\Widget;
use WEM\OffersBundle\Model\Offer;

class LoadFormFieldListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
    	if (
    		0 < ModuleModel::countBy('offer_applicationForm', $form->id)
            && 'pid' === $widget->name
            && Input::get('auto_item')
        ) {
    		$objOffer = Offer::findByIdOrCode(Input::get('auto_item'));

    		if ($objOffer) {
    			$widget->value = $objOffer->id;
    		}
        }

        return $widget;
    }
}