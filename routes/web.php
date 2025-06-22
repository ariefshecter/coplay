<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Front\IndexController;
use App\Http\Controllers\Front\ProductsController;
use App\Http\Controllers\Front\UserController;
use App\Http\Controllers\Front\VendorController;
use App\Http\Controllers\Front\PaypalController; // Perbaikan: seharusnya App\Http\Controllers\Front\PaypalController
use App\Http\Controllers\Front\AddressController; // Perbaikan: seharusnya App\Http\Controllers\Front\AddressController
use App\Http\Controllers\Front\OrderController;
use App\Http\Controllers\Front\RatingController;
use App\Http\Controllers\Front\NewsletterController;
use App\Http\Controllers\Front\CmsController; // Perbaikan: seharusnya App\Http\Controllers\Front\CmsController
use App\Http\Controllers\Front\IyzipayController;
use App\Http\Controllers\Front\MidtransController; // Import MidtransController baru

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan rute web untuk aplikasi Anda. Rute ini
| dimuat oleh RouteServiceProvider dalam grup yang
| berisi grup middleware "web". Sekarang buat sesuatu yang hebat!
|
*/

require __DIR__.'/auth.php';



// Catatan: WEBSITE KAMI AKAN MEMILIKI DUA BAGIAN UTAMA: RUTE ADMIN (untuk Panel Admin) & RUTE DEPAN (untuk bagian Frontend)!:

