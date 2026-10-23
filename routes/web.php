<?php

use Illuminate\Support\Facades\Route;

// No public site: the root leads to the admin panel.
Route::redirect('/', '/admin');
