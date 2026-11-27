<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' =>
"Contao Offers Bundle for Contao Open Source CMS
@author     Web Ex Machina

@see        https://github.com/Web-Ex-Machina/contao-offers
@license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt"])
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/contao',
    ])
    ->withSkip([
        ArrayOpenerAndCloserNewlineFixer::class,
        __DIR__ . '/contao/languages',
        __DIR__ . '/contao/templates',
        __DIR__ . '/vendor',
    ])
    // The "symplify" prepared set has been removed from ECS 13
    ->withPreparedSets(
        psr12: true,
        common: true,
    );
