<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'message' => 'Analytics dashboard',
            'data' => [
                'total_revenue' => 0,
                'total_customers' => 0,
                'total_products' => 0,
                'total_invoices' => 0,
            ]
        ]);
    }
}
