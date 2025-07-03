<?php

use App\Livewire\RepositoryManager;
use App\Livewire\RepositoryTracker;
use Illuminate\Support\Facades\Route;

// Home screen for managing repositories
Route::get('/', RepositoryManager::class)->name('home');

// The main git tracking view for a specific repository
Route::get('/repository/{repository}', RepositoryTracker::class)->name('git-tracker');