// Pertama: Rute Panel Admin:
// Bagian 'ADMIN' website: Grup Rute untuk rute yang dimulai dengan kata 'admin' (Grup Rute Admin) 	// CATATAN: SEMUA RUTE DI DALAM PREFIX INI DIMULAI DENGAN 'admin/', JADI RUTE-RUTE TERSEBUT DI DALAM PREFIX, ANDA TIDAK MENULIS '/admin' KETIKA ANDA MENDEFINISIKANNYA, ITU AKAN DIDEFINISIKAN SECARA OTOMATIS!!
Route::prefix('/admin')->namespace('App\Http\Controllers\Admin')->group(function() {
	Route::match(['get', 'post'], 'login', 'AdminController@login'); // Metode match() digunakan untuk menggunakan lebih dari satu metode permintaan HTTP untuk rute yang sama, jadi GET untuk merender halaman login.php, dan POST untuk pengiriman <form> halaman login.php (misalnya GET dan POST) 	// Cocok dengan URL '/admin/dashboard' (yaitu http://127.0.0.1:8000/admin/dashboard)


	// Ini adalah Grup Rute untuk rute yang SEMUA dimulai dengan 'admin/-sesuatu' dan menggunakan Penjaga Otentikasi 'admin' 	// Catatan: Anda harus menghapus bagian '/admin'/ dari rute yang ditulis di dalam Grup Rute ini (misalnya 	Route::get('logout'); 	, BUKAN 	Route::get('admin/logout'); 	)
	Route::group(['middleware' => ['admin']], function() { // menggunakan penjaga 'admin' kami (yang kami buat di auth.php)
		Route::get('dashboard', 'AdminController@dashboard'); // Login Admin
		Route::get('logout', 'AdminController@logout'); // Logout Admin
		Route::match(['get', 'post'], 'update-admin-password', 'AdminController@updateAdminPassword'); // Permintaan GET untuk melihat <form> pembaruan kata sandi, dan permintaan POST untuk mengirimkan <form> pembaruan kata sandi
		Route::post('check-admin-password', 'AdminController@checkAdminPassword'); // Periksa Kata Sandi Admin // Rute ini dipanggil dari panggilan AJAX di file admin/js/custom.js
		Route::match(['get', 'post'], 'update-admin-details', 'AdminController@updateAdminDetails'); // Perbarui Detail Admin di halaman update_admin_details.blade.php 	// Metode 'GET' untuk menampilkan halaman update_admin_details.blade.php, dan metode 'POST' untuk pengiriman <form> di halaman yang sama
		Route::match(['get', 'post'], 'update-vendor-details/{slug}', 'AdminController@updateVendorDetails'); // Perbarui Detail Vendor 	// Di slug kita dapat meneruskan: 'personal' yang berarti perbarui detail pribadi vendor, atau 'business' yang berarti perbarui detail bisnis vendor, atau 'bank' yang berarti perbarui detail bank vendor 	// Kami akan membuat satu tampilan (bukan 3) untuk 3 halaman, tetapi bagian di dalamnya akan berubah tergantung pada nilai $slug 	// Metode GET untuk menampilkan halaman detail admin, metode POST untuk pengiriman <form>

		// Perbarui persentase komisi vendor (oleh Admin) di tabel `vendors` (untuk setiap vendor sendiri) di Panel Admin di admin/admins/view_vendor_details.blade.php (Modul Komisi: Setiap vendor harus membayar komisi tertentu (yang dapat bervariasi dari satu vendor ke vendor lain) kepada pemilik website (admin) untuk setiap item yang terjual, dan itu didefinisikan oleh pemilik website (admin))
		Route::post('update-vendor-commission', 'AdminController@updateVendorCommission');

		Route::get('admins/{type?}', 'AdminController@admins'); // Dalam kasus pengguna terotentikasi (pengguna yang masuk) adalah superadmin, admin, subadmin, vendor, ini adalah tiga URL Manajemen Admin tergantung pada slug. Slug adalah kolom `type` di tabel `admins` yang hanya bisa: superadmin, admin, subadmin, atau vendor 	// Menggunakan Parameter Rute Opsional (atau Parameter Rute Opsional) menggunakan tanda tanya '?', untuk kasus di mana tidak ada {type} yang dilewatkan, halaman akan menampilkan SEMUA superadmin, admins, subadmins dan vendors di halaman yang sama
		Route::get('view-vendor-details/{id}', 'AdminController@viewVendorDetails'); // Lihat detail 'vendor' lebih lanjut di dalam tabel Manajemen Admin (jika pengguna terotentikasi adalah superadmin, admin, atau subadmin)
		Route::post('update-admin-status', 'AdminController@updateAdminStatus'); // Perbarui Status Admin menggunakan AJAX di admins.blade.php
	

		// Bagian (Bagian, Kategori, Subkategori, Produk, Atribut)
		Route::get('sections', 'SectionController@sections');
		Route::post('update-section-status', 'SectionController@updateSectionStatus'); // Perbarui Status Bagian menggunakan AJAX di sections.blade.php
		Route::get('delete-section/{id}', 'SectionController@deleteSection'); // Hapus bagian di sections.blade.php
		Route::match(['get', 'post'], 'add-edit-section/{id?}', 'SectionController@addEditSection'); // Slug {id?} adalah Parameter Opsional, jadi jika dilewatkan, ini berarti Edit/Perbarui bagian, dan jika tidak dilewatkan, ini berarti Tambah Bagian

		// Kategori
		Route::get('categories', 'CategoryController@categories'); // Kategori di Manajemen Katalog di Panel Admin
		Route::post('update-category-status', 'CategoryController@updateCategoryStatus'); // Perbarui Status Kategori menggunakan AJAX di categories.blade.php
		Route::match(['get', 'post'], 'add-edit-category/{id?}', 'CategoryController@addEditCategory'); // Slug {id?} adalah Parameter Opsional, jadi jika dilewatkan, ini berarti Edit/Perbarui Kategori, dan jika tidak dilewatkan, ini berarti Tambah Kategori
		Route::get('append-categories-level', 'CategoryController@appendCategoryLevel'); // Tampilkan <select> <option> Kategori tergantung pada Bagian yang dipilih (tampilkan kategori yang relevan dari bagian yang dipilih) menggunakan AJAX di admin/js/custom.js di halaman append_categories_level.blade.php
		Route::get('delete-category/{id}', 'CategoryController@deleteCategory'); // Hapus kategori di categories.blade.php
		Route::get('delete-category-image/{id}', 'CategoryController@deleteCategoryImage'); // Hapus gambar kategori di halaman add_edit_category.blade.php dari SERVER (SISTEM FILE) & DATABASE

		// Merk
		Route::get('brands', 'BrandController@brands');
		Route::post('update-brand-status', 'BrandController@updateBrandStatus'); // Perbarui Status Merk menggunakan AJAX di brands.blade.php
		Route::get('delete-brand/{id}', 'BrandController@deleteBrand'); // Hapus merk di brands.blade.php
		Route::match(['get', 'post'], 'add-edit-brand/{id?}', 'BrandController@addEditBrand'); // Slug {id?} adalah Parameter Opsional, jadi jika dilewatkan, ini berarti Edit/Perbarui merk, dan jika tidak dilewatkan, ini berarti Tambah Merk

		// Produk
		Route::get('products', 'ProductsController@products'); // render halaman products.blade.php di Panel Admin
		Route::post('update-product-status', 'ProductsController@updateProductStatus'); // Perbarui Status Produk menggunakan AJAX di products.blade.php
		Route::get('delete-product/{id}', 'ProductsController@deleteProduct'); // Hapus produk di products.blade.php
		Route::match(['get', 'post'], 'add-edit-product/{id?}', 'ProductsController@addEditProduct'); // Slug (Parameter Rute) {id?} adalah Parameter Opsional, jadi jika dilewatkan, ini berarti 'Edit/Perbarui Produk', dan jika tidak dilewatkan, ini berarti 'Tambah Produk' 	// Permintaan GET untuk merender tampilan add_edit_product.blade.php, dan permintaan POST untuk mengirimkan <form> di tampilan tersebut
		Route::get('delete-product-image/{id}', 'ProductsController@deleteProductImage'); // Hapus gambar produk (di tiga folder: small, medium, dan large) di halaman add_edit_product.blade.php dari SERVER (SISTEM FILE) & DATABASE
		Route::get('delete-product-video/{id}', 'ProductsController@deleteProductVideo'); // Hapus video produk di halaman add_edit_product.blade.php dari SERVER (SISTEM FILE) & DATABASE

		// Atribut
		Route::match(['get', 'post'], 'add-edit-attributes/{id}', 'ProductsController@addAttributes'); // Permintaan GET untuk merender tampilan add_edit_attributes.blade.php, dan permintaan POST untuk mengirimkan <form> di tampilan tersebut
		Route::post('update-attribute-status', 'ProductsController@updateAttributeStatus'); // Perbarui Status Atribut menggunakan AJAX di add_edit_attributes.blade.php
		Route::get('delete-attribute/{id}', 'ProductsController@deleteAttribute'); // Hapus atribut di add_edit_attributes.blade.php
		Route::match(['get', 'post'], 'edit-attributes/{id}', 'ProductsController@editAttributes'); // di add_edit_attributes.blade.php

		// Gambar
		Route::match(['get', 'post'], 'add-images/{id}', 'ProductsController@addImages'); // Permintaan GET untuk merender tampilan add_edit_attributes.blade.php, dan permintaan POST untuk mengirimkan <form> di tampilan tersebut
		Route::post('update-image-status', 'ProductsController@updateImageStatus'); // Perbarui Status Gambar menggunakan AJAX di add_images.blade.php
		Route::get('delete-image/{id}', 'ProductsController@deleteImage'); // Hapus gambar di add_images.blade.php

		// Spanduk
		Route::get('banners', 'BannersController@banners');
		Route::post('update-banner-status', 'BannersController@updateBannerStatus'); // Perbarui Status Kategori menggunakan AJAX di banners.blade.php
		Route::get('delete-banner/{id}', 'BannersController@deleteBanner'); // Hapus spanduk di banners.blade.php
		Route::match(['get', 'post'], 'add-edit-banner/{id?}', 'BannersController@addEditBanner'); // Slug (Parameter Rute) {id?} adalah Parameter Opsional, jadi jika dilewatkan, ini berarti 'Edit/Perbarui Spanduk', dan jika tidak dilewatkan, ini berarti 'Tambah Spanduk' 	// Permintaan GET untuk merender tampilan add_edit_banner.blade.php, dan permintaan POST untuk mengirimkan <form> di tampilan tersebut

		// Filter
		Route::get('filters', 'FilterController@filters'); // Render halaman filters.blade.php
		Route::post('update-filter-status', 'FilterController@updateFilterStatus'); // Perbarui Status Filter menggunakan AJAX di filters.blade.php
		Route::post('update-filter-value-status', 'FilterController@updateFilterValueStatus'); // Perbarui Status Nilai Filter menggunakan AJAX di filters_values.blade.php
		Route::get('filters-values', 'FilterController@filtersValues'); // Render halaman filters_values.blade.php
		Route::match(['get', 'post'], 'add-edit-filter/{id?}', 'FilterController@addEditFilter'); // Slug (Parameter Rute) {id?} adalah Parameter Opsional, jadi jika dilewatkan, ini berarti 'Edit/Perbarui filter', dan jika tidak dilewatkan, ini berarti 'Tambah filter' 	// Permintaan GET untuk merender tampilan add_edit_filter.blade.php, dan permintaan POST untuk mengirimkan <form> di tampilan tersebut
		Route::match(['get', 'post'], 'add-edit-filter-value/{id?}', 'FilterController@addEditFilterValue'); // Slug (Parameter Rute) {id?} adalah Parameter Opsional, jadi jika dilewatkan, ini berarti 'Edit/Perbarui Nilai Filter', dan jika tidak dilewatkan, ini berarti 'Tambah Nilai Filter' 	// Permintaan GET untuk merender tampilan add_edit_filter_value.blade.php, dan permintaan POST untuk mengirimkan <form> di tampilan tersebut
		Route::post('category-filters', 'FilterController@categoryFilters'); // Tampilkan filter terkait tergantung pada <select> kategori yang dipilih di category_filters.blade.php (yang pada gilirannya disertakan oleh add_edit_product.php) menggunakan AJAX. Periksa admin/js/custom.js

		// Kupon
		Route::get('coupons', 'CouponsController@coupons'); // Render halaman admin/coupons/coupons.blade.php di Panel Admin
		Route::post('update-coupon-status', 'CouponsController@updateCouponStatus'); // Perbarui Status Kupon (aktif/nonaktif) melalui AJAX di admin/coupons/coupons.blade.php, periksa admin/js/custom.js
		Route::get('delete-coupon/{id}', 'CouponsController@deleteCoupon'); // Hapus Kupon melalui AJAX di admin/coupons/coupons.blade.php, periksa admin/js/custom.js

		// Render halaman admin/coupons/add_edit_coupon.blade.php dengan permintaan 'GET' ('Edit/Perbarui Kupon') jika Parameter Opsional {id?} dilewatkan, atau jika tidak dilewatkan, itu juga permintaan GET untuk 'Tambah Kupon', atau itu adalah permintaan POST untuk pengiriman Form HTML di halaman yang sama
		Route::match(['get', 'post'], 'add-edit-coupon/{id?}', 'CouponsController@addEditCoupon'); // Slug (Parameter Rute) {id?} adalah Parameter Opsional, jadi jika dilewatkan, ini berarti 'Edit/Perbarui Kupon', dan jika tidak dilewatkan, ini berarti 'Tambah Kupon' 	// Permintaan GET untuk merender tampilan add_edit_coupon.blade.php (apakah Tambah atau Edit tergantung pada apakah Parameter Opsional {id?} dilewatkan atau tidak), dan permintaan POST untuk mengirimkan <form> di halaman yang sama

		// Pengguna
		Route::get('users', 'UserController@users'); // Render halaman admin/users/users.blade.php di Panel Admin
		Route::post('update-user-status', 'UserController@updateUserStatus'); // Perbarui Status Pengguna (aktif/nonaktif) melalui AJAX di admin/users/users.blade.php, periksa admin/js/custom.js

		// Pesanan
		// Render halaman admin/orders/orders.blade.php (bagian Manajemen Pesanan) di Panel Admin
		Route::get('orders', 'OrderController@orders');

		// Render halaman admin/orders/order_details.blade.php (Lihat halaman Detail Pesanan) ketika mengklik ikon Lihat Detail Pesanan di admin/orders/orders.blade.php (tab Pesanan di bawah bagian Manajemen Pesanan di Panel Admin)
		Route::get('orders/{id}', 'OrderController@orderDetails'); 

		// Perbarui Status Pesanan (yang ditentukan oleh 'admin' SAJA, bukan 'vendor', berbeda dengan "Perbarui Status Item" yang dapat diperbarui oleh 'vendor' dan 'admin') (Tertunda, Dikirim, Dalam Proses, Dibatalkan, ...) di admin/orders/order_details.blade.php di Panel Admin
		// Catatan: Tabel `order_statuses` berisi semua jenis status pesanan (yang dapat diperbarui oleh 'admin' SAJA di tabel `orders`) seperti: tertunda, dalam proses, dikirim, dibatalkan, ...dll. Di tabel `order_statuses`, kolom `name` bisa: 'Baru', 'Tertunda', 'Dibatalkan', 'Dalam Proses', 'Dikirim', 'Dikirim Sebagian', 'Terkirim', 'Terkirim Sebagian' dan 'Dibayar'. 'Dikirim Sebagian': Jika satu pesanan memiliki produk dari vendor yang berbeda, dan satu vendor telah mengirimkan produknya ke pelanggan sementara vendor lain tidak!. 'Terkirim Sebagian': jika satu pesanan memiliki produk dari vendor yang berbeda, dan satu vendor telah mengirimkan dan MENYAMPAIKAN produknya ke pelanggan sementara vendor lain tidak! 	// Tabel `order_item_statuses` berisi semua jenis status pesanan (yang dapat diperbarui oleh 'vendor' dan 'admin' di tabel `orders_products`) seperti: tertunda, dalam proses, dikirim, dibatalkan, ...dll.
		Route::post('update-order-status', 'OrderController@updateOrderStatus');

		// Perbarui Status Item (yang dapat ditentukan oleh 'vendor' dan 'admin', berbeda dengan "Perbarui Status Pesanan" yang diperbarui oleh 'admin' SAJA, bukan 'vendor') (Tertunda, Dalam Proses, Dikirim, Terkirim, ...) di admin/orders/order_details.blade.php di Panel Admin
		// Catatan: Tabel `order_statuses` berisi semua jenis status pesanan (yang dapat diperbarui oleh 'admin' SAJA di tabel `orders`) seperti: tertunda, dalam proses, dikirim, dibatalkan, ...dll. Di tabel `order_statuses`, kolom `name` bisa: 'Baru', 'Tertunda', 'Dibatalkan', 'Dalam Proses', 'Dikirim', 'Dikirim Sebagian', 'Terkirim', 'Terkirim Sebagian' dan 'Dibayar'. 'Dikirim Sebagian': Jika satu pesanan memiliki produk dari vendor yang berbeda, dan satu vendor telah mengirimkan produknya ke pelanggan sementara vendor lain tidak!. 'Terkirim Sebagian': jika satu pesanan memiliki produk dari vendor yang berbeda, dan satu vendor telah mengirimkan dan MENYAMPAIKAN produknya ke pelanggan sementara vendor lain tidak!
		Route::post('update-order-item-status', 'OrderController@updateOrderItemStatus');

		// Faktur Pesanan
		// Render halaman faktur pesanan (HTML) di order_invoice.blade.php
		Route::get('orders/invoice/{id}', 'OrderController@viewOrderInvoice'); 

		// Render faktur PDF pesanan di order_invoice.blade.php menggunakan Paket Dompdf
		Route::get('orders/invoice/pdf/{id}', 'OrderController@viewPDFInvoice'); 

		// Modul Biaya Pengiriman
		// Render halaman Biaya Pengiriman (admin/shipping/shipping_charges.blade.php) di Panel Admin hanya untuk 'admin', bukan untuk vendor
		Route::get('shipping-charges', 'ShippingController@shippingCharges');

		// Perbarui Status Pengiriman (aktif/nonaktif) melalui AJAX di admin/shipping/shipping_charages.blade.php, periksa admin/js/custom.js
		Route::post('update-shipping-status', 'ShippingController@updateShippingStatus');

		// Render halaman admin/shipping/edit_shipping_charges.blade.php jika ada permintaan HTTP 'GET' ('Edit/Perbarui Biaya Pengiriman'), atau tangani pengiriman Form HTML di halaman yang sama jika ada permintaan HTTP 'POST'
		Route::match(['get', 'post'], 'edit-shipping-charges/{id}', 'ShippingController@editShippingCharges'); 



		// Modul Pelanggan Buletin
		// Render halaman admin/subscribers/subscribers.blade.php (Tampilkan semua pelanggan Buletin di Panel Admin)
		Route::get('subscribers', 'NewsletterController@subscribers');

		// Perbarui Status Pelanggan (aktif/nonaktif) melalui AJAX di admin/subscribers/subscribers.blade.php, periksa admin/js/custom.js
		Route::post('update-subscriber-status', 'NewsletterController@updateSubscriberStatus');

		// Hapus Pelanggan melalui AJAX di admin/subscribers/subscribers.blade.php, periksa admin/js/custom.js
		Route::get('delete-subscriber/{id}', 'NewsletterController@deleteSubscriber'); 



		// Ekspor pelanggan (tabel database `newsletter_subscribers`) sebagai file Excel menggunakan Paket Maatwebsite/Laravel Excel di admin/subscribers/subscribers.blade.php
		Route::get('export-subscribers', 'NewsletterController@exportSubscribers');

		// Peringkat & Ulasan Pengguna
		// Render halaman admin/ratings/ratings.blade.php di Panel Admin
		Route::get('ratings', 'RatingController@ratings');

		// Perbarui Status Peringkat (aktif/nonaktif) melalui AJAX di admin/ratings/ratings.blade.php, periksa admin/js/custom.js
		Route::post('update-rating-status', 'RatingController@updateRatingStatus');

		// Hapus Peringkat melalui AJAX di admin/ratings/ratings.blade.php, periksa admin/js/custom.js
		Route::get('delete-rating/{id}', 'RatingController@deleteRating'); 
	});

});






