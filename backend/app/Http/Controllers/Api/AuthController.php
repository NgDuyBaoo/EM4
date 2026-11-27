<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * @tags Xác thực
 */
class AuthController extends Controller
{
    /**
     * Đăng ký tài khoản khách và nhận token Sanctum.
     *
     * @unauthenticated
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^0\d{9}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);
        $user = User::create($data);

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'api', ['customer'])->plainTextToken,
            'user' => $this->userData($user),
        ], 201);
    }

    /**
     * Đăng nhập, nhận token Sanctum. Token nhân viên có quyền "staff".
     *
     * @unauthenticated
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu chưa đúng.']);
        }
        $abilities = $user->isStaff() ? ['customer', 'staff'] : ['customer'];

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'api', $abilities, now()->addDays(7))->plainTextToken,
            'user' => $this->userData($user),
        ]);
    }

    /** Thông tin tài khoản đang đăng nhập. */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userData($request->user()));
    }

    /** Đăng xuất: thu hồi token hiện tại. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    private function userData(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'role' => $user->role->value];
    }
}
