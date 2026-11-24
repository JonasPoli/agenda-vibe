<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Os horários dos eventos são gravados e exibidos no horário de Brasília.
        date_default_timezone_set('America/Sao_Paulo');

        parent::boot();
    }
}
