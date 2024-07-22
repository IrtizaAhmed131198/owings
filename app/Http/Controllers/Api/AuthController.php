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
use Carbon\Carbon;
use App\Traits\HandleResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use HandleResponse;

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone_number' => 'required|max:15',
            'roleId' => 'required|in:2,3',
            'country' => 'required',
            'city' => 'required',
        ];

        if ($request->roleId == 2) {
            $rules['business_name'] = 'required|string|max:255';
            $rules['poc'] = 'required';
            $rules['poc_cell'] = 'required';
            $rules['images.*'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid credentials", $validator->errors()->first());
        }

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role_id = $request->roleId;
        $user->phoneNumber = $request->phone_number;
        $user->country = $request->country;
        $user->city = $request->city;
        $user->otp_code = $this->generateUniqueOtp();
        $user->otp_expires_at = now();

        if ($request->roleId == 2) {
            $user->business_name = $request->business_name;
            $user->poc = $request->poc;
            $user->poc_cell = $request->poc_cell;
        }

        $user->save();

        $imagePaths = [];

        if ($request->roleId == 2 && $request->hasFile('images')) {
            $images = $request->file('images');
            if (count($images) > 3) {
                User::find($user->id)->delete();
                return $this->fail(422, "You can only upload a maximum of 3 images.");
            }

            foreach ($images as $image) {
                $imagePath = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('asset/uploads/merchant_images'), $imagePath);

                DB::table('multi_images')->insert([
                    'user_id' => $user->id,
                    'image_path' => 'asset/uploads/merchant_images/' . $imagePath
                ]);

                $imagePaths[] = 'asset/uploads/merchant_images/' . $imagePath;
            }
        } elseif ($request->roleId == 2) {
            // If role is 2 but no images are uploaded, delete the user
            User::find($user->id)->delete();
            return $this->fail(422, "Image upload failed.");
        }

        $role = $request->roleId == 2 ? 'Merchant' : 'Customer';

        $token = $user->createToken('MyApp')->plainTextToken;

        $this->sendOtpEmail($user->email, $user->name, $user->otp_code, $user->otp_expires_at, 'register');

        // Build the response data conditionally
        $responseData = [
            'token' => $token,
            'userId' => $user->id,
        ];

        if ($request->roleId == 2) {
            $responseData['images'] = $imagePaths;
        }

        return $this->successWithData(
            $responseData,
            $role . " account registered, wait for OTP verification",
            200
        );
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

        if ($user->otp_code != $request->otpCode) {
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

    public function editProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore(Auth::guard('sanctum')->user()->id),
            ],
            'aboutUs' => 'nullable|string',
            'phoneNumber' => 'nullable|string|regex:/^\+?[0-9]{10,15}$/',
            'whatsapp' => [
                'nullable',
                'regex:/^https:\/\/wa\.me\/[0-9]{10,15}\?text=.*$/',
            ],
            'instagram' => [
                'nullable',
                'regex:/^https:\/\/www\.instagram\.com\/[a-zA-Z0-9._]+\/$/',
            ],
        ]);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid credentials", $validator->errors()->first());
        }

        $user = User::find(Auth::guard('sanctum')->user()->id);
        if ($user) {
            $user->email = $request->email;
            $user->aboutUs = $request->aboutUs;
            $user->phoneNumber = $request->phoneNumber;
            $user->whatsapp = $request->whatsapp;
            $user->instagram = $request->instagram;
            $user->save();

            return $this->successMessage("Profile updated successfully");
        } else {
            return $this->badRequestResponse("User not found");
        }
    }

    public function getEditProfile()
    {
        $user = User::find(Auth::guard('sanctum')->user()->id);
        if ($user) {
            $profileData = [
                'userId' => Auth::guard('sanctum')->user()->id,
                'email' => $user->email,
                'aboutUs' => $user->aboutUs,
                'phoneNumber' => $user->phoneNumber,
                'whatsapp' => $user->whatsapp,
                'instagram' => $user->instagram,
            ];

            return $this->successWithData($profileData, "Profile get successfully", 200);
        } else {
            return $this->badRequestResponse("User not found");
        }

    }

    public function getUsers()
    {
        $users = User::where('status', 1)->get();
        return $this->successWithData($users, "Fetch Users", 200);
    }

    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer|exists:users,id',
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore($request->userId),
            ],
        ]);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid credentials", $validator->errors()->first());
        }

        $user = User::find($request->userId);
        if ($user) {
            $user->email = $request->email;
            $user->save();

            return $this->successMessage("Email updated successfully");
        } else {
            return $this->badRequestResponse("User not found");
        }
    }

    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->fail(404, "User not found");
        }

        $user->delete();

        return $this->successMessage("User deleted successfully");
    }

}
