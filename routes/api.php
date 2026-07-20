<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use App\Models\Category;
use App\Models\Brand;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\ProductImportExportController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\PickupLocationController;
use App\Http\Controllers\Admin\AnalyticsController;

//  ---------------------- PUBLIC ROUTES ----------------------------------------------------------------------------------
    
    // USER AUTH ROUTES
    Route::post('/signup', [AuthController::class, 'signup']); // USER SIGNUP
    Route::post('/login', [AuthController::class, 'login']); // USER LOGIN

    // ADMIN SIGNUP REQUEST
    Route::post('/admin/signup-request', [AdminAuthController::class, 'signupRequest']); // ADMIN SIGNUP REQUEST

    // ADMIN + SUPER ADMIN LOGIN AUTH ROUTES
    Route::post('/admin/login', [AdminAuthController::class, 'login']); // ADMIN LOGIN

    // GET PRODUCTS
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/brands', [ProductController::class, 'brands']);
    Route::get('/products/meta', [ProductController::class, 'meta']);
    
    
    Route::get('/payments/callback', [PaymentController::class, 'callback']); // PAYMENT CALLBACK
    // PAYMENT ROUTES
//  ---------------------- ---------------------------------------------------------------- -------------------------------


//  ----------------- USER PROTECTED ROUTES -------------------------------------------------------------------------------
    Route::middleware(['auth:sanctum'])->group(function () {

        // USER AUTH
        Route::get('/user/me', [AuthController::class, 'me']); // USER DETAILS
        Route::post('/user/logout', [AuthController::class, 'logout']); // USER LOGOUT

        // CART
        Route::post('/cart', [CartController::class, 'store']); // ADD TO CART
        Route::get('/cart', [CartController::class, 'index']); // VIEW CART
        Route::put('/cart/{id}', [CartController::class, 'update']); // UPDATE CART ITEM
        Route::delete('/cart/{id}', [CartController::class, 'destroy']); // REMOVE FROM CART
        Route::delete('/cart/clear', [CartController::class, 'clear']); // CLEAR CART

        // CHECKOUT & PAYMENTS
        Route::post('/payments/initiate', [PaymentController::class, 'initialize']); // INITIATE PAYMENT
        // Payment verification (Paystack callback/webhook)
        Route::get('/payments/verify', [PaymentController::class, 'verifyPayment']); // VERIFY PAYMENT (ALTERNATIVE TO CALLBACK)
        Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook']); // PAYSTACK WEBHOOK

        // ORDERS
        Route::get('/orders', [OrderController::class, 'index']); // user orders
        Route::get('/orders/{id}', [OrderController::class, 'show']); // single order

        // WISHLIST
        Route::post('/wishlist/{productId}', [WishlistController::class, 'store']); // ADD TO WISHLIST
        Route::get('/wishlist', [WishlistController::class, 'index']); // VIEW WISHLIST
        Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']); // REMOVE FROM WISHLIST

        // ORDERS (USER)
        // Route::get('/orders/{id}/confirmation', [
        //     CheckoutController::class,
        //     'confirmation'
        // ]);

        // REVIEWS (USER)
        // Route::post('/reviews', [ReviewController::class, 'store']); // ADD REVIEW
        // Route::put('/reviews/{id}', [ReviewController::class, 'update']); // UPDATE REVIEW
        // Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']); // DELETE REVIEW

        // PICKUP LOCATIONS
        // COUNTRIES
        Route::get('/superadmin/countries', [CountryController::class, 'index']); // VIEW COUNTRIES
        Route::get('/superadmin/countries/{id}', [CountryController::class, 'show']); // VIEW SINGLE COUNTRY
        // STATES
        Route::get('/superadmin/states', [StateController::class, 'index']); // VIEW STATES
        Route::get('/superadmin/states/{id}', [StateController::class, 'show']); // VIEW SINGLE STATE
        Route::get(
            '/superadmin/countries/{country}/states',
            [StateController::class, 'getByCountry']
        );
        // LOCATIONS
        Route::get('/superadmin/pickup-locations', [PickupLocationController::class, 'index']); // VIEW PICKUP LOCATIONS
        Route::get('/superadmin/pickup-locations/{id}', [PickupLocationController::class, 'show']); // VIEW SINGLE PICKUP LOCATION
        Route::get('/superadmin/states/{stateId}/locations', [PickupLocationController::class, 'getLocations']); // GET LOCATIONS BY STATE
    });
//  ---------------------- ---------------------------------------------------------------- -------------------------------


