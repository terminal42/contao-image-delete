<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Terminal42\ImageDeleteBundle\Controller\ImageDeleteController;

return static function (RoutingConfigurator $routes): void {
    $routes->add('terminal42_image_delete', '/contao/image-delete/')
        ->controller(ImageDeleteController::class)
        ->defaults(['_scope' => 'backend', '_token_check' => true])
    ;
};
