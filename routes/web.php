<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Members routes
    Volt::route('members', 'members.index')->name('members.index');
    Volt::route('members/create', 'members.create')->name('members.create');
    Volt::route('members/{member}/edit', 'members.edit')->name('members.edit');
    Volt::route('members/{member}', 'members.show')->name('members.show');

    // Contributions routes
    Volt::route('contributions', 'contributions.index')->name('contributions.index');
    Volt::route('contributions/create', 'contributions.create')->name('contributions.create');
    Volt::route('contributions/submit', 'contributions.submit')->name('contributions.submit');
    Volt::route('contributions/review', 'contributions.review')->name('contributions.review');

    // Loans routes
    Volt::route('loans', 'loans.index')->name('loans.index');
    Volt::route('loans/create', 'loans.create')->name('loans.create');
    Volt::route('loans/{loan}', 'loans.show')->name('loans.show');
    Volt::route('loans/review', 'loans.review')->name('loans.review');

    // Eligibility routes
    Volt::route('eligibility/check', 'eligibility.check')->name('eligibility.check');

    // Reports routes
    Volt::route('reports', 'reports.index')->name('reports.index');
    Volt::route('reports/contributions', 'reports.contributions')->name('reports.contributions');
    Volt::route('reports/loans', 'reports.loans')->name('reports.loans');
    Volt::route('reports/dependents', 'reports.dependents')->name('reports.dependents');

    // User Management routes (Admin only)
    Volt::route('users', 'users.index')->name('users.index');
    Volt::route('users/create', 'users.create')->name('users.create');
    Volt::route('users/{user}/edit', 'users.edit')->name('users.edit');
});
