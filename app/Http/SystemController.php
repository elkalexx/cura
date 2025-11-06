<?php

namespace App\Http;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    public function runMigration()
    {
        try {
            \Artisan::call('migrate', ['--force' => true]);

            Log::info('Migrations run successfully via web request.');

            return back()->with('success', 'Migrations completed successfully');
        } catch (\Throwable $e) {
            Log::error('Error running migrations: '.$e->getMessage());

            return back()->withErrors([
                'migration_error' => 'An error occurred while running migrations: '.$e->getMessage(),
            ]);
        }
    }
}
