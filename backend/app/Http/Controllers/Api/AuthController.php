<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PatientProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|min:3|max:255|regex:/^[a-zA-Z\s.\'\-]+$/',
            'email'       => 'required|string|email|max:255|unique:users,email',
            'phone'       => ['nullable', 'string', 'max:20', 'regex:/^03[0-9]{2}-[0-9]{7}$/'],
            'password'    => 'required|string|min:8',
            'avatar'      => 'nullable|string',
            'gender'      => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
        ], [
            'name.required'      => 'Full name is required.',
            'name.min'           => 'Name must be at least 3 characters.',
            'name.regex'         => 'Name should contain letters only (no numbers or special characters).',
            'email.required'     => 'Email address is required.',
            'email.email'        => 'Please enter a valid email address.',
            'email.unique'       => 'This email address is already registered. Try logging in.',
            'phone.regex'        => 'Phone must be a valid Pakistani mobile number (e.g. 0300-1234567).',
            'password.required'  => 'Password is required.',
            'password.min'       => 'Password must be at least 8 characters long.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please correct the highlighted errors.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle avatar upload or selection
        $avatarPath = 'assets/images/default-patient.svg';

        if ($request->filled('avatar')) {
            $avatarInput = $request->avatar;

            if (str_starts_with($avatarInput, 'data:image/')) {
                @list($type, $data) = explode(';', $avatarInput);
                @list(, $data) = explode(',', $data);

                if ($data) {
                    $decoded = base64_decode($data);

                    $ext = str_contains($type, 'png')
                        ? 'png'
                        : (str_contains($type, 'webp') ? 'webp' : 'jpg');

                    $fileName = 'patient_' . time() . '_' . Str::random(6) . '.' . $ext;

                    Storage::disk('public')->put(
                        'avatars/' . $fileName,
                        $decoded
                    );

                    $avatarPath = 'storage/avatars/' . $fileName;
                }
            } else {
                $avatarPath = ltrim($avatarInput, '/');
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => 'patient',
            'status' => 'active',
            'password' => Hash::make($request->password),
            'avatar' => $avatarPath,
        ]);

        // Create Patient Profile
        PatientProfile::create([
            'user_id' => $user->id,
            'mrn' => 'HP-' . date('Y') . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            'gender' => $request->gender ?? 'male',
            'blood_group' => $request->blood_group ?? 'O+',
        ]);

        $token = $user->createToken('healthpulse_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful! Welcome to HealthPulse Hospital.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ]
        ], 201);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|string',
        ]);

        $user = $request->user();
        $avatarInput = $request->avatar;
        $avatarPath = null;

        if (str_starts_with($avatarInput, 'data:image/')) {
            @list($type, $data) = explode(';', $avatarInput);
            @list(, $data) = explode(',', $data);

            if ($data) {
                $decoded = base64_decode($data);

                $ext = str_contains($type, 'png')
                    ? 'png'
                    : (str_contains($type, 'webp') ? 'webp' : 'jpg');

                $rolePrefix = $user->role ?: 'user';

                $fileName = $rolePrefix . '_' . time() . '_' . Str::random(6) . '.' . $ext;

                Storage::disk('public')->put(
                    'avatars/' . $fileName,
                    $decoded
                );

                $avatarPath = 'storage/avatars/' . $fileName;
            }
        } else {
            $avatarPath = ltrim($avatarInput, '/');
        }

        if ($avatarPath) {
            // Remove previous custom uploaded avatar
            if ($user->avatar && str_starts_with($user->avatar, 'storage/avatars/')) {
                Storage::disk('public')->delete(
                    str_replace('storage/', '', $user->avatar)
                );
            }

            $user->avatar = $avatarPath;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated successfully.',
            'avatar' => $user->avatar,
            'user' => $user->load(['doctorProfile.department', 'patientProfile'])
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide valid credentials.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::with([
            'doctorProfile.department',
            'patientProfile'
        ])->where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address.',
                'errors' => [
                    'email' => ['No registered account found with this email address.']
                ]
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password entered.',
                'errors' => [
                    'password' => ['Incorrect password. Please verify and try again.']
                ]
            ], 401);
        }

        $token = $user->createToken('healthpulse_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'doctor_profile' => $user->doctorProfile,
                'patient_profile' => $user->patientProfile,
            ]
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load([
            'doctorProfile.department',
            'patientProfile'
        ]);

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}