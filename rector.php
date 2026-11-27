<?php

declare(strict_types=1);

use Contao\Rector\Set\ContaoSetList;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/contao',
    ])
    ->withSkip([
        __DIR__ . '/contao/languages',
        __DIR__ . '/contao/templates',
    ])
    ->withPhpSets(php84: true)
    // The ContaoLevelSetList sets currently reference Symfony sets removed from rector 2.9,
    // so we load the Contao 5 sets one by one and let rector pick the Symfony rules from composer.
    ->withComposerBased(symfony: true)
    ->withSets([
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        ContaoSetList::CONTAO_50,
        ContaoSetList::CONTAO_51,
        ContaoSetList::CONTAO_53,
        ContaoSetList::ANNOTATIONS_TO_ATTRIBUTES,
        ContaoSetList::FQCN,
    ]);
