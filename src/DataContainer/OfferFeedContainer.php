<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use WEM\OffersBundle\NotificationType\AlertEmailNotificationType;

class OfferFeedContainer
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Slug $slug,
        private readonly NotificationCenter $notificationCenter,
    ) {
    }

    /**
     * Auto-generate an alias if it has not been set yet.
     *
     * @throws \Exception
     */
    #[AsCallback(table: 'tl_wem_offer_feed', target: 'fields.alias.save')]
    public function generateAlias(mixed $varValue, DataContainer $dc): string
    {
        $aliasExists = fn (string $alias): bool => $this->connection->fetchOne('SELECT id FROM tl_wem_offer_feed WHERE alias = ? AND id != ?', [$alias, $dc->id]) !== false;

        // Generate an alias if there is none
        if (! $varValue) {
            return $this->slug->generate((string) $dc->getCurrentRecord()['title'], (int) $dc->getCurrentRecord()['jumpTo'], $aliasExists);
        }

        if ($aliasExists($varValue)) {
            throw new \Exception(\sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    #[AsCallback(table: 'tl_wem_offer_feed', target: 'fields.ncEmailAlert.options')]
    public function getAlertEmailNotificationChoices(): array
    {
        return $this->notificationCenter->getNotificationsForNotificationType(AlertEmailNotificationType::NAME);
    }
}
