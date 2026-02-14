<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class InvoiceController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Invoice list endpoint - To be implemented']);
    }

    public function show($id)
    {
        return response()->json(['message' => 'Invoice detail endpoint - To be implemented']);
    }

    public function store()
    {
        return response()->json(['message' => 'Invoice creation endpoint - To be implemented']);
    }

    public function update($id)
    {
        return response()->json(['message' => 'Invoice update endpoint - To be implemented']);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Invoice deletion endpoint - To be implemented']);
    }
}
