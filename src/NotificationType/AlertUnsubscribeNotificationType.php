<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\NotificationType;

use Terminal42\NotificationCenterBundle\Token\Definition\AnythingTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

class AlertUnsubscribeNotificationType extends AbstractOffersNotificationType
{
    public const NAME = 'wem_offers_alerts_unsubscribe';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [
            ...$this->getCommonTokenDefinitions(),
            $this->factory->create(AnythingTokenDefinition::class, 'subscription_*', 'wem_offers.subscription_*'),
            $this->factory->create(TextTokenDefinition::class, 'link_*', 'wem_offers.link_*'),
        ];
    }
}
