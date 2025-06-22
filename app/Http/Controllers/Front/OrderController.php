<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Mail;
use App\Mail\OrderShipped;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrdersProduct;
use App\Models\Product;
use App\Models\Sms;
use App\Models\User;


class OrderController extends Controller
{
    /**
     * Metode untuk menampilkan halaman daftar pesanan atau detail pesanan.
     *
     * @param int|null $id ID pesanan (opsional)
     * @return \Illuminate\View\View
     */
    public function orders($id = null)
    {
        if (empty($id)) {
            // Jika ID pesanan tidak ada, tampilkan daftar semua pesanan pengguna
            $orders = Order::with('orders_products')->where('user_id', Auth::user()->id)
                            ->orderBy('id', 'Desc')->get()->toArray();
            return view('front.orders.orders')->with(compact('orders'));
        } else {
            // Jika ID pesanan ada, tampilkan detail pesanan tertentu
            $orderDetails = Order::with('orders_products')->where('id', $id)->first()->toArray();
            return view('front.orders.order_details')->with(compact('orderDetails'));
        }
    }

    /**
     * Metode untuk menempatkan (place) pesanan baru.
     * Ini adalah inti dari proses checkout.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function placeOrder(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();

            // --- Validasi Data (Contoh, sesuaikan dengan kebutuhan Anda) ---
            // Asumsi validasi dasar untuk alamat pengiriman sudah dilakukan di frontend atau middleware
            // Anda bisa menambahkan validasi di sini jika diperlukan, misal:
            // $request->validate([
            //     'name' => 'required',
            //     'address' => 'required',
            //     'city' => 'required',
            //     'payment_gateway' => 'required',
            //     // ... validasi lain
            // ]);

            // --- Dapatkan ID dan Email Pengguna ---
            $user_id = 0;
            $user_email = $data['email'] ?? null; // Pastikan email ada jika guest checkout
            if (Auth::check()) {
                $user_id = Auth::user()->id;
                $user_email = Auth::user()->email;
            }

            // --- Dapatkan Item Keranjang ---
            $getCartItems = Cart::where('session_id', Session::get('session_id'));
            if (Auth::check()) {
                $getCartItems = $getCartItems->orWhere('user_id', $user_id);
            }
            $getCartItems = json_decode(json_encode($getCartItems->get()), true);

            if (empty($getCartItems)) {
                Session::flash('error_message', 'Keranjang Anda kosong!');
                // Mengembalikan JSON dengan instruksi redirect untuk AJAX
                return response()->json(['error' => 'Keranjang kosong.', 'redirect' => url('/cart')], 400);
            }

            // --- Hitung Total Harga, Kupon, dan Biaya Pengiriman ---
            $total_price = 0;
            foreach ($getCartItems as $item) {
                $total_price += ($item['product_price'] * $item['product_qty']);
            }

            $coupon_amount = 0;
            $coupon_code = Session::get('couponCode');
            if (!empty($coupon_code)) {
                $coupon_details = Coupon::where('coupon_code', $coupon_code)->first();
                if ($coupon_details) {
                    $coupon_amount = $total_price * ($coupon_details->amount / 100);
                }
            }

            // Anda perlu mengimplementasikan logika untuk menghitung shipping_charges
            // Ini biasanya melibatkan pengecekan pincode, berat produk, dll.
            $shipping_charges = 0; // Placeholder, sesuaikan dengan logika Anda

            $grand_total = $total_price - $coupon_amount + $shipping_charges;

            // --- Simpan Detail Pesanan ke Tabel 'orders' ---
            $order = new Order;
            $order->user_id = $user_id;
            $order->name = $data['name'];
            $order->address = $data['address'];
            $order->city = $data['city'];
            $order->state = $data['state'];
            $order->country = $data['country'];
            $order->pincode = $data['pincode'];
            $order->mobile = $data['mobile'];
            $order->email = $user_email;
            $order->shipping_charges = $shipping_charges;
            $order->coupon_code = $coupon_code ?? '';
            $order->coupon_amount = $coupon_amount;
            $order->order_status = 'New'; // Status awal saat order dibuat
            $order->payment_method = $data['payment_gateway'];
            $order->payment_gateway = $data['payment_gateway'];
            $order->grand_total = $grand_total;
            $order->save();

            // --- Simpan Produk Pesanan ke Tabel 'orders_products' ---
            foreach ($getCartItems as $item) {
                $orderProduct = new OrdersProduct;
                $orderProduct->order_id = $order->id;
                $orderProduct->user_id = $user_id;
                $orderProduct->product_id = $item['product_id'];
                $orderProduct->product_code = $item['product_code'];
                $orderProduct->product_name = $item['product_name'];
                $orderProduct->product_size = $item['product_size'];
                $orderProduct->product_color = $item['product_color'];
                $orderProduct->product_price = $item['product_price'];
                $orderProduct->product_qty = $item['product_qty'];
                $orderProduct->vendor_id = $item['vendor_id'];
                $orderProduct->save();

                // Stok produk akan dikurangi setelah pembayaran sukses di MidtransController,
                // tapi jika Anda juga ingin mengurangi di sini (untuk metode non-Midtrans),
                // pastikan logika tidak duplikat. Untuk Midtrans, stok dikurangi saat
                // notifikasi 'Success' diterima.
            }

            // --- Logika Spesifik Berdasarkan Metode Pembayaran ---
            if ($data['payment_gateway'] == 'COD') {
                $order->payment_status = 'Pending';
                $order->save();
                // Hapus item dari keranjang setelah pesanan COD berhasil
                if (Auth::check()) {
                    Cart::where('user_id', $user_id)->delete();
                } else {
                    Cart::where('session_id', Session::get('session_id'))->delete();
                }
                Session::forget('couponCode');
                Session::forget('couponAmount');

                Session::flash('success_message', 'Pesanan Anda telah berhasil ditempatkan dengan COD. Pembayaran akan dilakukan saat pengiriman.');
                // Mengembalikan JSON dengan instruksi redirect untuk AJAX
                return response()->json(['status' => 'redirect', 'redirect' => url('/orders')]);

            } else if ($data['payment_gateway'] == 'paypal') {
                $order->payment_status = 'Pending';
                $order->save();
                // Redirect ke halaman PayPal
                return response()->json(['status' => 'redirect', 'redirect' => url('/paypal?order_id=' . $order->id)]);

            } else if ($data['payment_gateway'] == 'midtrans') {
                // Untuk Midtrans, status awal adalah 'Pending'.
                // Status akan diupdate oleh webhook dari Midtrans.
                $order->payment_status = 'Pending';
                $order->save();

                // Karena ini adalah AJAX call dari frontend, kita kembalikan order_id
                // agar JavaScript dapat memicu Midtrans Snap.
                // Keranjang akan dihapus di MidtransController::handleNotification()
                // setelah pembayaran berhasil dikonfirmasi oleh Midtrans.
                return response()->json(['order_id' => $order->id, 'status' => 'midtrans_initiated']);
            } else {
                // Metode pembayaran tidak valid
                Session::flash('error_message', 'Metode pembayaran tidak valid.');
                // Mengembalikan JSON dengan instruksi redirect untuk AJAX
                return response()->json(['error' => 'Metode pembayaran tidak valid.', 'redirect' => url('/cart')], 400);
            }
        }

        // Jika request bukan POST, kembalikan ke halaman checkout (misal: jika diakses langsung)
        // Mengembalikan JSON dengan instruksi redirect untuk AJAX
        return response()->json(['error' => 'Metode request tidak valid.', 'redirect' => url('/checkout')], 405);
    }
}
