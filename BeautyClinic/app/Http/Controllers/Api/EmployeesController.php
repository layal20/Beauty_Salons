<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('view all employees')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            $employees = Employee::with('admin')->get();
            if ($employees->isEmpty()) {
                return response()->json(['message' => 'there is no employees yet.'], 403);
            }
            return EmployeeResource::collection($employees);
        } elseif ($admin) {
            $admin = Auth::guard('admin')->user();
            $employees = Employee::query()->where('admin_id', $admin->id)->get();
            if ($employees->isEmpty()) {
                return response()->json(['message' => 'you dont have any employee yet.'], 403);
            }
            return EmployeeResource::collection($employees);
        } elseif ($customer) {
            $employees = Employee::query()->whereHas('salon', function ($query) {
                $query->where('status', 'active');
            })->whereHas('service')->get();
            if ($employees->isEmpty()) {
                return response()->json(['message' => 'there is no employees yet.'], 403);
            }
            return EmployeeResource::collection($employees);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('add employee')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'name' => 'required|string|unique:employees,name|max:255',
            'image' => 'required',
            'salary' => 'required|numeric',

        ]);

        if (!$request->hasFile('image')) {
            return;
        }
        $data = $request->except('image');
        $file = $request->file('image');
        $path = $file->store('employees', [
            'disk' => 'uploads'
        ]);
        $data['image'] = $path;
        $employee = Employee::create([
            'name' => $request->name,
            'salary' => $request->salary,
            'image' => $path,
            'admin_id' => $admin->id,
            'salon_id' => $admin->salon_id,
        ]);
        return response()->json(['message' => 'Employee added to salon successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }


        if (!$user->can('view employee')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($super_admin) {

            $employee = Employee::with(['service', 'admin', 'salon'])->find($id);
            if (!$employee) {
                return response()->json(['message' => 'Employee not found for super admin'], 404);
            }
            return new EmployeeResource($employee);
        } elseif ($admin) {

            $employee = Employee::with(['service', 'admin', 'salon'])->where('admin_id', $admin->id)->find($id);
            if (!$employee) {
                return response()->json(['message' => 'Employee not found for admin'], 404);
            }
            return new EmployeeResource($employee);
        } elseif ($customer) {

            $employee = Employee::with('service', 'salon')->whereHas('salon', function ($query) {
                $query->where('status', 'active');
            })->find($id);
            if (!$employee) {
                return response()->json(['message' => 'Employee not found for customer'], 404);
            }
            return new EmployeeResource($employee);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('update employee info')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $employee = Employee::query()->where('admin_id', $admin->id)->find($id);
        if (!$employee) {
            return response()->json(['message' => 'The employee is either not assigned to this admin or does not exist.'], 403);
        }
        $request->validate([
            'name' => 'sometimes|string|unique:employees,name|max:255',
            'image' => 'sometimes',
            'salary' => 'sometimes|numeric',
        ]);
        if ($request->hasFile('image')) {
            $old_image = $employee->image;
            $data = $request->except('image');
            $file = $request->file('image');
            $path = $file->store('services', [
                'disk' => 'uploads'
            ]);
            $new_image = $path;
            if ($new_image) {
                $data['image']  = $new_image;
            }

            if ($old_image && isset($new_image)) {
                Storage::disk('uploads')->delete($employee->image);
            }

            $employee->update($data);
        } else
            $employee->update($request->all());
        return response()->json(['message' => 'Employee Info updated successfully'], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('delete employee')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = Employee::query()->where('admin_id', $admin->id)->find($id);
        if (!$employee) {
            return response()->json(['message' => 'The employee is either not assigned to this admin or does not exist.'], 403);
        }
        $employee->delete();
        if ($employee->image) {
            if (Storage::disk('uploads')->exists($employee->image)) {
                Storage::disk('uploads')->delete($employee->image);
            }
        }
        return response()->json([
            'message' => 'Employee deleted successfully',
        ], 200);
    }
}
