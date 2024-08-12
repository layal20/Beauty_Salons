<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\SalonProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartsController extends Controller
{
    public function addProductToCart(Request $request, $productId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('booking a product')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = SalonProduct::query()->where('product_id', $productId)->where('quantity', '>', 0)->first();
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        $salonId = $product->salon_id;
        if ($request->quantity > $product->quantity) {
            return response()->json(['message' => 'We do not have enough quantity.'], 400);
        }
        $cart = Cart::query()->where('salon_id', $salonId)->where('customer_id', $customer->id)->first();
        if (!$cart) {
            $cart = Cart::Create([
                'customer_id' => $customer->id,
                'salon_id' => $salonId,
                'total_price' => 0
            ]);
        }
        $cartItem = CartItem::query()->where('product_id', $product->id)->where('cart_id', $cart->id)->first();
        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
            $product->quantity -= $request->quantity;
            $product->save();
            $total = $cart->cartItems->sum(function ($cartItem) {
                return $cartItem->quantity * $cartItem->product->price;
            });
            $cart->total_price = $total;
            $cart->save();
            return response()->json([
                'message' => 'Product Item quantity updated successfully'
            ]);
        } else {
            $cartItem = CartItem::updateOrCreate([
                'product_id' => $productId,
                'cart_id' => $cart->id,
                'quantity' => $request->quantity
            ]);
            $product->quantity -= $request->quantity;
            $product->save();
            $productPrice = $cartItem->product->price;
            $cart->total_price += $cartItem->quantity * $productPrice;
            $cart->save();

            return response()->json([
                'message' => 'Product added to cart successfully'
            ]);
        }
    }


    public function removeProductFromCart($cartId, $productId)
    {

        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('delete booking')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $product = SalonProduct::query()->where('product_id', $productId)->first();
        if (!$product) {
            return response()->json([
                'message' => 'product not found'
            ], 404);
        }
        $salonId = $product->salon_id;
        $cart = Cart::query()->where('id', $cartId)->where('customer_id', $customer->id)->where('salon_id', $salonId)->first();
        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found Or this cart is not belong to you'
            ]);
        }
        $cartItem = CartItem::query()->where('product_id', $productId)->where('cart_id', $cartId)->first();
        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart Item not found'
            ], 404);
        }
        $product->quantity += $cartItem->quantity;
        $product->save();
        $cartItem->delete();
        $total = $cart->cartItems->sum(function ($cartItem) {
            return $cartItem->quantity * $cartItem->product->price;
        });
        $cart->total_price = $total;
        $cart->save();
        return response()->json([
            'message' => 'CartItem deleted successfully'
        ], 200);
    }

    public function updateCartItem(Request $request, $cartId, $productId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('update the booking details')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $cart = Cart::query()->where('id', $cartId)->where('customer_id', $customer->id)->first();
        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }

        $product = SalonProduct::query()->where('product_id', $productId)->where('quantity', '>', 0)->first();
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
        if ($request->quantity > $product->quantity) {
            return response()->json(['message' => 'We do not have enough quantity.'], 400);
        }

        $cartItem = CartItem::query()->where('product_id', $product->id)->where('cart_id', $cart->id)->first();
        $quantityDifference = $request->quantity - $cartItem->quantity;

        if ($quantityDifference > 0) {
            if ($quantityDifference > $product->quantity) {
                return response()->json(['message' => 'We do not have enough quantity.'], 400);
            }
            $product->quantity -= $quantityDifference;
        } else {
            $product->quantity += abs($quantityDifference);
        }

        $product->save();
        $cartItem->quantity = $request->quantity;
        $cartItem->save();
        $total = $cart->cartItems->sum(function ($cartItem) {
            return $cartItem->quantity * $cartItem->product->price;
        });
        $cart->total_price = $total;
        $cart->save();
        return response()->json([
            'message' => 'Product Item quantity updated successfully'
        ]);
    }

    public function deleteCart($cartId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('delete cart')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $cart = Cart::query()->where('id', $cartId)->where('customer_id', $customer->id)->first();
        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }
        $cartItems = CartItem::query()->where('cart_id', $cartId)->get();
        foreach ($cartItems as $cartItem) {
            $product = SalonProduct::query()->where('product_id', $cartItem->product_id)->first();
            if ($product) {
                $product->quantity += $cartItem->quantity;
                $product->save();
            }
            // } else {
            //     Log::warning('Product not found when deleting cart: Product ID ' . $cartItem->product_id);
            // }
            $cartItem->delete();
        }
        $cart->delete();

        return response()->json([
            'message' => 'Cart deleted and quantities returned successfully'
        ]);
    }


    public function showCart($cartId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('show cart')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            $cart = Cart::with(['cartItems.product' => function ($query) {
                $query->select('id', 'name', 'price');
            }])->where('id', $cartId)->first();
            if (!$cart) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            $respons = $cart->toArray();
            $respons['customer'] = $cart->customer ? [
                'id' => $cart->customer->id,
                'name' => $cart->customer->name,
                'phone_number' => $cart->customer->phone_number,
            ] : null;
            $respons['salon'] = [
                'id' => $cart->salon->id,
                'name' => $cart->salon->name,
            ];
            return response()->json($respons);
        } elseif ($admin) {

            $cart = Cart::with(['cartItems.product' => function ($query) use ($admin) {
                $query->select('id', 'name', 'price')->whereHas('admins', function ($query) use ($admin) {
                    $query->where('admin_id', $admin->id);
                });
            }])
                ->where('id', $cartId)->first();
            if (!$cart) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            $respons = $cart->toArray();
            $respons['customer'] = $cart->customer ? [
                'id' => $cart->customer->id,
                'name' => $cart->customer->name,
                'phone' => $cart->customer->phone_number,
            ] : null;
            $respons['salon'] = [
                'id' => $cart->salon->id,
                'name' => $cart->salon->name,
            ];
            return response()->json($respons);
        } else {

            $cart = Cart::with(['cartItems.product' => function ($query) {
                $query->select('id', 'name', 'price');
            }])->where('id', $cartId)->where('customer_id', $customer->id)->first();
            if (!$cart) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            $respons = $cart->toArray();
            $respons['salon'] = [
                'id' => $cart->salon->id,
                'name' => $cart->salon->name,
            ];
            unset($respons['customer_id']);
            return response()->json($respons);
        }
    }

    public function allCarts()
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('view all carts')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            $cart = Cart::get();
            if ($cart->isEmpty()) {
                return response()->json([
                    'message' => 'Carts not found',
                ], 404);
            }
            return response()->json($cart);
        } elseif ($admin) {
            $cart = Cart::whereHas('cartItems.product', function ($query) use ($admin) {
                $query->whereHas('admins', function ($q) use ($admin) {
                    $q->where('admin_id', $admin->id);
                });
            })->get();
            if ($cart->isEmpty()) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            return response()->json($cart);
        } else {
            $cart = Cart::query()->where('customer_id', $customer->id)->get();
            if ($cart->isEmpty()) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            $respons = $cart->toArray();
            unset($respons['customer_id']);
            return response()->json($respons);
        }
    }

    public function getCustomerBook($customerId)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('view all user booking')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($super_admin) {
            $cart = Cart::query()->where('customer_id', $customerId)->get();
            if ($cart->isEmpty()) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            return response()->json($cart);
        } elseif ($admin) {
            $cart = Cart::whereHas('cartItems.product', function ($query) use ($admin) {
                $query->whereHas('admins', function ($q) use ($admin) {
                    $q->where('admin_id', $admin->id);
                });
            })->where('customer_id', $customerId)->get();
            if ($cart->isEmpty()) {
                return response()->json([
                    'message' => 'Cart not found',
                ], 404);
            }
            return response()->json($cart);
        }
    }
}
