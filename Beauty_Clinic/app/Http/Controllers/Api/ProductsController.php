<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductsController extends Controller
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
        Log::info('Current user:', ['user_id' => $user->id, 'user_type' => get_class($user)]);

        if (!$user->can('view all products')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($super_admin) {
            $products = Product::with(['admins'])->get();
            if ($products->isEmpty()) {
                return response()->json(['message' => 'there is no products yet.'], 403);
            }
            return ProductResource::collection($products);
        } elseif ($admin) {
            $products = Product::query()->whereHas('admins', function ($query) use ($admin) {
                $query->where('admin_id', $admin->id);
            })->get();
            if ($products->isEmpty()) {
                return response()->json(['message' => 'you do not have any products yet.'], 403);
            }
            return ProductResource::collection($products);
        } else {
            $products = Product::query()->whereHas('salons', function ($query) {
                $query->where('salons.status', 'active')->where('salon_products.quantity', '>', 0);
            })->get();
            if ($products->isEmpty()) {
                return response()->json(['message' => 'there is no products yet.'], 403);
            }
            return ProductResource::collection($products);
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
        //return $user->roles()->with('permissions')->get();
        Log::info('Current user:', ['user_id' => $user->id, 'user_type' => get_class($user)]);
        if (!$user->can('add product')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate(
            [
                'name' => [
                    'required',
                    Rule::unique('products')->where(function ($query) use ($request, $admin) {
                        return $query->whereExists(function ($query) use ($admin) {
                            $query->select(DB::raw(1))
                                ->from('admin_products')
                                ->whereColumn('admin_products.product_id', 'products.id')
                                ->where('admin_products.admin_id', $admin->id);
                        });
                    }),
                    'string',
                    'max:255'
                ],

                'description' => 'required|string',
                'price' => 'required|numeric',
                'quantity' => 'required|integer|min:1',
                'image' => 'required'
            ],
            [
                'name.unique' => 'this product is already exist for this admin'
            ]
        );

        $data = $request->except('image');
        $file = $request->file('image');
        $path = $file->store('products', [
            'disk' => 'uploads'
        ]);
        $data['image'] = $path;
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image' => $path,
        ]);

        $admin->products()->attach($product->id);
        $salon = $admin->salon;
        $salon->products()->attach($product->id, ['quantity' => $request->quantity]);
        return response()->json(['message' => 'Product created and added to salon successfully'], 201);
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


        if (!$user->can('view product')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            $product = Product::with('salons', 'admins')->find($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            return new ProductResource($product);
        } elseif ($admin) {
            $product = Product::with('salons')->whereHas('admins', function ($query) use ($admin) {
                $query->where('admin_id', $admin->id);
            })->find($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found for admin'], 404);
            }
            return new ProductResource($product);
        } elseif ($customer) {
            $product = Product::with(
                ['salons' => function ($query) {
                    $query->wherePivot('quantity', '>', 0)->withPivot('quantity');
                }]
            )->whereHas('salons', function ($query) {
                $query->where('status', 'active');
            })->find($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found for customer '], 404);
            }
            return new ProductResource($product);
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
        if (!$user->can('update product details')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $product = Product::whereHas('admins', function ($query) use ($admin) {
            $query->where('admin_id', $admin->id);
        })->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found or you do not have permission to edit it'], 404);
        }
        $request->validate(
            [
                'name' => [
                    'sometimes',
                    Rule::unique('products')->where(function ($query) use ($request, $admin) {
                        return $query->whereExists(function ($query) use ($admin, $request) {
                            $query->select(DB::raw(1))
                                ->from('admin_products')
                                ->whereColumn('admin_products.product_id', 'products.id')
                                ->where('admin_products.admin_id', $admin->id);
                        });
                    }),
                    'string',
                    'max:255'
                ],
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric',
                'quantity' => 'sometimes|integer|min:1',
                'image' => 'sometimes'
            ],
            [
                'name.unique' => 'this product is already exist for this admin'
            ]
        );
        if ($request->hasFile('image')) {
            $old_image = $product->image;
            $data = $request->except('image');
            $file = $request->file('image');
            $path = $file->store('salons', [
                'disk' => 'uploads'
            ]);
            $new_image = $path;
            if ($new_image) {
                $data['image']  = $new_image;
            }

            if ($old_image && isset($new_image)) {
                if (Storage::disk('uploads')->exists($product->image)) {
                    Storage::disk('uploads')->delete($product->image);
                }
            }
            $product->update($data);
            $admin->products()->sync($product->id);
        }
        if ($request->has('quantity')) {
            $salon_id = $admin->salon_id;
            $salon = Salon::find($salon_id);

            if (!$salon) {
                return response()->json(['message' => 'Salon not found'], 404);
            }
            $productInSalon = $salon->products->find($id);

            if ($productInSalon) {
                $salon->products()->updateExistingPivot($id, ['quantity' => $request->quantity]);
                $product->update($request->all());
                $admin->products()->sync($product->id);
                return response()->json(['message' => 'Product  updated successfully'], 200);
            } else {
                return response()->json(['message' => 'Product not found in this salon'], 404);
            }
        }
        $product->update($request->all());
        $admin->products()->sync($product->id);

        return response()->json(['message' => 'Product updated successfully'], 200);
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
        if (!$user->can('delete product')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $product = Product::whereHas('admins', function ($query) use ($admin) {
            $query->where('admin_id', $admin->id);
        })->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found or you do not have permission to deleted it'], 404);
        }

        $product->delete();
        if ($product->image) {
            Storage::disk('uploads')->delete($product->image);
        }
        return response()->json([
            'message' => 'product deleted successfully',
        ], 200);
    }
}
