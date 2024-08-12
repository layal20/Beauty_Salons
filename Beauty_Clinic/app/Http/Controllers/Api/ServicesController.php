<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\AdminService;
use App\Models\Employee;
use App\Models\Salon;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ServicesController extends Controller
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

        if (!$user->can('view all services')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            $services = service::with('admins')->get();
            if ($services->isEmpty()) {
                return response()->json(['message' => 'there is no services yet.'], 403);
            }
            return ServiceResource::collection($services);
        } elseif ($admin) {
            $admin = Auth::guard('admin')->user();
            $services = Service::whereHas('admins', function ($query) use ($admin) {
                $query->where('admin_id', $admin->id);
            
            })->get();
            if ($services->isEmpty()) {
                return response()->json(['message' => 'you dont have any service yet.'], 403);
            }
            return ServiceResource::collection($services);
        } elseif ($customer) {
            $services = service::query()->whereHas('salons', function ($query) {
                $query->where('salons.status', 'active');
            })->whereHas('employee')->active()->get();
            if ($services->isEmpty()) {
                return response()->json(['message' => 'there is no services yet.'], 403);
            }
            return ServiceResource::collection($services);
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

        if (!$user->can('add service')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate(
            [
                'name' => [
                    'required',
                    Rule::unique('services')->where(function ($query) use ($request, $admin) {
                        return $query->whereExists(function ($query) use ($admin) {
                            $query->select(DB::raw(1))
                                ->from('admin_services')
                                ->whereColumn('admin_services.service_id', 'services.id')
                                ->where('admin_services.admin_id', $admin->id);
                        });
                    }),
                    'string',
                    'max:255'
                ],

                'description' => 'required|string',
                'price' => 'required|numeric',
                'status' => 'required|string',
                'employee_id' => 'required|exists:employees,id',
                'image' => 'required',
            ],
            [
                'name.unique' => 'this service is already exist for this admin'
            ]
        );
        $data = $request->except('image');
        $file = $request->file('image');
        $path = $file->store('services', [
            'disk' => 'uploads'
        ]);
        $data['image'] = $path;
        $employeeId = $request->employee_id;

        $employee = Employee::where('id', $employeeId)
            ->where('admin_id', $admin->id)
            ->doesntHave('service')
            ->first();

        if (!$employee) {
            return response()->json(['message' => 'The employee is either not assigned to this admin or already has a service.'], 403);
        }
        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'employee_id' => $employeeId,
            'image' => $path,
            'status' => $request->status,
        ]);


        $admin->services()->attach($service->id);
        $salon = $admin->salon;
        $salon->services()->attach($service->id);
        return response()->json(['message' => 'Service created and added to salon successfully'], 201);
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
        if (!$user->can('view service')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            $service = Service::with(['admins', 'salons', 'employee'])->find($id);
            if (!$service) {
                return response()->json(['message' => 'Service not found'], 404);
            }

            return new ServiceResource($service);
        } elseif ($admin) {

            $service = Service::whereHas('admins', function ($query) use ($admin) {
                $query->where('admin_id', $admin->id);
            })->with('salons', 'employee')->find($id);
            if (!$service) {
                return response()->json(['message' => 'Service not found'], 404);
            }
            return new ServiceResource($service);
        } elseif ($customer) {
            $service = Service::with('salons', 'employee')->whereHas('salons', function ($query) {
                $query->where('salons.status', 'active');
            })->whereHas('employee')->active()->find($id);
            if (!$service) {
                return response()->json(['message' => 'Service not found'], 404);
            }

            return new ServiceResource($service);
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
        if (!$user->can('update service details')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $service = Service::whereHas('admins', function ($query) use ($admin) {
            $query->where('admin_id', $admin->id);
        })->find($id);

        if (!$service) {
            return response()->json(['message' => 'Service not found or you do not have permission to edit it'], 404);
        }

        $request->validate(
            [
                'name' => [
                    'sometimes',
                    Rule::unique('services')->where(function ($query) use ($request, $admin) {
                        return $query->whereExists(function ($query) use ($admin) {
                            $query->select(DB::raw(1))
                                ->from('admin_services')
                                ->whereColumn('admin_services.service_id', 'services.id')
                                ->where('admin_services.admin_id', $admin->id);
                        });
                    }),
                    'string',
                    'max:255'
                ],

                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric',
                'status' => 'sometimes|string',
                'date' => 'sometimes',
                'time' => 'date_format:H:i:s',
                'employee_id' => 'sometimes|exists:employees,id',
                'image' => 'sometimes',
            ],
            [
                'name.unique' => 'this service is already exist for this admin'
            ]
        );
        if ($request->hasFile('image')) {
            $old_image = $service->image;
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

                Storage::disk('uploads')->delete($service->image);
            }
            $employeeId = $request->employee_id;

            $employee = Employee::where('id', $employeeId)
            ->where('admin_id', $admin->id)
            ->doesntHave('service')
            ->first();

            if (!$employee) {
                return response()->json(['message' => 'The employee is either not assigned to this admin or already has a service.'], 403);
            }
            $service->update($data);
            $admin->services()->sync($service->id);
        } else
        $employeeId = $request->employee_id;

        $employee = Employee::where('id', $employeeId)
            ->where('admin_id', $admin->id)
            ->doesntHave('service')
            ->first();

        if (!$employee) {
            return response()->json(['message' => 'The employee is either not assigned to this admin or already has a service.'], 403);
        }
            $service->update($request->all());
        $admin->services()->sync($service->id);

        return response()->json(['message' => 'Service updated successfully'], 200);
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
        if (!$user->can('delete service')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service = Service::whereHas('admins', function ($query) use ($admin) {
            $query->where('admin_id', $admin->id);
        })->find($id);

        if (!$service) {
            return response()->json(['message' => 'Service not found or you do not have permission to edit it'], 404);
        }
        $service->delete();
        if ($service->image) {
            if (Storage::disk('uploads')->exists($service->image)) {
                Storage::disk('uploads')->delete($service->image);
            }
        }
        return response()->json([
            'message' => 'Service deleted successfully',
        ], 200);
    }
}
