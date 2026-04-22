<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Don't redirect if we are already on the install page or if it's a console command
        if ($request->is('install*') || app()->runningInConsole()) {
            return $next($request);
        }

        try {
            // Check if users table exists and has at least one user
            if (!Schema::hasTable('users') || DB::table('users')->count() === 0) {
                return redirect()->route('install.index');
            }
        } catch (\Exception $e) {
            // If database connection fails or table doesn't exist, redirect to install
            return redirect()->route('install.index');
        }

        return $next($request);
    }
}
