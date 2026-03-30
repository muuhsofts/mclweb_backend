<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Cookie;
use Illuminate\Support\Facades\Log;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Exception;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['register', 'login', 'redirectToGoogle', 'handleGoogleCallback']);
    }

    public function register(Request $request)
    {
        // Validate user input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'role_id' => 'nullable|integer',
            'status' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        // Set default values
        $validatedData['role_id'] = $validatedData['role_id'] ?? 2;
        $validatedData['status'] = 'is_active';
        $validatedData['password'] = Hash::make($validatedData['password']);

        try {
            // Create the user
            $user = User::create($validatedData);

            // Generate login link
            $loginLink = url('http://localhost:5173/');

            // Prepare email content
            $emailBody = "Dear {$user->name},\n\n" .
                        "Your account has been successfully created. You can log in using the following link:\n" .
                        "{$loginLink}\n\n" .
                        "Thank you for joining us!";

            // Attempt to send email
            try {
                Mail::raw($emailBody, function ($message) use ($user) {
                    $message->to($user->email)
                            ->subject('Welcome To Our ASAS EVALUATION SYSTEM');
                });
            } catch (\Exception $e) {
                \Log::error('Email sending failed: ' . $e->getMessage());
                return response()->json([
                    'message' => 'User created successfully, but email notification failed.',
                    'user' => $user,
                ], 201);
            }

            return response()->json([
                'message' => 'User created successfully. A confirmation email has been sent.',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('User creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'User creation failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        \Log::info('Login request data: ', $request->all());

        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
        ]);

        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check for user existence and password validity
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            \Log::info('Invalid credentials for email: ' . $credentials['email']);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Check if the account is active
        if ($user->status !== 'is_active') {
            \Log::info('Inactive account for email: ' . $credentials['email']);
            return response()->json(['message' => 'Account is not active'], 403);
        }

        try {
            // Create token with an expiration time of 8 hours
            $token = $user->createToken('authToken', [], Carbon::now()->addHours(8))->plainTextToken;

            // Log the login activity to the audit_trail table
            \DB::table('audit_trail')->insert([
                'user_id' => $user->user_id,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'action' => 'login',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Store token in local storage on the client-side
            \Log::info('Login successful for email: ' . $credentials['email']);

            return response()->json([
                'user' => $user,
                'token' => $token,
                'role_id' => $user->role_id
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error during login: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function redirectToGoogle()
    {
        return response()->json([
            'url' => Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl(),
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            // Log incoming request data for debugging
            \Log::info('Google Callback Request Query:', $request->all());

            // Get the authorization code from the request
            $code = $request->query('code');
            if (!$code) {
                throw new Exception('No authorization code provided in request.');
            }

            // Exchange the code for an access token and user details
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromCode($code);

            // Find or create user
            $user = User::updateOrCreate(
                ['email' => $googleUser->email],
                [
                    'name' => $googleUser->name,
                    'role_id' => 3,
                    'status' => 'is_active',
                ]
            );

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Return response with token, user, and role_id
            return response()->json([
                'token' => $token,
                'user' => $user,
                'role_id' => $user->role_id,
            ], 200);
        } catch (Exception $e) {
            \Log::error('Google callback failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        $cookie = cookie()->forget('auth_token');

        return response()->json(['message' => 'Logged out successfully'])->cookie($cookie);
    }

    public function getLoggedUserProfile(Request $request)
    {
        // Eager load the 'role' relationship
        $user = $request->user()->load(['role']);

        return response()->json([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'role_id' => $user->role_id,
            'category' => $user->role->category ?? null,
            'status' => $user->status,
            'email' => $user->email,
        ]);
    }

    public function getLoggedUserName(Request $request)
    {
        // Fetch only the logged-in user's name
        $user = $request->user();

        return response()->json([
            'name' => $user->name,
        ]);
    }

    public function getLoggedUserEmail(Request $request)
    {
        // Fetch only the logged-in user's email
        $user = $request->user();

        return response()->json([
            'email' => $user->email,
        ]);
    }

    public function updateProfile(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'new_password' => 'required|string|min:8|confirmed',
            'name' => 'nullable|string|max:255',
            'role_id' => 'nullable|integer',
            'status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the authenticated user
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Check if the provided email matches the authenticated user's email
        if ($request->email !== $user->email) {
            return response()->json(['message' => 'Email does not match the logged-in user'], 403);
        }

        // Update user attributes
        $user->password = Hash::make($request->new_password);
        $user->name = $request->name ?? $user->name;
        $user->role_id = $request->role_id ?? $user->role_id;
        $user->status = $request->status ?? $user->status;

        // Save the user and check if it was successful
        if ($user->save()) {
            return response()->json(['message' => 'Profile updated successfully']);
        } else {
            return response()->json(['message' => 'Failed to update profile'], 500);
        }
    }

    public function users(Request $request)
    {
        try {
            // Eager load the 'role' relationship
            $users = User::with(['role'])
                     ->orderBy('user_id', 'desc')
                     ->get()
                     ->map(function ($user) {
                         return [
                             'user_id' => $user->user_id,
                             'name' => $user->name,
                             'role_id' => $user->role_id,
                             'status' => $user->status,
                             'email' => $user->email,
                             'role' => optional($user->role)->category,
                             'created_at' => $user->created_at,
                         ];
                     });

            // Return the response with HTTP status 200
            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error fetching users: ' . $e->getMessage());

            // Return a JSON error response with HTTP status 500
            return response()->json(['error' => 'Failed to fetch users.'], 500);
        }
    }

    public function getUsersForDropdown(Request $request)
    {
        try {
            // Fetch users with role_id = 3, selecting only user_id and name
            $users = User::select('user_id', 'name')
                         ->where('role_id', 3)
                         ->orderBy('name', 'asc')
                         ->get()
                         ->map(function ($user) {
                             return [
                                 'user_id' => $user->user_id,
                                 'name' => $user->name,
                             ];
                         });

            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching users for dropdown: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch users for dropdown.'], 500);
        }
    }

    public function dropdownUsersByName(Request $request)
    {
        try {
            $users = User::select('user_id', 'name')
                         ->orderBy('name', 'asc')
                         ->get()
                         ->map(function ($user) {
                             return [
                                 'user_id' => $user->user_id,
                                 'name' => $user->name,
                             ];
                         });

            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching users by name for dropdown: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch users for dropdown.'], 500);
        }
    }

    public function dropdownUsersByRole(Request $request)
    {
        try {
            $users = User::select('user_id', 'name', 'role_id')
                         ->with(['role' => function ($query) {
                             $query->select('role_id', 'category');
                         }])
                         ->orderBy('role_id', 'asc')
                         ->get()
                         ->map(function ($user) {
                             return [
                                 'user_id' => $user->user_id,
                                 'name' => $user->name,
                                 'role_id' => $user->role_id,
                                 'role_category' => optional($user->role)->category,
                             ];
                         });

            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching users by role for dropdown: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch users by role for dropdown.'], 500);
        }
    }

    public function getUsersWithRoles(Request $request)
    {
        try {
            $users = User::with(['role' => function ($query) {
                $query->select('role_id', 'category');
            }])
            ->orderBy('user_id', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'role_id' => $user->role_id,
                    'status' => $user->status,
                    'email' => $user->email,
                    'role_category' => optional($user->role)->category,
                    'created_at' => $user->created_at,
                ];
            });

            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching users with roles: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch users with roles.'], 500);
        }
    }

    public function showUserById($user_id)
    {
        $user = User::with('role')->find($user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'role_id' => $user->role_id,
            'status' => $user->status,
            'email' => $user->email,
            'role' => optional($user->role)->category,
        ], 200);
    }

    public function updateUser(Request $request, $user_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'role_id' => 'required|integer',
            'status' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'nullable|string|min:8',
        ]);
        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }
        $user->update($validatedData);
        return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
    }

    public function deleteUser($user_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function getAuditTrail(Request $request)
    {
        try {
            // Fetch the audit trail records for all users, joining with the roles table
            $auditTrail = \DB::table('audit_trail')
                ->join('roles', 'audit_trail.role_id', '=', 'roles.role_id')
                ->select('audit_trail.*', 'roles.category')
                ->orderBy('audit_trail.created_at', 'desc')
                ->get();

            return response()->json(['audit_trail' => $auditTrail], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error fetching audit trail: ' . $e->getMessage());

            // Return a JSON error response with HTTP status 500
            return response()->json(['error' => 'Failed to fetch audit trail.'], 500);
        }
    }

    public function countUsers()
    {
        try {
            $totalUsers = User::count();

            return response()->json([
                'total_users' => $totalUsers
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error counting users: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count users.'], 500);
        }
    }

    public function storeCookies(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cookie_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $cookie = Cookie::create([
                'user_id' => $request->user()->user_id,
                'cookie_data' => $request->cookie_data,
            ]);

            return response()->json(['message' => 'Cookie stored successfully', 'cookie' => $cookie], 201);
        } catch (\Exception $e) {
            \Log::error('Error storing cookie: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to store cookie.'], 500);
        }
    }
}