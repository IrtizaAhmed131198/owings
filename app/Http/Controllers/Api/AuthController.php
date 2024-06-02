<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use App\Traits\HandleResponse;
use App\Models\User;

class AuthController extends Controller
{
    use HandleResponse;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|in:2,3',
            'country' => 'required',
            'city' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->fail( 422 ,"Invalid credentials", $validator->errors()->first());
        }

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role_id = $request->role_id;
        $user->country = $request->country;
        $user->city = $request->city;
        $user->status = 1;
        $user->otp_code = $this->generateUniqueOtp();
        $user->otp_expires_at = now();
        $user->save();
        
        $role = $request->role_id == 2 ? 'Merchant' : 'Customer';

        $token = $user->createToken('MyApp')->plainTextToken;

        return $this->successWithData(['token' => $token, 'userId' => $user->id] , $role." account registered succesfully" , 200 );
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'roleId' => 'required|in:1,2,3',
        ]);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid credentials", $validator->errors()->first());
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'role_id' => $request->roleId,
        ];

        $user = User::where('email', $request->email)
                    ->where('role_id', $request->roleId)
                    ->first();

        if (!$user) {
            return $this->fail(422, "Invalid credentials", "User not found with provided email and role");
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->fail(422, "Invalid credentials", "Password is incorrect");
        }

        if ($user->status != 1) {
            return $this->fail(403, "Account inactive", "User account is not active");
        }

        $token = $user->createToken('MyApp')->plainTextToken;
        $user->remember_token = $token;
        $user->update();

        return $this->successWithData(['token' => $token, 'userId' => $user->id], "Login successful", 200);
    }

    private function generateUniqueOtp()
    {
        $otp = rand(100000, 999999);
        $existingOtpCount = DB::table('users')->where('otp_code', $otp)->count();

        if ($existingOtpCount > 0) {
            return $this->generateUniqueOtp();
        }

        return $otp;
    }
}