//  ---------------- ADMIN + SUPER ADMIN ROUTES ---------------------------------------------------------------------------
    Route::middleware(['auth:sanctum','role:admin,super_admin'])->group(function () {

        // ADMIN LOGOUT/ME (shared auth guard)
        Route::get('/admin/me', [AdminAuthController::class, 'me']); // ADMIN DETAILS
        Route::post('/admin/logout', [AdminAuthController::class, 'logout']); // ADMIN LOGOUT

        // PRODUCTS
        Route::get('/admin/products', [AdminProductController::class, 'index']); // VIEW PRODUCTS
        Route::get('/admin/products/categories', function () {
            return Category::all();
        });
        Route::get('/admin/products/brands', function () {
            return Brand::all();
        });
        
        // PRODUCT IMPORT/EXPORT
        Route::post('/admin/products/import', [ProductImportExportController::class, 'import']); // IMPORT PRODUCTS
        Route::get('/admin/products/export', [ProductImportExportController::class, 'export']); // EXPORT PRODUCTS

        Route::get('/admin/products/{id}', [AdminProductController::class, 'show']); // VIEW SINGLE PRODUCT
        Route::post('/admin/products', [AdminProductController::class, 'store']); // CREATE PRODUCT
        Route::post('/admin/products/{id}/images', [AdminProductController::class, 'uploadImages']); // UPLOAD PRODUCT IMAGES
        Route::put('/admin/products/{id}', [AdminProductController::class, 'update']); // UPDATE PRODUCT
        Route::delete('/admin/products/{id}', [AdminProductController::class, 'destroy']); // DELETE PRODUCT

        // ORDERS
        Route::get('/admin/orders', [AdminOrderController::class, 'index']); // VIEW ALL ORDERS
        Route::get('/admin/orders/{id}', [AdminOrderController::class, 'show']); // VIEW SINGLE ORDER
        Route::put('/admin/orders/{id}', [AdminOrderController::class, 'update']); // UPDATE ORDER
        Route::patch('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
        Route::delete('/admin/orders/{id}', [AdminOrderController::class, 'destroy']); // DELETE ORDER

        // REVIEWS
        // Route::get('/admin/reviews', [ReviewController::class, 'index']); // VIEW ALL REVIEWS
        // Route::put('/admin/reviews/{id}', [ReviewController::class, 'update']); // EDIT REVIEWS ADDED
        // Route::delete('/admin/reviews/{id}', [ReviewController::class, 'destroy']); // DELETE REVIEWS ADDED

    });
//  ---------------------- ---------------------------------------------------------------- -------------------------------


//  ---------------- SUPER ADMIN ONLY ROUTES ------------------------------------------------------------------------------
    Route::middleware(['auth:sanctum','role:super_admin'])->group(function () {

        // ADMIN REQUESTS
        Route::get('/superadmin/admin-requests', [SuperAdminController::class, 'adminRequests']); // GET ALL PENDING ADMIN SIGNUP REQUESTS

        // GET ALL ADMINS (PENDING + APPROVED + REJECTED)
        Route::get('/superadmin/admins', [SuperAdminController::class, 'getAdmins']); // GET ALL ADMINS (PENDING + APPROVED + REJECTED)
        Route::post('/superadmin/admin-request/{id}/approve', [SuperAdminController::class, 'approveAdmin']); // APPROVE ADMIN SIGNUP REQUEST
        Route::delete('/superadmin/admin-request/{id}', [SuperAdminController::class, 'rejectAdmin']); // REJECT & DELETE ADMIN SIGNUP REQUEST
        
        // ADMIN MANAGEMENT
        Route::delete('/superadmin/admins/{id}', [SuperAdminController::class, 'deleteAdmin']);

        // USER MANAGEMENT
        Route::get('/superadmin/users', [SuperAdminController::class, 'users']); // GET ALL USERS
        Route::post('/superadmin/user/block/{id}', [SuperAdminController::class, 'blockUser']); // BLOCK USER
        Route::post('/superadmin/user/unblock/{id}', [SuperAdminController::class, 'unblockUser']); // UNBLOCK USER
        Route::delete('/superadmin/users/{id}', [SuperAdminController::class, 'deleteUser']); // DELETE USER

        //PICKUP LOCATIONS
        // COUNTRIES
        Route::post('/superadmin/countries', [CountryController::class, 'store']); // CREATE COUNTRY
        Route::put('/superadmin/countries/{id}', [CountryController::class, 'update']); // UPDATE COUNTRY
        Route::delete('/superadmin/countries/{id}', [CountryController::class, 'destroy']); // DELETE COUNTRY
        // STATES
        Route::post('/superadmin/states', [StateController::class, 'store']); // CREATE STATE
        Route::put('/superadmin/states/{id}', [StateController::class, 'update']); // UPDATE STATE
        Route::delete('/superadmin/states/{id}', [StateController::class, 'destroy']); // DELETE STATE
        // PICKUP LOCATIONS
        Route::post('/superadmin/pickup-locations', [PickupLocationController::class, 'store']); // CREATE PICKUP LOCATION
        Route::put('/superadmin/pickup-locations/{id}', [PickupLocationController::class, 'update']); // UPDATE PICKUP LOCATION
        Route::delete('/superadmin/pickup-locations/{id}', [PickupLocationController::class, 'destroy']); // DELETE PICKUP LOCATION

        // ORDERS OVERVIEW
        Route::get('/superadmin/orders', [SuperAdminController::class, 'orders']);

        Route::get('/superadmin/analytics', [AnalyticsController::class, 'index']); // GET ANALYTICS DATA

        // REVIEWS OVERRIDE
        // Route::get('/superadmin/reviews', [SuperAdminController::class, 'reviews']);
        // Route::delete('/superadmin/reviews/{id}', [SuperAdminController::class, 'deleteReview']);
    });
//  ---------------------- ---------------------------------------------------------------- -------------------------------