<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:allbazar', function () {
    $this->comment('AllBazar marketplace scaffold is ready.');
});
