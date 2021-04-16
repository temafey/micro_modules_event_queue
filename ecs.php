<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(ArraySyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'short',
        ]]);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $containerConfigurator->import(__DIR__ . '/vendor/symplify/easy-coding-standard/config/set/clean-code.php');

    $containerConfigurator->import(__DIR__ . '/vendor/symplify/easy-coding-standard/config/set/symfony.php');

    $containerConfigurator->import(__DIR__ . '/vendor/symplify/easy-coding-standard/config/set/php80-migration.php');

    $containerConfigurator->import(__DIR__ . '/vendor/symplify/easy-coding-standard/config/set/psr12.php');

    $parameters->set(Option::SETS, [
        // run and fix, one by one
        // SetList::SPACES,
        // SetList::ARRAY,
        // SetList::DOCBLOCK,
        // SetList::NAMESPACES,
        // SetList::CONTROL_STRUCTURES,
        // SetList::CLEAN_CODE,
        // SetList::PSR_12,
        // SetList::PHP_70,
        // SetList::PHP_71,
    ]);
};
