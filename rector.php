<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Contao\Rector\Set\ContaoSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withImportNames()
    ->withSets([
        SetList::PHP_74,
        ContaoSetList::CONTAO_413,
    ])
    ->withPreparedSets(
        codeQuality: true,
        codingStyle: true,
        doctrineCodeQuality: true,
        deadCode: true,
        earlyReturn: true,
        instanceOf: true,
        typeDeclarations: true,
        strictBooleans: true,
        symfonyCodeQuality: true
    );