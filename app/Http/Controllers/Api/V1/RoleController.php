<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Role list endpoint - To be implemented']);
    }

    public function show($id)
    {
        return response()->json(['message' => 'Role detail endpoint - To be implemented']);
    }

    public function store()
    {
        return response()->json(['message' => 'Role creation endpoint - To be implemented']);
    }

    public function update($id)
    {
        return response()->json(['message' => 'Role update endpoint - To be implemented']);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Role deletion endpoint - To be implemented']);
    }
}
