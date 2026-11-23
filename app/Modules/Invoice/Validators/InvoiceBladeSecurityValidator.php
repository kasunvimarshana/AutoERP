<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Validators;

use Illuminate\Support\Facades\Blade;
use Illuminate\Validation\ValidationException;
use Throwable;

final class InvoiceBladeSecurityValidator
{
    /** @var list<string> */
    private const FORBIDDEN_DIRECTIVES = [
        'php',
        'endphp',
        'include',
        'includeif',
        'includewhen',
        'includeunless',
        'includefirst',
        'each',
        'inject',
        'extends',
        'section',
        'yield',
        'push',
        'prepend',
        'stack',
        'component',
        'endcomponent',
        'slot',
        'endslot',
    ];

    /** @var list<string> */
    private const FORBIDDEN_FUNCTIONS = [
        'exec',
        'passthru',
        'shell_exec',
        'system',
        'proc_open',
        'popen',
        'pcntl_exec',
        'eval',
        'assert',
        'create_function',
        'call_user_func',
        'call_user_func_array',
        'preg_replace_callback',
        'unserialize',
        'file_get_contents',
        'file_put_contents',
        'unlink',
        'rmdir',
        'mkdir',
        'readfile',
        'fopen',
        'file',
        'glob',
        'copy',
        'rename',
        'scandir',
        'include',
        'require',
        'include_once',
        'require_once',
        'reflectionclass',
        'reflectionfunction',
        'reflectionmethod',
        'phpinfo',
        'getenv',
        'putenv',
        'env',
        'config',
        'app',
        'resolve',
        'request',
        'response',
        'redirect',
        'session',
        'cookie',
        'auth',
        'broadcast',
        'event',
        'dispatch',
        'cache',
        'artisan',
        'storage_path',
        'base_path',
        'app_path',
        'public_path',
        'database_path',
        'config_path',
    ];

    /** @var list<string> */
    private const FORBIDDEN_TOKENS = [
        '$_GET',
        '$_POST',
        '$_SERVER',
        '$_ENV',
        '$_COOKIE',
        '$_SESSION',
        '$_REQUEST',
        '$GLOBALS',
        'DB::',
        'Schema::',
        'Model::',
        'Eloquent::',
    ];

    /**
     * Validate the provided Blade template content for security and syntax validity.
     *
     * @throws ValidationException
     */
    public function validate(string $content): void
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'blade_content' => ['The Blade template content cannot be empty.'],
            ]);
        }

        // 1. Check for raw PHP opening tags
        if (preg_match('/<\?(?:php|=)?/i', $trimmed) === 1 || preg_match('/<script\b[^>]*\blanguage\s*=\s*["\']?php/i', $trimmed) === 1) {
            throw ValidationException::withMessages([
                'blade_content' => ['Raw PHP tags (<?php, <?=, <?) are strictly forbidden in custom templates. Use safe Blade syntax instead.'],
            ]);
        }

        // 2. Check for active JavaScript / script tags in HTML
        if (preg_match('/<script\b[^>]*>|javascript:/i', $trimmed) === 1) {
            throw ValidationException::withMessages([
                'blade_content' => ['Custom templates must not contain active JavaScript or <script> tags.'],
            ]);
        }

        // 3. Check for forbidden Blade directives
        foreach (self::FORBIDDEN_DIRECTIVES as $directive) {
            if (preg_match('/@'.preg_quote($directive, '/').'\b/i', $trimmed) === 1) {
                throw ValidationException::withMessages([
                    'blade_content' => ["The Blade directive '@{$directive}' is forbidden for security isolation."],
                ]);
            }
        }

        // 4. Check for forbidden tokens and superglobals
        foreach (self::FORBIDDEN_TOKENS as $token) {
            if (stripos($trimmed, $token) !== false) {
                throw ValidationException::withMessages([
                    'blade_content' => ["Direct access to '{$token}' is strictly forbidden."],
                ]);
            }
        }

        // 5. Check for forbidden function calls in template expressions
        foreach (self::FORBIDDEN_FUNCTIONS as $fn) {
            if (preg_match('/\b'.preg_quote($fn, '/').'\s*\(/i', $trimmed) === 1) {
                throw ValidationException::withMessages([
                    'blade_content' => ["Function call '{$fn}()' is not permitted in custom Blade templates."],
                ]);
            }
        }

        // 6. Test Blade compilation and compiled PHP tokens
        $this->validateCompiledSyntax($trimmed);
    }

    /**
     * Compiles the Blade string and validates the PHP tokens.
     *
     * @throws ValidationException
     */
    private function validateCompiledSyntax(string $content): void
    {
        try {
            $compiled = Blade::compileString($content);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'blade_content' => ['Blade syntax error: '.$e->getMessage()],
            ]);
        }

        // Tokenize compiled PHP
        $tokens = token_get_all($compiled);
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $tokenName = token_name($token[0]);
                $tokenValue = mb_strtolower($token[1]);

                // Ban eval, include, require tokens
                if (in_array($token[0], [T_EVAL, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
                    throw ValidationException::withMessages([
                        'blade_content' => ["Forbidden construct detected in compiled template ({$tokenName})."],
                    ]);
                }

                // Check string tokens for forbidden functions
                if ($token[0] === T_STRING && in_array($tokenValue, self::FORBIDDEN_FUNCTIONS, true)) {
                    throw ValidationException::withMessages([
                        'blade_content' => ["Forbidden function '{$token[1]}()' detected in compiled template."],
                    ]);
                }
            }
        }
    }
}
