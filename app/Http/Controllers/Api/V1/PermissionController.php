<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Permission list endpoint - To be implemented']);
    }

    public function show($id)
    {
        return response()->json(['message' => 'Permission detail endpoint - To be implemented']);
    }

    public function store()
    {
        return response()->json(['message' => 'Permission creation endpoint - To be implemented']);
    }

    public function update($id)
    {
        return response()->json(['message' => 'Permission update endpoint - To be implemented']);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Permission deletion endpoint - To be implemented']);
    }
}
