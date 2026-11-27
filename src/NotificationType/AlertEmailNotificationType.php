<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\NotificationType;

use Terminal42\NotificationCenterBundle\Token\Definition\HtmlTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

class AlertEmailNotificationType extends AbstractOffersNotificationType
{
    public const NAME = 'wem_offers_alerts_email';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [
            ...$this->getCommonTokenDefinitions(),
            $this->factory->create(TextTokenDefinition::class, 'offerstext', 'wem_offers.offerstext'),
            $this->factory->create(HtmlTokenDefinition::class, 'offershtml', 'wem_offers.offershtml'),
            $this->factory->create(TextTokenDefinition::class, 'link_*', 'wem_offers.link_*'),
        ];
    }
}
