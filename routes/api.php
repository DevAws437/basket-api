<?php

use App\Http\Controllers\Api\ActionController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

Route::apiResource('teams', TeamController::class)->except('edit', 'create');
Route::apiResource('players', PlayerController::class)->except('edit', 'create');

Route::post('/matches', [MatchController::class, 'store']);
Route::get('/matches', [MatchController::class, 'index']);
Route::get('/matches/{match}', [MatchController::class, 'show']);
Route::patch('/matches/{match}/start', [MatchController::class, 'start']);
Route::patch('/matches/{match}/pause', [MatchController::class, 'togglePause']);
Route::patch('/matches/{match}/opponent-score', [MatchController::class, 'updateOpponentScore']);
Route::post('/matches/{match}/end-period', [MatchController::class, 'endPeriod']);
Route::post('/matches/{match}/overtime', [MatchController::class, 'addOvertime']);
Route::post('/matches/{match}/end', [MatchController::class, 'endMatch']);

Route::post('/matches/{match}/actions', [ActionController::class, 'record']);
Route::post('/matches/{match}/substitute', [ActionController::class, 'substitute']);
Route::post('/matches/{match}/undo', [ActionController::class, 'undo']);

Route::get('/matches/{match}/team-stats', [StatisticsController::class, 'teamStats']);
Route::get('/matches/{match}/player-stats', [StatisticsController::class, 'playerStats']);
Route::get('/matches/{match}/players/{player}', [StatisticsController::class, 'playerDetail']);
Route::get('/matches/{match}/mvp', [StatisticsController::class, 'mvp']);
Route::get('/matches/{match}/flow', [StatisticsController::class, 'flow']);

Route::get('/records', [RecordController::class, 'index']);
Route::get('/records/{match}', [RecordController::class, 'show']);
Route::get('/records/{match}/export', [RecordController::class, 'export']);
