<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Import Admin Controllers
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BannersController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponsController;
use App\Http\Controllers\Admin\FilterController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductsController as AdminProductsController;
use App\Http\Controllers\Admin\RatingController as AdminRatingController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

// Import Front (User-facing) Controllers
use App\Http\Controllers\Front\IndexController;
use App\Http\Controllers\Front\ProductsController;
use App\Http\Controllers\Front\UserController;
use App\Http\Controllers\Front\VendorController;
use App\Http\Controllers\Front\PaypalController;
use App\Http\Controllers\Front\AddressController;
use App\Http\Controllers\Front\OrderController;
use App\Http\Controllers\Front\RatingController;
use App\Http\Controllers\Front\NewsletterController;
use App\Http\Controllers\Front\CmsController;
use App\Http\Controllers\Front\IyzipayController;
use App\Http\Controllers\Front\MidtransController;

// Import Breeze Profile Controller
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Include default auth routes from Laravel Breeze
require __DIR__.'/auth.php';


/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/
Route::prefix('/admin')->group(function() {
    // Admin Login Route
    Route::match(['get', 'post'], 'login', [AdminController::class, 'login']);

    // Admin Authenticated Routes
    Route::group(['middleware' => ['admin']], function() {
        // Dashboard & Settings
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('logout', [AdminController::class, 'logout']);
        Route::match(['get', 'post'], 'update-admin-password', [AdminController::class, 'updateAdminPassword']);
        Route::post('check-admin-password', [AdminController::class, 'checkAdminPassword']);
        Route::match(['get', 'post'], 'update-admin-details', [AdminController::class, 'updateAdminDetails']);

        // Vendor Management
        Route::match(['get', 'post'], 'update-vendor-details/{slug}', [AdminController::class, 'updateVendorDetails']);
        Route::post('update-vendor-commission', [AdminController::class, 'updateVendorCommission']);
        Route::get('admins/{type?}', [AdminController::class, 'admins']);
        Route::get('view-vendor-details/{id}', [AdminController::class, 'viewVendorDetails']);
        Route::post('update-admin-status', [AdminController::class, 'updateAdminStatus']);
        
        // Sections
        Route::get('sections', [SectionController::class, 'sections']);
        Route::post('update-section-status', [SectionController::class, 'updateSectionStatus']);
        Route::get('delete-section/{id}', [SectionController::class, 'deleteSection']);
        Route::match(['get', 'post'], 'add-edit-section/{id?}', [SectionController::class, 'addEditSection']);

        // Categories
        Route::get('categories', [CategoryController::class, 'categories']);
        Route::post('update-category-status', [CategoryController::class, 'updateCategoryStatus']);
        Route::match(['get', 'post'], 'add-edit-category/{id?}', [CategoryController::class, 'addEditCategory']);
        Route::get('append-categories-level', [CategoryController::class, 'appendCategoryLevel']);
        Route::get('delete-category/{id}', [CategoryController::class, 'deleteCategory']);
        Route::get('delete-category-image/{id}', [CategoryController::class, 'deleteCategoryImage']);

        // Brands
        Route::get('brands', [BrandController::class, 'brands']);
        Route::post('update-brand-status', [BrandController::class, 'updateBrandStatus']);
        Route::get('delete-brand/{id}', [BrandController::class, 'deleteBrand']);
        Route::match(['get', 'post'], 'add-edit-brand/{id?}', [BrandController::class, 'addEditBrand']);
        
        // Products
        Route::get('products', [AdminProductsController::class, 'products']);
        Route::post('update-product-status', [AdminProductsController::class, 'updateProductStatus']);
        Route::get('delete-product/{id}', [AdminProductsController::class, 'deleteProduct']);
        Route::match(['get', 'post'], 'add-edit-product/{id?}', [AdminProductsController::class, 'addEditProduct']);
        Route::get('delete-product-image/{id}', [AdminProductsController::class, 'deleteProductImage']);
        Route::get('delete-product-video/{id}', [AdminProductsController::class, 'deleteProductVideo']);

        // Product Attributes & Images
        Route::match(['get', 'post'], 'add-edit-attributes/{id}', [AdminProductsController::class, 'addAttributes']);
        Route::post('update-attribute-status', [AdminProductsController::class, 'updateAttributeStatus']);
        Route::get('delete-attribute/{id}', [AdminProductsController::class, 'deleteAttribute']);
        Route::match(['get', 'post'], 'edit-attributes/{id}', [AdminProductsController::class, 'editAttributes']);
        Route::match(['get', 'post'], 'add-images/{id}', [AdminProductsController::class, 'addImages']);
        Route::post('update-image-status', [AdminProductsController::class, 'updateImageStatus']);
        Route::get('delete-image/{id}', [AdminProductsController::class, 'deleteImage']);

        // Banners
        Route::get('banners', [BannersController::class, 'banners']);
        Route::post('update-banner-status', [BannersController::class, 'updateBannerStatus']);
        Route::get('delete-banner/{id}', [BannersController::class, 'deleteBanner']);
        Route::match(['get', 'post'], 'add-edit-banner/{id?}', [BannersController::class, 'addEditBanner']);

        // Filters
        Route::get('filters', [FilterController::class, 'filters']);
        Route::post('update-filter-status', [FilterController::class, 'updateFilterStatus']);
        Route::get('filters-values', [FilterController::class, 'filtersValues']);
        Route::post('update-filter-value-status', [FilterController::class, 'updateFilterValueStatus']);
        Route::match(['get', 'post'], 'add-edit-filter/{id?}', [FilterController::class, 'addEditFilter']);
        Route::match(['get', 'post'], 'add-edit-filter-value/{id?}', [FilterController::class, 'addEditFilterValue']);
        Route::post('category-filters', [FilterController::class, 'categoryFilters']);

        // Coupons
        Route::get('coupons', [CouponsController::class, 'coupons']);
        Route::post('update-coupon-status', [CouponsController::class, 'updateCouponStatus']);
        Route::get('delete-coupon/{id}', [CouponsController::class, 'deleteCoupon']);
        Route::match(['get', 'post'], 'add-edit-coupon/{id?}', [CouponsController::class, 'addEditCoupon']);

        // Users
        Route::get('users', [AdminUserController::class, 'users']);
        Route::post('update-user-status', [AdminUserController::class, 'updateUserStatus']);

        // Orders
        Route::get('orders', [AdminOrderController::class, 'orders']);
        Route::get('orders/{id}', [AdminOrderController::class, 'orderDetails']);
        Route::post('update-order-status', [AdminOrderController::class, 'updateOrderStatus']);
        Route::post('update-order-item-status', [AdminOrderController::class, 'updateOrderItemStatus']);
        Route::get('orders/invoice/{id}', [AdminOrderController::class, 'viewOrderInvoice']);
        Route::get('orders/invoice/pdf/{id}', [AdminOrderController::class, 'viewPDFInvoice']);

        // Shipping
        Route::get('shipping-charges', [ShippingController::class, 'shippingCharges']);
        Route::post('update-shipping-status', [ShippingController::class, 'updateShippingStatus']);
        Route::match(['get', 'post'], 'edit-shipping-charges/{id}', [ShippingController::class, 'editShippingCharges']);

        // Subscribers
        Route::get('subscribers', [AdminNewsletterController::class, 'subscribers']);
        Route::post('update-subscriber-status', [AdminNewsletterController::class, 'updateSubscriberStatus']);
        Route::get('delete-subscriber/{id}', [AdminNewsletterController::class, 'deleteSubscriber']);
        Route::get('export-subscribers', [AdminNewsletterController::class, 'exportSubscribers']);
        
        // Ratings
        Route::get('ratings', [AdminRatingController::class, 'ratings']);
        Route::post('update-rating-status', [AdminRatingController::class, 'updateRatingStatus']);
        Route::get('delete-rating/{id}', [AdminRatingController::class, 'deleteRating']);
    });
});

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
*/
Route::group([], function() {
    // Homepage
    Route::get('/', [IndexController::class, 'index']);

    // Dynamic Category Routes
    // Note: If you run migrations for the first time, comment out this block.
    try {
        if (DB::connection()->getDatabaseName()) {
            $catUrls = \App\Models\Category::select('url')->where('status', 1)->get()->pluck('url')->toArray();
            foreach ($catUrls as $url) {
                Route::match(['get', 'post'], '/' . $url, [ProductsController::class, 'listing']);
            }
        }
    } catch (\Exception $e) {
        Log::error("Database not connected yet for dynamic routes: " . $e->getMessage());
    }

    // Vendor Login/Register
    Route::get('vendor/login-register', [VendorController::class, 'loginRegister']);
    Route::post('vendor/register', [VendorController::class, 'vendorRegister']);
    Route::get('vendor/confirm/{code}', [VendorController::class, 'confirmVendor']);

    // Product, Cart, and General Pages
    Route::get('/product/{id}', [ProductsController::class, 'detail']);
    Route::post('get-product-price', [ProductsController::class, 'getProductPrice']);
    Route::get('/products/{vendorid}', [ProductsController::class, 'vendorListing']);
    Route::get('search-products', [ProductsController::class, 'listing']);
    Route::post('check-pincode', [ProductsController::class, 'checkPincode']);
    Route::post('cart/add', [ProductsController::class, 'cartAdd']);
    Route::get('cart', [ProductsController::class, 'cart'])->name('cart');
    Route::post('cart/update', [ProductsController::class, 'cartUpdate']);
    Route::post('cart/delete', [ProductsController::class, 'cartDelete']);
    Route::match(['get', 'post'], 'contact', [CmsController::class, 'contact']);
    Route::post('add-subscriber-email', [NewsletterController::class, 'addSubscriber']);
    Route::post('add-rating', [RatingController::class, 'addRating']);
    
    // User Login/Register/Forgot Password
    Route::get('user/login-register', [UserController::class, 'loginRegister'])->name('login');
    Route::post('user/register', [UserController::class, 'userRegister']);
    Route::post('user/login', [UserController::class, 'userLogin']);
    Route::get('user/logout', [UserController::class, 'userLogout']);
    Route::match(['get', 'post'], 'user/forgot-password', [UserController::class, 'forgotPassword']);
    Route::get('user/confirm/{code}', [UserController::class, 'confirmAccount']);

    // Midtrans Payment Routes (Callbacks/Notifications are public)
    // NOTE: Midtrans webhook/notification URL should be excluded from CSRF protection.
    // Add '/midtrans/notification' to the $except array in app/Http/Middleware/VerifyCsrfToken.php
    Route::post('/midtrans/notification', [MidtransController::class, 'handleNotification']);
    Route::get('/midtrans/finish', [MidtransController::class, 'finish']);
    Route::get('/midtrans/error', [MidtransController::class, 'error']);
    Route::get('/midtrans/pending', [MidtransController::class, 'pending']);


    // Authenticated User Routes
    Route::group(['middleware' => ['auth']], function() {
        // User Account & Orders
        Route::match(['get', 'post'], 'user/account', [UserController::class, 'userAccount']);
        Route::post('user/update-password', [UserController::class, 'userUpdatePassword']);
        Route::get('user/orders/{id?}', [OrderController::class, 'orders']);
        Route::get('orders/invoice/download/{id}', [AdminOrderController::class, 'viewPDFInvoice']);

        // Checkout Process
        Route::get('/checkout', [ProductsController::class, 'checkout']);
        Route::post('/checkout', [OrderController::class, 'placeOrder']); // <-- This is the main fix
        Route::post('/apply-coupon', [ProductsController::class, 'applyCoupon']);
        Route::get('thanks', [ProductsController::class, 'thanks']);
        
        // Delivery Addresses
        Route::post('get-delivery-address', [AddressController::class, 'getDeliveryAddress']);
        Route::post('save-delivery-address', [AddressController::class, 'saveDeliveryAddress']);
        Route::post('remove-delivery-address', [AddressController::class, 'removeDeliveryAddress']);

        // --- Payment Gateway Routes (for authenticated users) ---

        // Midtrans (Initiation requires auth)
        Route::post('/midtrans/initiate-payment', [MidtransController::class, 'initiatePayment']);

        // PayPal (if still used)
        Route::get('paypal', [PaypalController::class, 'paypal']);
        Route::post('pay', [PaypalController::class, 'pay'])->name('payment');
        Route::get('success', [PaypalController::class, 'success']);
        Route::get('error', [PaypalController::class, 'error']);

        // Iyzipay (if still used)
        Route::get('iyzipay', [IyzipayController::class, 'iyzipay']);
        Route::get('iyzipay/pay', [IyzipayController::class, 'pay']);
    });
});


/*
|--------------------------------------------------------------------------
| Laravel Breeze Default Routes
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
