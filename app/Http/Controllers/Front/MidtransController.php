<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;

class MidtransController extends Controller
{
    /**
     * Konstruktor untuk mengatur konfigurasi dasar Midtrans.
     */
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Menginisiasi pembayaran dan menghasilkan Snap Token.
     * Method ini dipanggil oleh AJAX dari frontend setelah pesanan dibuat.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiatePayment(Request $request)
    {
        // Validasi order_id dari request AJAX
        $request->validate(['order_id' => 'required|exists:orders,id']);
        $order = Order::with('orders_products')->find($request->order_id);

        if (!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        // Buat detail item untuk Midtrans dari relasi 'orders_products'
        $item_details = [];
        foreach ($order->orders_products as $product) {
            $item_details[] = [
                'id' => $product->product_id,
                'price' => (int)round($product->product_price), // Pastikan harga adalah integer
                'quantity' => (int)$product->product_qty,
                'name' => substr($product->product_name, 0, 50), // Nama item maks 50 karakter
            ];
        }

        // Tambahkan biaya pengiriman sebagai item terpisah jika ada
        if ($order->shipping_charges > 0) {
            $item_details[] = [
                'id' => 'SHIPPING',
                'price' => (int)round($order->shipping_charges),
                'quantity' => 1,
                'name' => 'Biaya Pengiriman',
            ];
        }

        // Tambahkan diskon kupon sebagai item negatif jika ada
        if ($order->coupon_amount > 0) {
            $item_details[] = [
                'id' => 'DISCOUNT',
                'price' => -(int)round($order->coupon_amount),
                'quantity' => 1,
                'name' => 'Diskon Kupon',
            ];
        }

        // Parameter transaksi untuk Midtrans Snap
        $params = [
            'transaction_details' => [
                'order_id' => $order->id . '-' . time(),
                'gross_amount' => (int)round($order->grand_total),
            ],
            'customer_details' => [
                'first_name' => $order->name,
                'email' => $order->email,
                'phone' => $order->mobile,
            ],
            'item_details' => $item_details,
            // **[PERBAIKAN] Menambahkan URL Callback dari Sisi Server**
            'callbacks' => [
                'finish' => url('/midtrans/finish'),
                'error' => url('/midtrans/error'),
                'pending' => url('/midtrans/pending'),
            ]
        ];

        try {
            // Dapatkan Snap Token dari Midtrans
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]); // Kirim token ke frontend
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Token Error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menginisiasi pembayaran.'], 500);
        }
    }

    /**
     * Menangani notifikasi (webhook) dari Midtrans.
     */
    public function handleNotification(Request $request)
    {
        $notification = new Notification();

        $transactionStatus = $notification->transaction_status;
        $orderIdParts = explode('-', $notification->order_id);
        $orderId = $orderIdParts[0];
        $fraudStatus = $notification->fraud_status;

        $order = Order::find($orderId);

        if (!$order || $order->payment_status === 'Success') {
            return response('OK', 200);
        }

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            if ($fraudStatus == 'accept') {
                $order->order_status = 'Paid';
                $order->payment_status = 'Success';
                $order->save();
                Cart::where('user_id', $order->user_id)->delete();
                Session::forget(['couponCode', 'couponAmount']);
                foreach ($order->orders_products as $orderedProduct) {
                    Product::where('id', $orderedProduct->product_id)->decrement('stock', $orderedProduct->product_qty);
                }
            }
        } else if ($transactionStatus == 'pending') {
            $order->payment_status = 'Pending';
            $order->save();
        } else if ($transactionStatus == 'deny' || $transactionStatus == 'cancel' || $transactionStatus == 'expire') {
            $order->payment_status = 'Failed';
            $order->order_status = 'Cancelled';
            $order->save();
        }

        return response('OK', 200);
    }
    
    /**
     * Menangani redirect setelah pembayaran selesai (finish).
     * Akan mengarahkan ke halaman checkout dengan notifikasi sukses.
     */
    public function finish(Request $request) {
        Session::flash('success_message', 'Pembayaran berhasil! Terima kasih, pesanan Anda akan segera kami proses.');
        return redirect('/checkout');
    }

    /**
     * Menangani redirect jika pembayaran gagal (error).
     * Akan mengarahkan ke halaman checkout dengan notifikasi error.
     */
    public function error(Request $request) {
        Session::flash('error_message', 'Pembayaran gagal atau dibatalkan. Silakan coba lagi.');
        return redirect('/checkout');
    }
    
    /**
     * Menangani redirect jika pembayaran tertunda (pending).
     * Akan mengarahkan ke halaman checkout dengan notifikasi informasi.
     */
    public function pending(Request $request) {
        Session::flash('info_message', 'Pembayaran Anda sedang menunggu penyelesaian. Kami akan mengupdate status setelah pembayaran dikonfirmasi.');
        return redirect('/checkout');
    }
}
