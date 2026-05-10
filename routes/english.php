<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\English\EnglishController; 

Route::get('/', [EnglishController::class, 'index']);
