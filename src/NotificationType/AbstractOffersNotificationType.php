<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\AnythingTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\EmailTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\TokenDefinitionInterface;

abstract class AbstractOffersNotificationType implements NotificationTypeInterface
{
    public function __construct(
        protected readonly TokenDefinitionFactoryInterface $factory,
    ) {
    }

    /**
     * Tokens shared by every notification type of the bundle.
     *
     * @return array<TokenDefinitionInterface>
     */
    protected function getCommonTokenDefinitions(): array
    {
        return [
            $this->factory->create(EmailTokenDefinition::class, 'admin_email', 'admin_email'),
            $this->factory->create(EmailTokenDefinition::class, 'recipient_email', 'wem_offers.recipient_email'),
            $this->factory->create(AnythingTokenDefinition::class, 'feed_*', 'wem_offers.feed_*'),
            $this->factory->create(AnythingTokenDefinition::class, 'recipient_*', 'wem_offers.recipient_*'),
        ];
    }
}
