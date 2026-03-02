<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="KV Enterprise ERP/CRM SaaS API",
 *     description="Production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform API. All endpoints require tenant context via the X-Tenant-ID header or JWT claim.",
 *     @OA\Contact(
 *         email="api@kv-enterprise.com"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="API v1"
 * )
 */
abstract class Controller
{
    //
}
