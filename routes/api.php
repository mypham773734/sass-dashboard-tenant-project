<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\English\EnglishController; 

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('random', [EnglishController::class, 'generateMessage']); 

Route::post('score', [EnglishController::class, 'scoreGrammar']); 

Route::post('suggest', [EnglishController::class, 'suggetMessages']); 
