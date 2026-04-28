<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/restaurant/login');
});

require __DIR__.'/../app/Modules/Restaurant/Routes/web.php';
require __DIR__.'/../app/Modules/Admin/Routes/web.php';