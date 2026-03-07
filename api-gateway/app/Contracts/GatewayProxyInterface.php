<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Contract for proxying requests to downstream microservices.
 *
 * Keeps the gateway routing layer decoupled from the HTTP client
 * implementation (Guzzle, Symfony HttpClient, etc.).
 */
interface GatewayProxyInterface
{
    /**
     * Forward a request to a downstream service.
     *
     * @param  string   $serviceUrl  Base URL of the target service.
     * @param  Request  $request     Incoming HTTP request to forward.
     * @return \Illuminate\Http\Response
     */
    public function forward(string $serviceUrl, Request $request): Response;
}
