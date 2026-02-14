<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'User list endpoint - To be implemented']);
    }

    public function show($id)
    {
        return response()->json(['message' => 'User detail endpoint - To be implemented']);
    }

    public function store()
    {
        return response()->json(['message' => 'User creation endpoint - To be implemented']);
    }

    public function update($id)
    {
        return response()->json(['message' => 'User update endpoint - To be implemented']);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'User deletion endpoint - To be implemented']);
    }
}