// Unduh faktur PDF pesanan Pengguna (Kami akan menggunakan fungsi viewPDFInvoice() yang sama (tetapi dengan rute/URL yang berbeda!) untuk merender faktur PDF untuk 'admin' di Panel Admin dan agar pengguna dapat mengunduhnya!) (kami membuat rute ini di luar rute Panel Admin agar pengguna dapat menggunakannya!)
Route::get('orders/invoice/download/{id}', 'App\Http\Controllers\Admin\OrderController@viewPDFInvoice');


// Kedua: Rute bagian DEPAN:
Route::namespace('App\Http\Controllers\Front')->group(function() {
	Route::get('/', 'IndexController@index');


	// Rute Dinamis untuk kolom `url` di tabel `categories` menggunakan loop foreach 	// Rute Daftar/Kategori
	// Catatan Penting: Saat Anda menjalankan proyek Laravel ini untuk pertama kalinya dan jika Anda menjalankan perintah "php artisan migrate" untuk pertama kalinya, sebelum itu Anda harus mengomentari variabel $catUrls dan loop foreach berikut di file web.php (file rute), karena ketika kita menjalankan perintah artisan tersebut, tabel `categories` belum dibuat, dan ini menyebabkan kesalahan, jadi pastikan untuk mengomentari kode ini di file web.php sebelum menjalankan perintah "php artisan migrate" untuk pertama kalinya.
	$catUrls = \App\Models\Category::select('url')->where('status', 1)->get()->pluck('url')->toArray(); // Rute seperti: /men, /women, /shirts, ...
	// dd($catUrls);
	foreach ($catUrls as $key => $url) {
		// Catatan Penting: Saat Anda menjalankan proyek Laravel ini untuk pertama kalinya dan jika Anda menjalankan perintah "php artisan migrate" untuk pertama kalinya, sebelum itu Anda harus mengomentari variabel $catUrls dan loop foreach berikut di file web.php (file rute), karena ketika kita menjalankan perintah artisan tersebut, tabel `categories` belum dibuat, dan ini menyebabkan kesalahan, jadi pastikan untuk mengomentari kode ini di file web.php sebelum menjalankan perintah "php artisan migrate" untuk pertama kalinya.
		Route::match(['get', 'post'], '/' . $url, 'ProductsController@listing'); // digunakan metode match() untuk permintaan HTTP 'GET' untuk merender halaman listing.blade.php dan metode HTTP 'POST' untuk permintaan AJAX Filter Penyortiran atau pengiriman Form HTML dan jQuery untuk Filter Penyortiran TANPA AJAX, DAN JUGA untuk mengirimkan Form Pencarian di listing.blade.php 	// misalnya 	/men 	atau 	/computers 	// Catatan Penting: Saat Anda menjalankan proyek Laravel ini untuk pertama kalinya dan jika Anda menjalankan perintah "php artisan migrate" untuk pertama kalinya, sebelum itu Anda harus mengomentari variabel $catUrls dan loop foreach berikut di file web.php (file rute), karena ketika kita menjalankan perintah artisan tersebut, tabel `categories` belum dibuat, dan ini menyebabkan kesalahan, jadi pastikan untuk mengomentari kode ini di file web.php sebelum menjalankan perintah "php artisan migrate" untuk pertama kalinya.
	}


	// Login/Daftar Vendor
	Route::get('vendor/login-register', 'VendorController@loginRegister'); // merender halaman login_register.blade.php vendor

	// Daftar Vendor
	Route::post('vendor/register', 'VendorController@vendorRegister'); // pengiriman form HTML pendaftaran di halaman login_register.blade.php vendor

	// Konfirmasi Akun Vendor (dari 'vendor_confirmation.blade.php) dari email oleh Mailtrap
	Route::get('vendor/confirm/{code}', 'VendorController@confirmVendor'); // {code} adalah email vendor yang di-encode base64 yang dengannya mereka telah mendaftar yang merupakan Parameter Rute/Parameter URL: https://laravel.com/docs/9.x/routing#required-parameters 	// rute ini diminta (diakses/dibuka) dari dalam email yang dikirim ke vendor (vendor_confirmation.blade.php)

	// Render Halaman Detail Produk Tunggal di front/products/detail.blade.php
	Route::get('/product/{id}', 'ProductsController@detail');

	// Panggilan AJAX dari file front/js/custom.js, untuk menampilkan `harga` dan `stok` terkait yang benar tergantung pada `ukuran` yang dipilih (dari tabel `products_attributes`)) dengan mengklik kotak <select> ukuran di front/products/detail.blade.php
	Route::post('get-product-price', 'ProductsController@getProductPrice');

	// Tampilkan semua produk Vendor di front/products/vendor_listing.blade.php 	// Rute ini diakses dari elemen HTML <a> di front/products/vendor_listing.blade.php
	Route::get('/products/{vendorid}', 'ProductsController@vendorListing');

	// Tambahkan ke pengiriman <form> Keranjang di front/products/detail.blade.php
	Route::post('cart/add', 'ProductsController@cartAdd');

	// Render halaman Keranjang (front/products/cart.blade.php) 	// rute ini diakses dari tag HTML <a> di dalam pesan flash di dalam metode cartAdd() di Front/ProductsController.php (di dalam front/products/detail.blade.php)
	Route::get('cart', 'ProductsController@cart')->name('cart');

	// Perbarui Kuantitas Item Keranjang Panggilan AJAX di front/products/cart_items.blade.php. Periksa front/js/custom.js
	Route::post('cart/update', 'ProductsController@cartUpdate');

	// Hapus Item Keranjang Panggilan AJAX di front/products/cart_items.blade.php. Periksa front/js/custom.js
	Route::post('cart/delete', 'ProductsController@cartDelete');



// Render halaman Login/Daftar Pengguna (front/users/login_register.blade.php)
Route::get('user/login-register', ['as' => 'login', 'uses' => 'UserController@loginRegister']); 

// Menangani akses GET ke /user/register agar tidak error
Route::get('user/register', function () {
	return redirect('user/login-register');
});

// Pendaftaran Pengguna (pengiriman form melalui AJAX)
Route::post('user/register', 'UserController@userRegister');


	// Login Pengguna (di front/users/login_register.blade.php) pengiriman <form> menggunakan permintaan AJAX. Periksa front/js/custom.js
	Route::post('user/login', 'UserController@userLogin');

	// Logout Pengguna (Rute ini diakses dari tab Logout di menu drop-down di header (di front/layout/header.blade.php))
	Route::get('user/logout', 'UserController@userLogout');

	// Fungsionalitas Lupa Kata Sandi Pengguna (rute ini diakses dari tag <a> di front/users/login_register.blade.php melalui permintaan 'GET', dan melalui permintaan 'POST' ketika Form HTML dikirimkan di front/users/forgot_password.blade.php)
	Route::match(['get', ' post'], 'user/forgot-password', 'UserController@forgotPassword'); // Kami menggunakan metode match() untuk menggunakan get() untuk merender halaman front/users/forgot_password.blade.php, dan post() ketika Form HTML di halaman yang sama dikirimkan 	// Permintaan POST berasal dari permintaan AJAX. Periksa front/js/custom.js

	// Konfirmasi Akun Pengguna E-mail yang berisi 'Tautan Aktivasi' untuk mengaktifkan akun pengguna (di resources/views/emails/confirmation.blade.php, menggunakan Mailtrap)
	Route::get('user/confirm/{code}', 'UserController@confirmAccount'); // {code} adalah 'Kode Aktivasi' pengguna yang di-encode base64 yang dikirimkan ke pengguna di E-mail Konfirmasi yang dengannya mereka telah mendaftar, yang diterima sebagai Parameter Rute/Parameter URL di 'Tautan Aktivasi' 	// rute ini diminta (diakses/dibuka) dari dalam email yang dikirim ke pengguna (di resources/views/emails/confirmation.blade.php)

	// Form Pencarian Website (untuk mencari semua produk website). Periksa Form HTML di front/layout/header.blade.php
	Route::get('search-products', 'ProductsController@listing');

	// Pemeriksaan Ketersediaan Kode PIN: periksa apakah kode PIN Alamat Pengiriman pengguna ada di database kami (di `cod_pincodes` dan `prepaid_pincodes`) atau tidak di front/products/detail.blade.php melalui AJAX. Periksa front/js/custom.js
	Route::post('check-pincode', 'ProductsController@checkPincode');

	// Render halaman Hubungi Kami (front/pages/contact.blade.php) menggunakan Permintaan HTTP GET, atau Pengiriman Form HTML menggunakan Permintaan HTTP POST
	Route::match(['get', 'post'], 'contact', 'CmsController@contact');

	// Tambah pengiriman Form HTML email Pelanggan Buletin di front/layout/footer.blade.php ketika mengklik tombol Kirim (menggunakan Permintaan/Panggilan AJAX)
	Route::post('add-subscriber-email', 'NewsletterController@addSubscriber');

	// Tambah Peringkat & Ulasan pada produk di front/products/detail.blade.php
	Route::post('add-rating', 'RatingController@addRating');




	// Melindungi rute pengguna (pengguna harus terotentikasi/masuk) (untuk mencegah akses ke tautan ini saat tidak terotentikasi/belum masuk (keluar))
	Route::group(['middleware' => ['auth']], function() {
		// Render halaman Akun Pengguna dengan permintaan 'GET' (front/users/user_account.blade.php), atau pengiriman Form HTML di halaman yang sama dengan permintaan 'POST' menggunakan AJAX (untuk memperbarui detail pengguna). Periksa front/js/custom.js
		Route::match(['GET', 'POST'], 'user/account', 'UserController@userAccount');

		// Perbarui Kata Sandi Akun Pengguna pengiriman Form HTML melalui AJAX. Periksa front/js/custom.js
		Route::post('user/update-password', 'UserController@userUpdatePassword');

		// Penebusan Kode Kupon (Terapkan kupon) / pengiriman Form HTML Kode Kupon melalui AJAX di front/products/cart_items.blade.php, periksa front/js/custom.js
		Route::post('/apply-coupon', 'ProductsController@applyCoupon'); // Catatan Penting: Kami menambahkan rute ini di sini sebagai rute yang dilindungi di dalam grup middleware 'auth' karena HANYA pengguna yang masuk/terotentikasi yang diizinkan untuk menebus Kupon!

		// Halaman Checkout (menggunakan metode match() untuk permintaan 'GET' untuk merender halaman front/products/checkout.blade.php atau permintaan 'POST' untuk pengiriman Form HTML di halaman yang sama (untuk mengirimkan Alamat Pengiriman dan Metode Pembayaran pengguna))
		Route::match(['GET', 'POST'], '/checkout', 'ProductsController@checkout');

		// Edit Alamat Pengiriman (Muat ulang halaman dan isi bidang <input> dengan Alamat Pengiriman pengguna terotentikasi/masuk dari tabel database `delivery_addresses` saat mengklik tombol Edit) di front/products/delivery_addresses.blade.php (yang 'disertakan' di front/products/checkout.blade.php) via AJAX, periksa front/js/custom.js
		Route::post('get-delivery-address', 'AddressController@getDeliveryAddress');

		// Simpan Alamat Pengiriman melalui AJAX (simpan alamat pengiriman pengguna terotentikasi/masuk di tabel database `delivery_addresses` saat mengirimkan Form HTML) di front/products/delivery_addresses.blade.php (yang 'disertakan' di front/products/checkout.blade.php) melalui AJAX, periksa front/js/custom.js
		Route::post('save-delivery-address', 'AddressController@saveDeliveryAddress');

		// Hapus Alamat Pengiriman melalui AJAX (Muat ulang halaman dan isi bidang <input> dengan detail Alamat Pengiriman pengguna terotentikasi/masuk dari tabel database `delivery_addresses` saat mengklik tombol Hapus) di front/products/delivery_addresses.blade.php (yang 'disertakan' di front/products/checkout.blade.php) melalui AJAX, periksa front/js/custom.js
		Route::post('remove-delivery-address', 'AddressController@removeDeliveryAddress');

		// Merender halaman Terima Kasih (setelah melakukan pemesanan)
		Route::get('thanks', 'ProductsController@thanks');

		// Merender halaman 'Pesanan Saya' Pengguna
		Route::get('user/orders/{id?}', 'OrderController@orders'); // Jika slug {id?} (Parameter Opsional) dilewatkan, ini berarti pergi ke halaman front/orders/order_details.blade.php, dan jika tidak, ini berarti pergi ke halaman front/orders/orders.blade.php



		// Rute PayPal:
		// Integrasi gateway pembayaran PayPal di Laravel (rute ini diakses dari metode checkout() di Front/ProductsController.php). Merender halaman front/paypal/paypal.blade.php
		Route::get('paypal', 'PaypalController@paypal');

		// Lakukan pembayaran PayPal
		Route::post('pay', 'PaypalController@pay')->name('payment'); 

		// Pembayaran PayPal berhasil
		Route::get('success', 'PaypalController@success');

		// Pembayaran PayPal gagal
		Route::get('error', 'PaypalController@error');



		// Rute iyzipay (iyzico): 	// Integrasi Gateway Pembayaran iyzico di/dengan Laravel
		// Integrasi gateway pembayaran iyzico di Laravel (rute ini diakses dari metode checkout() di Front/ProductsController.php). Merender halaman front/iyzipay/iyzipay.blade.php
		Route::get('iyzipay', 'IyzipayController@iyzipay');

		// Lakukan pembayaran iyzipay (redirect pengguna ke gateway pembayaran iyzico dengan detail pesanan)
		Route::get('iyzipay/pay', 'IyzipayController@pay'); 
	});
	
	// Rute Pembayaran Midtrans
	// Rute untuk menginisiasi pembayaran Midtrans (melalui AJAX dari frontend)
	// Ditempatkan di luar grup 'auth' jika Anda ingin memungkinkan pembayaran oleh tamu (guest)
	// Jika hanya untuk pengguna yang masuk, tetap di dalam grup 'auth' yang sudah ada.
	// Saya akan menempatkannya di sini untuk fleksibilitas.
	Route::post('/midtrans/initiate-payment', [MidtransController::class, 'initiatePayment']);

	// Rute untuk callback dari Midtrans setelah pembayaran selesai (ini harus diakses publik)
	// Midtrans akan mengirimkan permintaan POST ke URL ini.
	Route::post('/midtrans/notification', [MidtransController::class, 'handleNotification']);

	// Rute untuk halaman redirect setelah pembayaran di Midtrans Snap selesai
	// Midtrans akan mengarahkan pengguna ke URL ini.
	Route::get('/midtrans/finish', [MidtransController::class, 'finish']);
	Route::get('/midtrans/error', [MidtransController::class, 'error']);
	Route::get('/midtrans/pending', [MidtransController::class, 'pending']);


});

// Rute Dashboard Laravel Breeze/Auth bawaan
Route::get('/dashboard', function () {
	return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rute Profil Laravel Breeze/Auth bawaan
Route::middleware('auth')->group(function () {
	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update'); // Perbaikan: seharusnya Route::patch
	Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Ini harus tetap di bagian paling bawah jika ada di file auth.php terpisah
// require __DIR__.'/auth.php'; // Baris ini sudah ada di awal file. Pastikan tidak dobel.
