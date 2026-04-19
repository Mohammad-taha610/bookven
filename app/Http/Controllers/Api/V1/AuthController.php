<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginUserRequest;
use App\Http\Requests\Api\RegisterUserRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'role' => UserRole::User,
        ]);

        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        $user->load('branches');

        return $this->jsonSuccess([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Registration successful.', 201);
    }

    public function login(LoginUserRequest $request)
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->jsonError('Invalid credentials.', 401);
        }

        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();
        $user->load('branches');
        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return $this->jsonSuccess([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Logged in.');
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('branches');

        return $this->jsonSuccess(new UserResource($user));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->jsonSuccess(null, 'Logged out.');
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->jsonSuccess(null, __($status));
        }

        return $this->jsonError(__($status), 422);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->jsonSuccess(null, __($status));
        }

        return $this->jsonError(__($status), 422);
    }
}
