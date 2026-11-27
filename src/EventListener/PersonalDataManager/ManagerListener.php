<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\EventListener\PersonalDataManager;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use WEM\OffersBundle\Model\Application;

class ManagerListener
{
    /**
     * Table of the optional webexmachina/contao-form-data-manager bundle.
     */
    public const FORM_STORAGE_TABLE = 'tl_sm_form_storage';

    public function __construct(
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        #[Autowire(param: 'contao.backend.route_prefix')]
        private readonly string $backendRoutePrefix,
    ) {
    }

    public function getHrefByPidAndPtableAndEmail(int $pid, string $ptable, string $email, string $href): string
    {
        return match ($ptable) {
            Application::getTable() => $this->buildHref('wem-offers', Application::getTable(), $pid),
            self::FORM_STORAGE_TABLE => $this->buildHref('form', self::FORM_STORAGE_TABLE, $pid),
            default => $href,
        };
    }

    private function buildHref(string $do, string $table, int $id): string
    {
        return \sprintf('%s?do=%s&table=%s&id=%s&act=edit&rt=%s', $this->backendRoutePrefix, $do, $table, $id, $this->csrfTokenManager->getDefaultTokenValue());
    }
}
