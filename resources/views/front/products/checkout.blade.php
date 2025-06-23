{{-- Note: This page (view) is rendered by the checkout() method in the Front/ProductsController.php --}}
@extends('front.layout.layout')


@section('content')
    <!-- Page Introduction Wrapper -->
    <div class="page-style-a">
        <div class="container">
            <div class="page-intro">
                <h2>Checkout</h2>
                <ul class="bread-crumb">
                    <li class="has-separator">
                        <i class="ion ion-md-home"></i>
                        <a href="{{ url('/') }}">Home</a>
                    </li>
                    <li class="is-marked">
                        <a href="{{ url('/cart') }}">Cart</a>
                    </li>
                    <li class="is-marked">
                        <a href="javascript:void(0)">Checkout</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- Page Introduction Wrapper /- -->
    <!-- Checkout-Page -->
    <div class="page-checkout u-s-p-t-80">
        <div class="container">

            {{-- Menampilkan Notifikasi dari Controller --}}
            @if (Session::has('error_message'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong> {{ Session::get('error_message') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            @if (Session::has('success_message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Sukses:</strong> {{ Session::get('success_message') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            @if (Session::has('info_message'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong>Info:</strong> {{ Session::get('info_message') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif


            <div class="row">
                <div class="col-lg-12 col-md-12">

                    <div class="row">
                        <!-- Billing-&-Shipping-Details -->
                        <div class="col-lg-6" id="deliveryAddresses"> {{-- We created this id="deliveryAddresses" to use it as a handle for jQuery AJAX to refresh this page, check front/js/custom.js --}}
                            @include('front.products.delivery_addresses')
                        </div>
                        <!-- Billing-&-Shipping-Details /- -->

                        <!-- Checkout -->
                        <div class="col-lg-6">
                            {{-- The complete HTML Form of the user submitting their Delivery Address and Payment Method --}}
                            <form name="checkoutForm" id="checkoutForm" action="{{ url('/checkout') }}" method="post">
                                @csrf {{-- Preventing CSRF Requests: https://laravel.com/docs/9.x/csrf#preventing-csrf-requests --}}

                                {{-- Pilihan Alamat Pengiriman yang sudah ada --}}
                                @if (count($deliveryAddresses) > 0)
                                    <h4 class="section-h4">Alamat Pengiriman</h4>
                                    @foreach ($deliveryAddresses as $address)
                                        <div class="control-group" style="float: left; margin-right: 5px">
                                            {{-- We'll use the Custom HTML data attributes: shipping_charges, total_price, coupon_amount, codpincodeCount and prepaidpincodeCount to use them as handles for jQuery to change the calculations in "Your Order" section using jQuery. Check front/js/custom.js file --}}
                                            <input type="radio" id="address{{ $address['id'] }}" name="address_id" value="{{ $address['id'] }}"
                                                shipping_charges="{{ $address['shipping_charges'] }}"
                                                total_price="{{ $total_price }}"
                                                coupon_amount="{{ \Illuminate\Support\Facades\Session::get('couponAmount') }}"
                                                codpincodeCount="{{ $address['codpincodeCount'] }}"
                                                prepaidpincodeCount="{{ $address['prepaidpincodeCount'] }}"
                                                @if(Session::has('address_id') && Session::get('address_id') == $address['id']) checked @endif>
                                            <label class="control-label" for="address{{ $address['id'] }}">
                                                {{ $address['name'] }}, {{ $address['address'] }}, {{ $address['city'] }}, {{ $address['state'] }}, {{ $address['country'] }} ({{ $address['mobile'] }})
                                            </label>
                                            <a href="javascript:;" data-addressid="{{ $address['id'] }}" class="removeAddress" style="float: right; margin-left: 10px">Hapus</a>
                                            <a href="javascript:;" data-addressid="{{ $address['id'] }}" class="editAddress" style="float: right">Edit</a>
                                        </div>
                                    @endforeach
                                    <br>
                                @endif

                                <h4 class="section-h4">Pesanan Anda</h4>
                                <div class="order-table">
                                    <table class="u-s-m-b-13">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $total_price = 0 @endphp
                                            @foreach ($getCartItems as $item) {{-- $getCartItems is passed in from cart() method in Front/ProductsController.php --}}
                                                @php
                                                    $getDiscountAttributePrice = \App\Models\Product::getDiscountAttributePrice($item['product_id'], $item['size']);
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <a href="{{ url('product/' . $item['product_id']) }}">
                                                            <img width="50px" src="{{ asset('front/images/product_images/small/' . $item['product']['product_image']) }}" alt="Product">
                                                            <h6 class="order-h6">{{ $item['product']['product_name'] }}
                                                            <br>
                                                            </h6>
                                                        </a>
                                                        <span class="order-span-quantity">x {{ $item['quantity'] }}</span>
                                                    </td>
                                                    <td>
                                                        <h6 class="order-h6">Rp{{ $getDiscountAttributePrice['final_price'] * $item['quantity'] }}</h6>
                                                    </td>
                                                </tr>
                                                @php $total_price = $total_price + ($getDiscountAttributePrice['final_price'] * $item['quantity']) @endphp
                                            @endforeach

                                            <tr>
                                                <td>
                                                    <h3 class="order-h3">Subtotal</h3>
                                                </td>
                                                <td>
                                                    <h3 class="order-h3">Rp{{ $total_price }}</h3>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <h6 class="order-h6">Biaya Pengiriman</h6>
                                                </td>
                                                <td>
                                                    <h6 class="order-h6">
                                                        <span class="shipping_charges">Rp0</span> {{-- Ini akan diupdate oleh JS --}}
                                                    </h6>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <h6 class="order-h6">Diskon Kupon</h6>
                                                </td>
                                                <td>
                                                    <h6 class="order-h6">
                                                        @if (\Illuminate\Support\Facades\Session::has('couponAmount'))
                                                            <span class="couponAmount">Rp{{ \Illuminate\Support\Facades\Session::get('couponAmount') }}</span>
                                                        @else
                                                            Rp0
                                                        @endif
                                                    </h6>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <h3 class="order-h3">Grand Total</h3>
                                                </td>
                                                <td>
                                                    <h3 class="order-h3">
                                                        <strong class="grand_total">
                                                            Rp{{ $total_price - (\Illuminate\Support\Facades\Session::get('couponAmount') ?? 0) }}
                                                        </strong> {{-- Grand total awal sebelum biaya pengiriman dihitung JS --}}
                                                    </h3>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    {{-- Payment Methods --}}
                                    <h4 class="section-h4">Pilih Metode Pembayaran</h4>
                                    <div class="payment-methods">
                                        <div class="u-s-m-b-13 codMethod">
                                            <input type="radio" class="radio-box" name="payment_gateway" id="cash-on-delivery" value="COD" @if(Session::get('payment_gateway') == 'COD') checked @endif>
                                            <label class="label-text" for="cash-on-delivery">Cash on Delivery</label>
                                        </div>
                                        <div class="u-s-m-b-13 prepaidMethod">
                                            <input type="radio" class="radio-box" name="payment_gateway" id="paypal" value="paypal" @if(Session::get('payment_gateway') == 'paypal') checked @endif>
                                            <label class="label-text" for="paypal">PayPal</label>
                                        </div>
                                        <div class="u-s-m-b-13 prepaidMethod">
                                            <input type="radio" class="radio-box" name="payment_gateway" id="iyzipay" value="iyzipay" @if(Session::get('payment_gateway') == 'iyzipay') checked @endif>
                                            <label class="label-text" for="iyzipay">iyzipay</label>
                                        </div>
                                        {{-- Tambahkan Opsi Midtrans --}}
                                        <div class="u-s-m-b-13 prepaidMethod">
                                            <input type="radio" class="radio-box" name="payment_gateway" id="midtrans" value="midtrans" @if(Session::get('payment_gateway') == 'midtrans') checked @endif>
                                            <label class="label-text" for="midtrans">Midtrans</label>
                                        </div>
                                    </div>


                                    <div class="u-s-m-b-13">
                                        <input type="checkbox" class="check-box" id="accept" name="accept" value="Yes" title="Please agree to T&C">
                                        <label class="label-text no-color" for="accept">I’ve read and accept the
                                            <a href="terms-and-conditions.html" class="u-c-brand">terms & conditions</a>
                                        </label>
                                    </div>
                                    <button type="submit" id="PlaceOrder" class="button button-outline-secondary">Tempatkan Pesanan</button>
                                </div>
                            </form>
                        </div>
                        <!-- Checkout /- -->
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- Checkout-Page /- -->

{{-- JavaScript untuk Midtrans dan pengiriman form --}}
<script>
    $(document).ready(function() {
        var checkoutForm = $('#checkoutForm');
        var placeOrderButton = $('#PlaceOrder');

        // Pastikan token CSRF diatur untuk semua AJAX request
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        placeOrderButton.on('click', function(e) {
            var selectedPaymentMethod = $('input[name="payment_gateway"]:checked').val();
            console.log('Payment method selected:', selectedPaymentMethod); // DEBUG: Log metode pembayaran yang dipilih
            
            if (!selectedPaymentMethod) {
                alert('Silakan pilih metode pembayaran.');
                e.preventDefault();
                return false;
            }

            // Validasi alamat pengiriman sederhana: pastikan ada metode pembayaran yang dipilih
            // Asumsi input radio address_id untuk memilih alamat pengiriman
            var deliveryAddressId = $('input[name="address_id"]:checked').val();
            console.log('Delivery Address ID:', deliveryAddressId); // DEBUG: Log ID alamat pengiriman
            if (!deliveryAddressId) {
                alert('Silakan pilih alamat pengiriman atau tambahkan yang baru.');
                e.preventDefault();
                return false;
            }

            if (selectedPaymentMethod === 'midtrans') {
                console.log('Midtrans selected, preventing default form submission.'); // DEBUG: Konfirmasi Midtrans dipilih
                e.preventDefault(); // Hentikan pengiriman form normal untuk Midtrans

                placeOrderButton.prop('disabled', true).text('Memproses Pembayaran...');

                // Langkah 1: Kirim data form checkout ke OrderController@placeOrder
                // Ini akan membuat order di database dan mengembalikan order_id
                $.ajax({
                    url: checkoutForm.attr('action'),
                    type: checkoutForm.attr('method'),
                    data: checkoutForm.serialize(),
                    success: function(response) {
                        console.log('Response from OrderController (Step 1):', response); // DEBUG: Log respons dari OrderController
                        // Cek apakah response adalah JSON (untuk Midtrans) atau redirect (untuk COD/PayPal)
                        if (typeof response === 'object' && response.status === 'midtrans_initiated' && response.order_id) {
                            // Order berhasil dibuat di backend, sekarang inisiasi Midtrans Snap
                            
                            // Perbarui hidden input order_id jika perlu (penting untuk callback)
                            $('#order_id_hidden').val(response.order_id);

                            // Langkah 2: Panggil MidtransController@initiatePayment untuk mendapatkan snap_token
                            $.ajax({
                                url: '/midtrans/initiate-payment',
                                type: 'POST',
                                data: {
                                    order_id: response.order_id
                                },
                                success: function(snapResponse) {
                                    console.log('Response from MidtransController (Step 2 - Snap Token):', snapResponse); // DEBUG: Log respons Snap Token
                                    if (snapResponse.snap_token) {
                                        // Langkah 3: Snap Token berhasil didapat, tampilkan pop-up Snap
                                        snap.pay(snapResponse.snap_token, {
                                            onSuccess: function(result) {
                                                console.log('Midtrans Snap Success:', result); // DEBUG
                                                // Redirect ke halaman finish menggunakan URL absolut dari Laravel
                                                window.location.href = "{{ url('/midtrans/finish') }}";
                                            },
                                            onPending: function(result) {
                                                console.log('Midtrans Snap Pending:', result); // DEBUG
                                                // Redirect ke halaman pending menggunakan URL absolut dari Laravel
                                                window.location.href = "{{ url('/midtrans/pending') }}";
                                            },
                                            onError: function(result) {
                                                console.log('Midtrans Snap Error:', result); // DEBUG
                                                // Redirect ke halaman error menggunakan URL absolut dari Laravel
                                                window.location.href = "{{ url('/midtrans/error') }}";
                                            },
                                            onClose: function() {
                                                console.log('Midtrans Snap Closed by user.'); // DEBUG
                                                alert('Anda menutup pop-up pembayaran tanpa menyelesaikan transaksi.');
                                                // Kembalikan tombol ke keadaan semula
                                                placeOrderButton.prop('disabled', false).text('Tempatkan Pesanan');
                                            }
                                        });
                                    } else {
                                        console.error('Failed to get Snap Token:', snapResponse.error); // DEBUG
                                        alert(snapResponse.error || 'Gagal mendapatkan Snap Token.');
                                        placeOrderButton.prop('disabled', false).text('Tempatkan Pesanan');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX Error saat inisiasi Snap:', xhr.responseText, status, error); // DEBUG
                                    alert('Error AJAX saat inisiasi Snap: ' + xhr.responseText);
                                    placeOrderButton.prop('disabled', false).text('Tempatkan Pesanan');
                                }
                            });

                        } else {
                            // Ini akan menangani kasus di mana OrderController melakukan redirect untuk COD/PayPal
                            // atau jika ada error lain yang menyebabkan redirect
                            console.warn('OrderController did not return JSON for Midtrans. Redirecting...'); // DEBUG
                            // Lanjutkan dengan pengiriman form normal atau redirect sesuai respons
                            // Karena ini AJAX, Anda perlu secara manual mengarahkan user jika responsnya bukan JSON
                            if (response && response.redirect) { // Jika Laravel mengirimkan redirect sebagai bagian dari respons AJAX
                                window.location.href = response.redirect;
                            } else {
                                // Default jika bukan JSON dan bukan redirect eksplisit
                                // Biarkan form terkirim ulang atau tampilkan pesan
                                alert(response.error || 'Terjadi kesalahan tidak terduga saat menempatkan pesanan.');
                                placeOrderButton.prop('disabled', false).text('Tempatkan Pesanan');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error saat submit checkout form:', xhr.responseText, status, error); // DEBUG
                        alert('Error AJAX saat submit checkout form: ' + xhr.responseText);
                        placeOrderButton.prop('disabled', false).text('Tempatkan Pesanan');
                    }
                });

            } else {
                console.log('Non-Midtrans payment selected, submitting form normally.'); // DEBUG
                // Untuk metode pembayaran lain (COD, Paypal), biarkan form terkirim secara normal
                checkoutForm.submit();
            }
        });

        // Logika untuk mengubah biaya pengiriman dan total saat alamat dipilih
        // (ini mungkin sudah ada di front/js/custom.js Anda)
        $('input[name="address_id"]').on('change', function() {
            var selectedAddress = $(this);
            var shippingCharges = parseFloat(selectedAddress.attr('shipping_charges')) || 0;
            var totalPrice = parseFloat(selectedAddress.attr('total_price')) || 0;
            var couponAmount = parseFloat(selectedAddress.attr('coupon_amount')) || 0;

            // Update display of shipping charges
            $('.shipping_charges').text('Rp' + shippingCharges.toLocaleString('id-ID'));

            // Calculate new grand total
            var newGrandTotal = totalPrice - couponAmount + shippingCharges;
            $('.grand_total').text('Rp' + newGrandTotal.toLocaleString('id-ID'));
        });

        // Trigger change event on page load if an address is already checked
        if ($('input[name="address_id"]:checked').length > 0) {
            $('input[name="address_id"]:checked').trigger('change');
        } else {
            // Jika tidak ada alamat yang dipilih secara default, pastikan grand total awal diperbarui
            var initialTotalPrice = {{ $total_price ?? 0 }};
            var initialCouponAmount = {{ \Illuminate\Support\Facades\Session::get('couponAmount') ?? 0 }};
            var initialGrandTotal = initialTotalPrice - initialCouponAmount;
            $('.grand_total').text('Rp' + initialGrandTotal.toLocaleString('id-ID'));
        }
    });
</script>
@endsection
