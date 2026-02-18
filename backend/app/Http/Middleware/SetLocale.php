<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for detecting and setting application locale
 * Supports multi-language (i18n) based on request headers, user preferences, or tenant settings
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);
        
        // Set application locale
        App::setLocale($locale);
        
        // Store locale in request for later use
        $request->attributes->set('locale', $locale);

        return $next($request);
    }

    /**
     * Detect locale from various sources
     * Priority: Query param > Header > User preference > Tenant default > App default
     */
    protected function detectLocale(Request $request): string
    {
        // 1. Check query parameter
        if ($request->has('locale')) {
            $locale = $request->get('locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // 2. Check Accept-Language header or custom X-Locale header
        $headerLocale = $request->header('X-Locale') ?? $request->header('Accept-Language');
        if ($headerLocale) {
            $locale = $this->parseAcceptLanguage($headerLocale);
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // 3. Check authenticated user's preference
        if ($request->user() && isset($request->user()->locale)) {
            $locale = $request->user()->locale;
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // 4. Check tenant's default locale
        if ($request->attributes->has('tenant')) {
            $tenant = $request->attributes->get('tenant');
            if (isset($tenant->settings['default_locale'])) {
                $locale = $tenant->settings['default_locale'];
                if ($this->isValidLocale($locale)) {
                    return $locale;
                }
            }
        }

        // 5. Fall back to application default
        return config('app.locale', 'en');
    }

    /**
     * Parse Accept-Language header
     */
    protected function parseAcceptLanguage(string $header): string
    {
        // Parse Accept-Language header (e.g., "en-US,en;q=0.9,es;q=0.8")
        $languages = explode(',', $header);
        
        foreach ($languages as $language) {
            // Extract language code before semicolon
            $lang = explode(';', $language)[0];
            // Extract primary language (e.g., "en" from "en-US")
            $primaryLang = explode('-', trim($lang))[0];
            
            if ($this->isValidLocale($primaryLang)) {
                return $primaryLang;
            }
        }

        return config('app.locale', 'en');
    }

    /**
     * Check if locale is supported
     */
    protected function isValidLocale(string $locale): bool
    {
        $supportedLocales = config('app.supported_locales', ['en']);
        return in_array($locale, $supportedLocales);
    }
}
