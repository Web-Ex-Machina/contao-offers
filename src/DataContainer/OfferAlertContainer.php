<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Intl\Locales;
use Contao\Date;
use Contao\Message;
use Contao\ModuleModel;
use Contao\ThemeModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\OffersBundle\Controller\Frontend\AlertModuleController;
use WEM\OffersBundle\Cronjob\SendAlerts;
use WEM\OffersBundle\Model\Alert;

class OfferAlertContainer
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Locales $locales,
        private readonly SendAlerts $sendAlerts,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Design each row of the DCA.
     */
    #[AsCallback(table: 'tl_wem_offer_alert', target: 'list.label.label')]
    public function listItems(array $row, string $label, mixed $dc, array $labels): array
    {
        $feeds = $this->getFeeds();

        $labels[0] = $row['email'];
        $labels[1] = $feeds[$row['feed']] ?? $row['feed'];
        $labels[2] = $GLOBALS['TL_LANG'][Alert::getTable()]['frequency'][$row['frequency']] ?? $row['frequency'];
        $labels[3] = empty($row['lastJob']) ? '-' : Date::parse(Config::get('datimFormat'), (int) $row['lastJob']);
        $labels[4] = empty($row['activatedAt']) ? '-' : Date::parse(Config::get('datimFormat'), (int) $row['activatedAt']);

        return $labels;
    }

    /**
     * Get available feeds.
     */
    #[AsCallback(table: 'tl_wem_offer_alert', target: 'fields.feed.options')]
    public function getFeeds(): array
    {
        return $this->connection->fetchAllKeyValue('SELECT id, title FROM tl_wem_offer_feed ORDER BY title');
    }

    #[AsCallback(table: 'tl_wem_offer_alert', target: 'fields.language.options')]
    public function getLanguages(): array
    {
        return $this->locales->getLocales();
    }

    #[AsCallback(table: 'tl_wem_offer_alert', target: 'fields.moduleOffersAlert.options')]
    public function getOffersAlertModules(): array
    {
        $arrChoices = [];

        foreach (ModuleModel::findByType(AlertModuleController::TYPE) ?? [] as $objModule) {
            $objTheme = ThemeModel::findById($objModule->pid);
            $arrChoices[$objModule->id] = \sprintf('%s (%s)', $objModule->name, $objTheme instanceof ThemeModel ? $objTheme->name : '-');
        }

        return $arrChoices;
    }

    /**
     * Back end key callback: send the alerts right now.
     *
     * @throws \Exception
     */
    public function sendAlerts(): void
    {
        $this->sendAlerts->do(false);

        Message::addInfo($GLOBALS['TL_LANG']['WEM']['OFFERS']['jobExecuted']);

        $request = (string) $this->requestStack->getCurrentRequest()?->getRequestUri();
        Controller::redirect(preg_replace('/&(amp;)?key=[^&]*/', '', $request));
    }
}
