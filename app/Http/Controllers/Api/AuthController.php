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
use App\Mail\OtpMail;
use App\Mail\ActivateMail;
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
            'phoneNumber' => 'required|max:15',
            'roleId' => 'required|in:2,3',
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
        $user->role_id = $request->roleId;
        $user->phoneNumber = $request->phoneNumber;
        $user->country = $request->country;
        $user->city = $request->city;
        $user->status = 1;
        $user->otp_code = $this->generateUniqueOtp();
        $user->otp_expires_at = now();
        $user->save();

        $role = $request->roleId == 2 ? 'Merchant' : 'Customer';

        $token = $user->createToken('MyApp')->plainTextToken;

        $this->sendOtpEmail($user->email, $user->name, $user->otp_code, $user->otp_expires_at, 'register');

        return $this->successWithData(['token' => $token, 'userId' => $user->id] , $role." account registered wait for otp verification" , 200 );
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

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'otpCode' => 'required|max:6',
        ]);


        if ($validator->fails()) {
            return $this->fail(422, "Invalid input", $validator->errors()->first());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->fail(404, "User not found", "No user found with this email address.");
        }

        if ($user->otp_code !== $request->otpCode) {
            return $this->fail(401, "Invalid OTP", "The OTP code is incorrect.");
        }

        if ($user->otp_expires_at > now()) {
            return $this->fail(401, "Expired OTP", "The OTP code has expired.");
        }

        $user->status = 1;
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        try {
            Mail::to($user->email)->send(new ActivateMail($user->name)); // Assuming you have a WelcomeMail Mailable class
        } catch (\Exception $e) {
            return $this->successMessage("Account activated successfully");
        }

        return $this->successMessage("Account activated successfully but email not send!");
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid input", $validator->errors()->first());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->fail(404, "User not found", "No user found with this email address.");
        }

        if($user->status == 1){
            return $this->successMessage("Your account already verify. You do not need to resend otp.", 201);
        }

        $otp = $this->generateUniqueOtp();
        $user->otp_code = $otp;
        $user->otp_expires_at = now();
        $user->save();

        $this->sendOtpEmail($user->email, $user->name, $otp, $user->otp_expires_at, "resendOtp");

        return $this->successMessage("OTP resent successfully");
    }


    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid credentials", $validator->errors()->first());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->fail(422, "Invalid credentials", "User not found with provided email");
        }

        $otp = $this->generateUniqueOtp();
        $user->otp_code = $otp;
        $user->otp_expires_at = now();
        $user->save();

        $this->sendOtpEmail($user->email, $user->name, $otp, $user->otp_expires_at, "forgotPassword");

        return $this->successMessage("Password reset otp sent successfully");
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otpCode' => 'required|max:6',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid credentials", $validator->errors()->first());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->fail(422, "Invalid credentials", "User not found with provided email");
        }

        if ($user->otp_code !== $request->otpCode) {
            return $this->fail(401, "Invalid OTP", "The OTP code is incorrect.");
        }

        if ($user->otp_expires_at > now()) {
            return $this->fail(401, "Expired OTP", "The OTP code has expired.");
        }

        $user->password = Hash::make($request->password);
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        return $this->successMessage("Password reset successfully");
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

    private function sendOtpEmail($email, $name, $otp, $expiresAt, $type)
    {
        try {
            Mail::to($email)->send(new OtpMail($name, $otp, $expiresAt, $type));
        } catch (\Exception $e) {
            return $this->fail(500, "Failed to resend OTP email", "An error occurred while sending the OTP email.");
        }
    }
}
