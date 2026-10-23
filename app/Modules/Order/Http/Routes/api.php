<?php

use App\Modules\Order\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{id}', [OrderController::class, 'show'])->name('orders.show');
