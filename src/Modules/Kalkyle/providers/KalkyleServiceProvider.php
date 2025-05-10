<?php

namespace Modules\Kalkyle\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class KalkyleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Laster modulens routes (valgfritt, kan fjernes hvis du ikke trenger det ennå)
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }

    public function register(): void
    {
        //
    }
}

