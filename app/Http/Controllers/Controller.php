<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="AutoERP API",
 *     description="Production-ready, modular ERP-grade SaaS platform API",
 *
 *     @OA\Contact(
 *         email="support@autoerp.com"
 *     ),
 *
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://autoerp.com/license"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 * @OA\Server(
 *     url="https://api.autoerp.com",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum token authentication"
 * )
 */
abstract class Controller
{
    //
}
