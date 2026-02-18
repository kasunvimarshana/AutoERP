<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for detecting and setting timezone
 * Supports multi-timezone handling based on user preferences or tenant settings
 */
class SetTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $this->detectTimezone($request);
        
        // Set application timezone for this request
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        Date::setTimezone($timezone);
        
        // Store timezone in request for later use
        $request->attributes->set('timezone', $timezone);

        return $next($request);
    }

    /**
     * Detect timezone from various sources
     * Priority: Header > User preference > Tenant default > App default
     */
    protected function detectTimezone(Request $request): string
    {
        // 1. Check X-Timezone header
        if ($request->hasHeader('X-Timezone')) {
            $timezone = $request->header('X-Timezone');
            if ($this->isValidTimezone($timezone)) {
                return $timezone;
            }
        }

        // 2. Check authenticated user's timezone preference
        if ($request->user() && isset($request->user()->timezone)) {
            $timezone = $request->user()->timezone;
            if ($this->isValidTimezone($timezone)) {
                return $timezone;
            }
        }

        // 3. Check tenant's default timezone
        if ($request->attributes->has('tenant')) {
            $tenant = $request->attributes->get('tenant');
            if (isset($tenant->settings['default_timezone'])) {
                $timezone = $tenant->settings['default_timezone'];
                if ($this->isValidTimezone($timezone)) {
                    return $timezone;
                }
            }
        }

        // 4. Fall back to application default
        return config('app.timezone', 'UTC');
    }

    /**
     * Check if timezone is valid
     */
    protected function isValidTimezone(string $timezone): bool
    {
        try {
            new \DateTimeZone($timezone);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
