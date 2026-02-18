<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 * 
 * Adds security-related HTTP headers to all responses
 * Implements OWASP best practices for web application security
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers to response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable browser XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Enforce HTTPS
        if (config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Content Security Policy
        // Start with restrictive policy. For production, use nonces for inline scripts
        // and eliminate unsafe-eval by refactoring code that uses eval()
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self'", // Remove unsafe-inline and unsafe-eval for production
            "style-src 'self'", // Use external stylesheets or nonces
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests", // Force HTTPS
        ]);
        
        // In development, allow inline scripts for debugging
        if (config('app.debug')) {
            $csp = str_replace("script-src 'self'", "script-src 'self' 'unsafe-inline' 'unsafe-eval'", $csp);
            $csp = str_replace("style-src 'self'", "style-src 'self' 'unsafe-inline'", $csp);
        }
        
        $response->headers->set('Content-Security-Policy', $csp);

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature Policy)
        $permissions = implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()',
        ]);
        $response->headers->set('Permissions-Policy', $permissions);

        return $response;
    }
}
