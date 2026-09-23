<?php

use App\Http\Controllers\Api\AgentController;
use Illuminate\Support\Facades\Route;

Route::post('/agent/checkin', [AgentController::class, 'checkin'])
    ->middleware(['throttle:30,1', 'agent.token'])
    ->name('api.agent.checkin');
