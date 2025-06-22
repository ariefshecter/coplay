<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller; // Perbaikan: menggunakan backslash (\)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config; // Perbaikan: menggunakan backslash (\)
use Midtrans\Snap; // Perbaikan: menggunakan backslash (\)
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;

class MidtransController extends Controller
{
    /**
     * Konstruktor untuk mengatur konfigurasi dasar Midtrans.
     * Konfigurasi diambil dari file config/midtrans.php.
     */
    public function __construct()
    {
        // Set Server Key Merchant Anda
        Config::$serverKey = config('midtrans.serverKey');
        // Set lingkungan ke Production (true) atau Sandbox (false).
        Config::$isProduction = config('midtrans.isProduction');
        // Mengaktifkan sanitasi (default: true)
        Config::$isSanitized = true;
        // Mengaktifkan 3D Secure (default: true)
        Config::$is3ds = true;
    }

    /**
     * Menginisiasi pembayaran Midtrans Snap.
     * Menerima order_id dari frontend dan menghasilkan snap_token.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiatePayment(Request $request)
    {
        // Validasi order_id yang diterima dari frontend
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $orderId = $request->input('order_id');
        // Ambil data order beserta produk-produknya
        $order = Order::with('orders_products')->find($orderId);

        // Pastikan order ditemukan dan dimiliki oleh user yang login (jika user login)
        if (!$order || ($order->user_id !== Auth::id() && Auth::id() !== null)) {
            Session::flash('error_message', 'Pesanan tidak ditemukan atau tidak diizinkan!');
            return response()->json(['error' => 'Order not found or unauthorized.'], 404);
        }

        // Buat detail item untuk Midtrans
        $item_details = [];
        foreach ($order->orders_products as $product) {
            $item_details[] = [
                'id' => $product->product_code, // ID unik untuk item (misal: kode produk)
                'price' => $product->product_price,
                'quantity' => $product->product_qty,
                'name' => $product->product_name,
            ];
        }

        // Tambahkan biaya pengiriman sebagai item terpisah jika ada
        if ($order->shipping_charges > 0) {
            $item_details[] = [
                'id' => 'SHIPPING_FEE',
                'price' => $order->shipping_charges,
                'quantity' => 1,
                'name' => 'Biaya Pengiriman',
            ];
        }

        // Tambahkan diskon kupon sebagai item negatif jika ada
        if ($order->coupon_amount > 0) {
            $item_details[] = [
                'id' => 'COUPON_DISCOUNT',
                'price' => -$order->coupon_amount, // Diskon sebagai nilai negatif
                'quantity' => 1,
                'name' => 'Diskon Kupon',
            ];
        }

        // Parameter transaksi untuk Midtrans Snap
        $params = array(
            'transaction_details' => array(
                'order_id' => $order->id,        // ID unik pesanan Anda
                'gross_amount' => $order->grand_total, // Total harga yang harus dibayar
            ),
            'customer_details' => array(
                'first_name' => $order->name,
                'email' => $order->email,
                'phone' => $order->mobile,
            ),
            'item_details' => $item_details, // Detail produk yang dibeli
            'callbacks' => [
                'finish' => url('/midtrans/finish?order_id=' . $order->id), // URL saat pembayaran sukses/ditutup
                'error' => url('/midtrans/error?order_id=' . $order->id),   // URL saat pembayaran error
                'pending' => url('/midtrans/pending?order_id=' . $order->id), // URL saat pembayaran pending
            ],
        );

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken, 'order_id' => $order->id]);
        } catch (\Exception $e) {
            // Tangani error jika gagal mendapatkan Snap Token
            Session::flash('error_message', 'Terjadi kesalahan saat menginisiasi pembayaran: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Menangani notifikasi (webhook) dari Midtrans.
     * Midtrans akan memanggil endpoint ini untuk menginformasikan status transaksi.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleNotification(Request $request)
    {
        // Buat objek notifikasi Midtrans
        $notification = new \Midtrans\Notification();

        // Dapatkan status transaksi dari notifikasi
        $transactionStatus = $notification->transaction_status;
        $orderId = $notification->order_id;
        $fraudStatus = $notification->fraud_status;
        $paymentType = $notification->payment_type; // Tipe pembayaran (misal: bank_transfer, gopay)

        // Cari pesanan di database berdasarkan order_id
        $order = Order::where('id', $orderId)->first();

        if ($order) {
            $payment_status = ''; // Status pembayaran yang akan disimpan di database

            // Logika untuk menentukan status pembayaran berdasarkan notifikasi Midtrans
            if ($transactionStatus == 'capture') {
                // Untuk transaksi kartu kredit
                if ($fraudStatus == 'challenge') {
                    $payment_status = 'Challenged';
                } else if ($fraudStatus == 'accept') {
                    $payment_status = 'Success';
                }
            } else if ($transactionStatus == 'settlement') {
                // Transaksi berhasil diselesaikan (selain kartu kredit capture)
                $payment_status = 'Success';
            } else if ($transactionStatus == 'pending') {
                // Transaksi masih menunggu pembayaran
                $payment_status = 'Pending';
            } else if ($transactionStatus == 'deny') {
                // Transaksi ditolak
                $payment_status = 'Denied';
            } else if ($transactionStatus == 'expire') {
                // Transaksi kadaluarsa
                $payment_status = 'Expired';
            } else if ($transactionStatus == 'cancel') {
                // Transaksi dibatalkan
                $payment_status = 'Cancelled';
            }

            // Perbarui status pesanan di database
            $order->payment_method = 'Midtrans (' . $paymentType . ')'; // Simpan metode pembayaran spesifik
            $order->payment_gateway = 'Midtrans';
            $order->order_status = $payment_status; // Sesuaikan dengan status pesanan Anda (misal: 'Paid', 'Pending')
            $order->payment_status = $payment_status;
            $order->midtrans_transaction_id = $notification->transaction_id; // Simpan ID transaksi Midtrans
            $order->midtrans_payment_type = $paymentType; // Simpan tipe pembayaran Midtrans
            $order->save();

            // Jika pembayaran berhasil, hapus item dari keranjang pengguna
            if ($payment_status == 'Success') {
                if (Auth::check()) {
                    Cart::where('user_id', $order->user_id)->delete();
                } else {
                    Cart::where('session_id', Session::get('session_id'))->delete();
                }
                Session::forget('couponCode'); // Hapus kode kupon dari session
                Session::forget('couponAmount'); // Hapus jumlah kupon dari session

                // Opsional: Kirim email konfirmasi pesanan atau notifikasi lainnya
                // Pastikan Anda sudah punya logic pengiriman email
                // Mail::to($order->email)->send(new OrderShipped($order));

                // Opsional: Kurangi stok produk (pastikan ini hanya berjalan sekali)
                foreach ($order->orders_products as $orderedProduct) {
                    $product = Product::find($orderedProduct->product_id);
                    if ($product && $product->stock >= $orderedProduct->product_qty) {
                        $product->stock -= $orderedProduct->product_qty;
                        $product->save();
                    }
                }
            }

            // Anda bisa menambahkan log atau notifikasi tambahan di sini
        }

        // Selalu kembalikan respons OK ke Midtrans untuk mengkonfirmasi penerimaan notifikasi
        return response('OK', 200);
    }

    /**
     * Halaman callback setelah pembayaran Midtrans Selesai (finish).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function finish(Request $request)
    {
        $orderId = $request->query('order_id');
        $order = Order::find($orderId);

        // Periksa apakah order_id ada dan milik user yang sedang login (memungkinkan tamu juga)
        if ($order && ($order->user_id === Auth::id() || Auth::id() === null)) {
            // Disini Anda bisa melakukan pengecekan ulang status order dari Midtrans API jika perlu,
            // tapi handleNotification seharusnya sudah memperbarui status.
            if ($order->payment_status == 'Success' || $order->payment_status == 'Settlement') {
                Session::flash('success_message', 'Pembayaran Midtrans berhasil! Pesanan Anda telah dikonfirmasi.');
                return redirect('/orders'); // Arahkan ke halaman daftar pesanan user
            } else if ($order->payment_status == 'Pending') {
                Session::flash('info_message', 'Pembayaran Midtrans sedang menunggu konfirmasi. Silakan selesaikan pembayaran Anda.');
                return redirect('/orders');
            } else {
                Session::flash('error_message', 'Pembayaran Midtrans gagal atau dibatalkan.');
                return redirect('/cart'); // Arahkan kembali ke keranjang atau halaman checkout
            }
        } else {
            Session::flash('error_message', 'Pesanan tidak ditemukan atau tidak diizinkan untuk diakses.');
            return redirect('/cart');
        }
    }

    /**
     * Halaman callback setelah pembayaran Midtrans Gagal (error).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function error(Request $request)
    {
        $orderId = $request->query('order_id'); // Ambil order_id jika tersedia
        Session::flash('error_message', 'Pembayaran Midtrans gagal. Silakan coba lagi atau pilih metode pembayaran lain.');
        return redirect('/cart'); // Kembali ke keranjang atau halaman checkout
    }

    /**
     * Halaman callback setelah pembayaran Midtrans Pending.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pending(Request $request)
    {
        $orderId = $request->query('order_id'); // Ambil order_id jika tersedia
        Session::flash('info_message', 'Pembayaran Midtrans sedang menunggu konfirmasi dari Anda.');
        return redirect('/orders');
    }
}
