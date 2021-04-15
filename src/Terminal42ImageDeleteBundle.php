<?php

declare(strict_types=1);

namespace Terminal42\ImageDeleteBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42ImageDeleteBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
