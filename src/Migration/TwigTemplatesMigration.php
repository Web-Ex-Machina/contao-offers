<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

/**
 * Replace the names of the former PHP templates shipped with the bundle by their
 * Twig counterparts, unless the website still overrides the PHP template.
 */
class TwigTemplatesMigration extends AbstractMigration
{
    /**
     * @var array<string, array{0: string, 1: string, 2: string}> legacy name => [table, column, new name]
     */
    private const array TEMPLATES = [
        'offer_default' => ['tl_module', 'offer_template', ''],
        'offer_details' => ['tl_module', 'offer_template', 'offer/details'],
        'offer_alert_default' => ['tl_wem_offer_feed', 'tplOfferAlert', ''],
    ];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    public function shouldRun(): bool
    {
        foreach ($this->getTemplatesToMigrate() as $legacyName => [$table, $column]) {
            if ((int) $this->connection->fetchOne(\sprintf('SELECT COUNT(*) FROM %s WHERE %s = ?', $table, $column), [$legacyName]) > 0) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        foreach ($this->getTemplatesToMigrate() as $legacyName => [$table, $column, $newName]) {
            $this->connection->update($table, [
                $column => $newName,
            ], [
                $column => $legacyName,
            ]);
        }

        return $this->createResult(true);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    private function getTemplatesToMigrate(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $templates = [];

        foreach (self::TEMPLATES as $legacyName => [$table, $column, $newName]) {
            if (! $schemaManager->tablesExist([$table]) || ! isset($schemaManager->listTableColumns($table)[strtolower($column)])) {
                continue;
            }

            if ($this->isOverridden($legacyName)) {
                continue;
            }

            $templates[$legacyName] = [$table, $column, $newName];
        }

        return $templates;
    }

    private function isOverridden(string $legacyName): bool
    {
        if (! is_dir($this->projectDir . '/templates')) {
            return false;
        }

        return new Finder()->files()->in($this->projectDir . '/templates')->name($legacyName . '.html5')->hasResults();
    }
}
