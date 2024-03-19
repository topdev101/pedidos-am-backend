<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $token = auth()->attempt($validator->validated());
        if (!$token) {
            return $this->sendErrors(['username' => [__('auth.failed')]], 'Unauthorized', 422);
        }

        $auth_user = auth()->user();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        if($auth_user->role == 'secretary' && $auth_user->ip_address != '' && $ip_address != $auth_user->ip_address){
            return $this->sendErrors(['username' => [__('auth.not_allowed_pc')]], 'Unauthorized', 422);
        } else {
            $auth_user->update(['last_ip' => $ip_address]);
        }

        return $this->respondWithToken($token);
    }

    public function getAccount()
    {
        return response()->json(auth()->user());
    }


    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'auth_user' => auth()->user(),
            'expires_in' => auth('api')->factory()->getTTL() * 60 //mention the guard name inside the auth fn
        ]);
    }
}