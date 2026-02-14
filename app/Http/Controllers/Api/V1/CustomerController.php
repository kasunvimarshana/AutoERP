<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Customer list endpoint - To be implemented']);
    }

    public function show($id)
    {
        return response()->json(['message' => 'Customer detail endpoint - To be implemented']);
    }

    public function store()
    {
        return response()->json(['message' => 'Customer creation endpoint - To be implemented']);
    }

    public function update($id)
    {
        return response()->json(['message' => 'Customer update endpoint - To be implemented']);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Customer deletion endpoint - To be implemented']);
    }
}
