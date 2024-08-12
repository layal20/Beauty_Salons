<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function loginAdmin(Request $request)
    {
        $credentials = $request->only('user_name', 'password');
        $admin = admin::query()->where('user_name', $credentials['user_name'])->first();
        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            $token = $admin->createToken('Admin Access Token', ['admin'])->accessToken;
            return response()->json([
                'token' => $token
            ], 200);
        }
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }

    public function loginSuperAdmin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $super_admin = SuperAdmin::query()->where('email', $credentials['email'])->first();
        if ($super_admin && Hash::check($credentials['password'], $super_admin->password)) {
            $token = $super_admin->createToken('Super Admin Access Token', ['super_admin'])->accessToken;
            return response()->json([
                'token' => $token
            ], 200);
        }
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }

    public function CustomerRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:customers,name',
            'email' => 'required|unique:customers,email,',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'image' => 'sometimes',
            'phone_number' => 'required|unique:customers,phone_number'
        ]);
        $password = Hash::make($request->password);
        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
            'phone_number' => $request->phone_number,
        ]);
        if ($request->hasFile('image')) {
            $image_name = 'default.png';
            $destenationPath = 'public/images/users';
            $image = $request->image;
            $image_name = implode('.', [
                md5_file($image->getPathname()),
                $image->getClientOriginalExtension()
            ]);
            $path = $request->file('image')->storeAs($destenationPath, $image_name);
            $customer->image = $image_name;
            $customer->save();
        }
        $token = $customer->createToken('Customer Access Token', ['customer'])->accessToken;
        $role = Role::findByName('customer', 'customer');
        $customer->assignRole($role);
        return response()->json([
            'token' => $token
        ], 200);
    }

    public function customerLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $customer = Customer::query()->where('email', $credentials['email'])->first();
        if ($customer && Hash::check($credentials['password'], $customer->password)) {
            $token = $customer->createToken('Customer Access Token', ['customer'])->accessToken;
            return response()->json([
                'token' => $token
            ], 200);
        }
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }

    public function logout()
    {
        $guard = null;
        if (Auth::guard('super_admin')->check()) {
            $guard = 'super_admin';
        } elseif (Auth::guard('admin')->check()) {
            $guard = 'admin';
        } elseif (Auth::guard('customer')->check()) {
            $guard = 'customer';
        }
        if ($guard) {
            $user = Auth::guard($guard)->user();
            $tokens = $user->tokens;
            foreach ($tokens as $token) {
                $token->revoke();
            }
            return response()->json(['message' => 'Successfully logged out'], 200);
        } else {
            return response()->json(['message' => 'Not authenticated'], 401);
        }
    }
}
