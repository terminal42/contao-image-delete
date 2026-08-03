<?php

declare(strict_types=1);

namespace Terminal42\ImageDeleteBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class Terminal42ImageDeleteBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator
            ->services()
            ->load(__NAMESPACE__.'\\', '../src/')
            ->autoconfigure()
            ->autowire()
        ;
    }
}
