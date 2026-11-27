<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\NotificationCenterBundle\Terminal42NotificationCenterBundle;
use WEM\OffersBundle\OffersBundle;
use WEM\PersonalDataManagerBundle\WEMPersonalDataManagerBundle;
use WEM\UtilsBundle\UtilsBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(OffersBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    UtilsBundle::class,
                    WEMPersonalDataManagerBundle::class,
                    Terminal42NotificationCenterBundle::class,
                ])
                ->setReplace(['wem-offers']),
        ];
    }
}
