<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function user(Request $request)
    {
        return response()->json([
            'data' => Auth::user()->only('name', 'email')
        ]);
    }

    public function login(Request $request)
    {
        $credentials = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'password' => 'required',
        ]);
        if ($credentials->fails()) {
            return response()->json(["errors" => $credentials->messages()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['errors' => ['message' => 'Unauthorized']], 401);
        }

        return $this->respondWithToken($user, $user->createToken($user->name)->plainTextToken);
    }

    public function register(Request $request)
    {
        $credentials = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]);
        if ($credentials->fails()) {
            return response()->json(["errors" => $credentials->messages()], 422);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return $this->respondWithToken($user, $user->createToken($user->name)->plainTextToken);
    }

    protected function respondWithToken($user, $token)
    {
        return response()->json([
            'data' => $user->only('name', 'email'),
            'meta' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => 60 * 24
            ]
        ]);
    }
}
