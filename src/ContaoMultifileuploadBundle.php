<?php

declare(strict_types=1);

namespace BohnMedia\ContaoMultifileuploadBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoMultifileuploadBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}