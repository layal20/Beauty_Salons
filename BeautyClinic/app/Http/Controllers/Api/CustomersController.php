<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SalonResource;
use App\Http\Resources\ServiceResource;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Salon;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class CustomersController extends Controller
{
    public function searchAboutProduct($name)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }

        if (!$user->can('search about product')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $results = null;

        if ($super_admin) {
            $results = Product::with(['salons' => function ($query) {
                $query->withPivot('quantity');
            }])
                ->where('name', 'like', "%{$name}%")
                ->get();
            if ($results->isEmpty()) {
                return response()->json(['message' => 'Product Not Found'], 404);
            }
            return ProductResource::collection($results);
        } elseif ($admin) {
            $results = Product::with(['salons' => function ($query) {
                $query->withPivot('quantity');
            }])
                ->whereHas('admins', function ($query) use ($admin) {
                    $query->where('admin_id', $admin->id);
                })
                ->where(
                    'name',
                    'like',
                    "%{$name}%"
                )
                ->get();
            if ($results->isEmpty()) {
                return response()->json(['message' => 'Product Not Found'], 404);
            }
            return ProductResource::collection($results);
        } elseif ($customer) {
            $results = Product::query()
            ->where('name', 'like', "%{$name}%")
            ->whereHas('salons', function ($query) {
                $query->where('status', 'active')->where('salon_products.quantity' , '>' , 0);
            })
            ->with(['salons'])
            ->get();

            if ($results->isEmpty()) {
                return response()->json(['message' => 'Product Not Found'], 404);
            }
            return ProductResource::collection($results);
        }
    }

    public function searchAboutSalon($name)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {

            return response()->json(['message' => 'Not Authenticated'], 401);
        }

        if (!$user->can('search about salon')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $results = null;

        if ($super_admin) {
            $results = Salon::with(['products' => function ($query) {
                $query->withPivot('quantity');
            }, 'services'])
                ->where('name', 'like', "%{$name}%")
                ->get();
            if ($results->isEmpty()) {
                return Response::json([
                    'message' => 'Salon Not Found'
                ]);
            }
            return SalonResource::collection($results);
        } elseif ($admin) {
            $results = Salon::with(['products' => function ($query) {
                $query->withPivot('quantity');
            }, 'services'])
                ->active()
                ->where('id', $admin->salon_id)
                ->where('name', 'like', "%{$name}%")->get();
            if ($results->isEmpty()) {
                return Response::json([
                    'message' => 'Salon Not Found'
                ]);
            }
            return SalonResource::collection($results);
        } elseif ($customer) {
            $results = Salon::with(['products' => function ($query) {
                $query->wherePivot('quantity', '>', 0)->withPivot('quantity');
            }, 'services'])
                ->active()
                ->where('name', 'like', "%{$name}%")
                ->get();
            if ($results->isEmpty()) {
                return Response::json([
                    'message' => 'Salon Not Found'
                ]);
            }
            return SalonResource::collection($results);
        }
    }
    public function searchAboutService($name)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }

        if (!$user->can('search about service')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $results = null;

        if ($super_admin) {
            $results = Service::with(['salons'])
                ->where('name', 'like', "%{$name}%")
                ->get();
            if ($results->isEmpty()) {
                return response()->json(['message' => 'Service Not Found'], 404);
            }
            return ServiceResource::collection($results);
        } elseif ($admin) {
            $results = Service::with(['salons'])->whereHas('admins', function ($query) use ($admin) {
                $query->where('admin_id', $admin->id);
            })
                ->where(
                    'name',
                    'like',
                    "%{$name}%"
                )
                ->get();
            if ($results->isEmpty()) {
                return response()->json(['message' => 'Service Not Found'], 404);
            }
            return ServiceResource::collection($results);
        } elseif ($customer) {
            $results = Service::with(['salons'])->whereHas('salons', function ($query) {
                $query->where('salons.status', 'active');
            })
                ->where('name', 'like', "%{$name}%")
                ->get();
            if ($results->isEmpty()) {
                return response()->json(['message' => 'Service Not Found'], 404);
            }
            return ServiceResource::collection($results);
        }
    }
    public function searchAboutEmployee($name)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }

        if (!$user->can('search about employee')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $results = null;
        if ($super_admin) {
            $results = Employee::with(['salon', 'service'])->where('name', 'like', "%{$name}%")->get();
            if ($results->isEmpty()) {
                return Response::json([
                    'Employee Not Found'
                ]);
            }
            return EmployeeResource::collection($results);
        } elseif ($admin) {
            $results = Employee::with(['salon', 'service'])->where('admin_id', $admin->id)
                ->where('name', 'like', "%{$name}%")->get();
            if ($results->isEmpty()) {
                return Response::json([
                    'Employee Not Found'
                ]);
            }
            return EmployeeResource::collection($results);
        } elseif ($customer) {
            $results = Employee::with(['salon', 'service'])->whereHas('salon', function ($query) {
                $query->where('status', 'active');
            })->where('name', 'like', "%{$name}%")->get();
            if ($results->isEmpty()) {
                return Response::json([
                    'Employee Not Found'
                ]);
            }
            return EmployeeResource::collection($results);
        }
    }

    public function product_reservation(Request $request, $id)
    {
        $product = Product::query()->find($id);
        if (!$product) {
            return response()->json(['message' => 'product not found'], 404);
        }
    }
}
