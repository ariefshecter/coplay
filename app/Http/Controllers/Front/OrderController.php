<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\Cart;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrdersProduct;
use App\Models\Product;

class OrderController extends Controller
{
    /**
     * Display the user's orders list or a specific order's details.
     * Menampilkan daftar pesanan pengguna atau detail pesanan tertentu.
     */
    public function orders($id = null)
    {
        if (empty($id)) {
            $orders = Order::with('orders_products')->where('user_id', Auth::user()->id)
                            ->orderBy('id', 'Desc')->get()->toArray();
            return view('front.orders.orders')->with(compact('orders'));
        } else {
            $orderDetails = Order::with('orders_products')->where('id', $id)->first()->toArray();
            if ($orderDetails['user_id'] == Auth::id()) {
                return view('front.orders.order_details')->with(compact('orderDetails'));
            } else {
                return redirect('/orders');
            }
        }
    }

    /**
     * Create a new order entry in the database.
     * Membuat entri pesanan baru di database.
     */
    public function placeOrder(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();

            // Basic input validation
            // Validasi input dasar
            if (empty($data['address_id']) || empty($data['payment_gateway'])) {
                return response()->json(['status' => 'error', 'message' => 'Silakan pilih alamat pengiriman dan metode pembayaran.'], 400);
            }

            // Get delivery address details from database
            // Dapatkan detail alamat dari database
            $deliveryAddress = DeliveryAddress::where('id', $data['address_id'])->first();
            if (!$deliveryAddress) {
                return response()->json(['status' => 'error', 'message' => 'Alamat pengiriman tidak valid.'], 400);
            }
            
            $user_id = Auth::user()->id;
            $user_email = Auth::user()->email;

            // Get cart items
            // Dapatkan item dari keranjang
            $getCartItems = Cart::with('product')->where('user_id', $user_id)->get();

            if ($getCartItems->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'Keranjang Anda kosong.'], 400);
            }

            // Calculate totals
            // Hitung total harga, kupon, dan biaya kirim
            $total_price = 0;
            foreach ($getCartItems as $item) {
                $attrPrice = Product::getDiscountAttributePrice($item->product_id, $item->size);
                $total_price += ($attrPrice['final_price'] * $item->quantity);
            }
            $coupon_amount = Session::get('couponAmount') ?? 0;
            $shipping_charges = 0; // Implement shipping logic here if needed
            $grand_total = $total_price - $coupon_amount + $shipping_charges;

            // Use a database transaction to ensure data integrity
            // Gunakan transaksi database untuk memastikan integritas data
            DB::beginTransaction();

            try {
                // Save order details
                // Simpan detail pesanan
                $order = new Order;
                $order->user_id = $user_id;
                $order->name = $deliveryAddress->name;
                $order->address = $deliveryAddress->address;
                $order->city = $deliveryAddress->city;
                $order->state = $deliveryAddress->state;
                $order->country = $deliveryAddress->country;
                $order->pincode = $deliveryAddress->pincode;
                $order->mobile = $deliveryAddress->mobile;
                $order->email = $user_email;
                $order->shipping_charges = $shipping_charges;
                $order->coupon_code = Session::get('couponCode') ?? '';
                $order->coupon_amount = $coupon_amount;
                $order->order_status = 'New';
                $order->payment_method = $data['payment_gateway'];
                $order->payment_gateway = $data['payment_gateway'];
                $order->grand_total = $grand_total;
                $order->payment_status = 'Pending';
                $order->save();
                
                $order_id = $order->id;

                // Save ordered products
                // Simpan produk pesanan
                foreach ($getCartItems as $item) {
                    $productDetails = $item->product;
                    $attrPrice = Product::getDiscountAttributePrice($item->product_id, $item->size);
                    
                    $orderProduct = new OrdersProduct;
                    $orderProduct->order_id = $order_id;
                    $orderProduct->user_id = $user_id;
                    $orderProduct->vendor_id = $productDetails->vendor_id;
                    $orderProduct->admin_id = 0; // Or get the relevant admin ID if needed
                    $orderProduct->product_id = $item->product_id;
                    $orderProduct->product_code = $productDetails->product_code;
                    $orderProduct->product_name = $productDetails->product_name;
                    $orderProduct->product_color = $productDetails->product_color ?? '';
                    $orderProduct->product_size = $item->size;
                    $orderProduct->product_price = $attrPrice['final_price'];
                    $orderProduct->product_qty = $item->quantity;
                    $orderProduct->item_status = 'Pending';
                    $orderProduct->save();
                }
                
                Session::put('order_id', $order_id);

                DB::commit(); // Commit the transaction

                // Return response based on payment gateway
                // Kembalikan respons berdasarkan metode pembayaran
                if ($data['payment_gateway'] == 'COD') {
                    Cart::where('user_id', $user_id)->delete();
                    Session::forget(['couponCode', 'couponAmount']);
                    Session::flash('success_message', 'Pesanan Anda dengan COD telah berhasil dibuat.');
                    return response()->json(['status' => 'success', 'redirect' => url('/thanks')]);
                } else if ($data['payment_gateway'] == 'midtrans') {
                    // For Midtrans, just return the order_id for the frontend to process
                    // Untuk Midtrans, kembalikan ID Pesanan agar frontend bisa melanjutkan
                    return response()->json(['status' => 'midtrans_initiated', 'order_id' => $order_id]);
                } else {
                    // For other gateways like PayPal
                    return response()->json(['status' => 'success', 'redirect' => url('/' . $data['payment_gateway'])]);
                }

            } catch (\Exception $e) {
                DB::rollBack(); // Rollback the transaction on error
                Log::error('Gagal membuat pesanan: ' . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'Gagal membuat pesanan. Silakan coba lagi.'], 500);
            }
        }
        return response()->json(['status' => 'error', 'message' => 'Request tidak valid.'], 405);
    }
}
