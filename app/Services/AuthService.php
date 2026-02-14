<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Hash password
            $data['password'] = Hash::make($data['password']);

            // Create user
            $user = $this->userRepository->create($data);

            // Log registration
            \Log::info('User registered', ['user_id' => $user->id]);

            return $user;
        });
    }

    public function login(array $credentials)
    {
        if (!auth()->attempt($credentials)) {
            throw new \Exception('Invalid credentials');
        }

        $user = auth()->user();
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log successful login
        \Log::info('User logged in', ['user_id' => $user->id]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout($user)
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Log logout
        \Log::info('User logged out', ['user_id' => $user->id]);

        return true;
    }
}
