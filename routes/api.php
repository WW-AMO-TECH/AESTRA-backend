<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SellerAuthController;
use App\Http\Controllers\SellerVerificationController;
use App\Http\Controllers\SuperAdminSellerController;
use App\Http\Controllers\Seller\SellerProductController;
use App\Http\Controllers\SuperAdminProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SuperAdminOrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Admin\ProductImportExportController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\PickupLocationController;
use App\Http\Controllers\Admin\AnalyticsController;


// ================================================================
// PUBLIC ROUTES
// ================================================================

// ---------------- USER AUTH ----------------
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);

// ---------------- SELLER AUTH ----------------
Route::post('/seller/signup', [SellerAuthController::class, 'signup']);
Route::post('/seller/login', [SellerAuthController::class, 'login']);

// ---------------- SUPER ADMIN AUTH ----------------
Route::post('/superadmin/signup', [SuperAdminController::class, 'signup']);
Route::post('/superadmin/login', [SuperAdminController::class, 'login']);

// ---------------- PUBLIC PRODUCTS ---------------
Route::get('/products/meta', [ProductController::class, 'meta']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// ---------------- PUBLIC BRANDS ----------------
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{id}', [BrandController::class, 'show']);

// ---------------- PUBLIC CATEGORIES ----------------
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// ---------------- PUBLIC REVIEWS ----------------
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

// ---------------- PAYSTACK CALLBACK ---------------
Route::get('/payments/callback', [PaymentController::class, 'callback']);

// ---------------- PAYSTACK WEBHOOK ----------------
// Paystack needs to reach this without Sanctum authentication.

Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook']);


// ================ AUTHENTICATED USER ROUTES ================
    Route::middleware(['auth:sanctum'])->group(function () {

        // ---------------- USER AUTH ----------------
        Route::get('/user/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // ---------------- CART ----------------
        Route::post('/cart', [CartController::class, 'store']);
        Route::get('/cart', [CartController::class, 'index']);
        Route::put('/cart/{id}', [CartController::class, 'update']);
        Route::delete('/cart/{id}', [CartController::class, 'destroy']);
        Route::delete('/cart/clear', [CartController::class, 'clear']);

        // ---------------- PICKUP LOCATIONS ----------------
        // COUNTRIES
        Route::get('/superadmin/countries', [CountryController::class, 'index']); // VIEW COUNTRIES
        Route::get('/superadmin/countries/{id}', [CountryController::class, 'show']); // VIEW SINGLE COUNTRY

        // STATES
        Route::get('/superadmin/states', [StateController::class, 'index']); // VIEW STATES
        Route::get('/superadmin/states/{id}', [StateController::class, 'show']); // VIEW SINGLE STATE
        Route::get('/superadmin/countries/{country}/states', [StateController::class, 'getByCountry']);

        // LOCATIONS
        Route::get('/superadmin/pickup-locations', [PickupLocationController::class, 'index']); // VIEW PICKUP LOCATIONS
        Route::get('/superadmin/pickup-locations/{id}', [PickupLocationController::class, 'show']); // VIEW SINGLE PICKUP LOCATION
        Route::get('/superadmin/states/{stateId}/locations', [PickupLocationController::class, 'getLocations']); // GET LOCATIONS BY STATE

        // ---------------- PAYMENTS ----------------
        Route::post('/payments/initiate', [PaymentController::class, 'initialize']);
        Route::get('/payments/verify', [PaymentController::class, 'verifyPayment']);
        Route::post('/payments/bank-transfer', [PaymentController::class, 'submitBankTransfer']);

        // ---------------- CUSTOMER ORDERS ----------------
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);

        // ---------------- WISHLIST ----------------
        Route::post('/wishlist/{productId}', [WishlistController::class, 'store']);
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);

        // ---------------- REVIEWS ----------------
        Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{reviewId}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{reviewId}', [ReviewController::class, 'destroy']);
    });
//  ---------------------- -----------------------

// ================ SELLER ROUTES ================
    Route::middleware(['auth:sanctum', 'role:seller'])->group(function () {

        // ---------------- SELLER PROFILE ----------------
        Route::get('/seller/me', [SellerAuthController::class, 'me']);

        // ---------------- SELLER VERIFICATION ----------------
        Route::get('/verification', [SellerVerificationController::class, 'show']);
        Route::post('/verification', [SellerVerificationController::class, 'submit']);

        // ---------------- SELLER PRODUCTS ----------------
        Route::get('/seller/products', [SellerProductController::class, 'index']);
        Route::get('/seller/products/{id}', [SellerProductController::class, 'show']);
        Route::post('/seller/products', [SellerProductController::class, 'store']);
        Route::put('/seller/products/{id}', [SellerProductController::class, 'update']);
        Route::patch('/seller/products/{id}', [SellerProductController::class, 'update']);
        Route::delete('/seller/products/{id}', [SellerProductController::class, 'destroy']);
        
        // Product images
        Route::post('/seller/products/{id}/images', [SellerProductController::class, 'uploadImages']);
        Route::delete('/seller/products/images/{image}', [SellerProductController::class, 'deleteImage']);
        Route::put( '/seller/products/images/{image}/primary', [SellerProductController::class, 'setPrimaryImage'] ); // SET PRIMARY IMAGE
        Route::put( '/seller/products/images/{image}/order', [SellerProductController::class, 'updateImageOrder'] ); // UPDATE IMAGE ORDER

        // Variants
        Route::post('/seller/products/{product}/variants', [SellerProductController::class, 'storeVariant']);
        Route::put('/seller/products/{product}/variants/{variant}', [SellerProductController::class, 'updateVariant']);
        Route::patch('/seller/products/{product}/variants/{variant}', [SellerProductController::class, 'updateVariant']);
        Route::delete('/seller/products/{product}/variants/{variant}', [SellerProductController::class, 'destroyVariant']);
        
        // ================ VARIANT IMAGES =================
        Route::post( '/seller/products/{product}/variants/{variant}/images', [SellerProductController::class, 'uploadVariantImages'] ); // UPLOAD VARIANT IMAGES

        // ================ PRODUCT IMPORT / EXPORT ==================
        Route::post('/seller/products/import', [ProductImportExportController::class, 'import']);
        Route::get('/seller/products/export', [ProductImportExportController::class, 'export']);
    });
//  ---------------------- -----------------------

//  ---------------- ADMIN + SUPER ADMIN ROUTES ---------------------------------------------------------------------------
    Route::middleware(['auth:sanctum','role:seller,super_admin'])->group(function () {
        
        // PRODUCT IMPORT/EXPORT
        Route::post('/admin/products/import', [ProductImportExportController::class, 'import']); // IMPORT PRODUCTS
        Route::get('/admin/products/export', [ProductImportExportController::class, 'export']); // EXPORT PRODUCTS

    });
//  ---------------------- ---------------------------------------------------------------- -------------------------------


//  ---------------- SUPER ADMIN ONLY ROUTES ------------------------------------------------------------------------------
    Route::middleware(['auth:sanctum','role:super_admin'])->group(function () {

        // ---------------- SUPER ADMIN PROFILE ----------------
        Route::get('/superadmin/me', [SuperAdminController::class, 'me']); // ADMIN DETAILS
        Route::post('/superadmin/logout', [SuperAdminController::class, 'logout']); // ADMIN LOGOUT
        
        // ---------------- PRODUCTS ----------------
        Route::get('/superadmin/products', [SuperAdminProductController::class, 'index']); // VIEW PRODUCTS
        Route::get('/superadmin/products/{id}', [SuperAdminProductController::class, 'show']); // VIEW SINGLE PRODUCT
        Route::post('/superadmin/products', [SuperAdminProductController::class, 'store']); // CREATE PRODUCT
        Route::put('/superadmin/products/{id}', [SuperAdminProductController::class, 'update']); // UPDATE PRODUCT
        Route::delete('/superadmin/products/{id}', [SuperAdminProductController::class, 'destroy']); // DELETE PRODUCT

        // ---------------- PRODUCT IMAGES ----------------
        Route::post('/superadmin/products/{id}/images', [SuperAdminProductController::class, 'uploadImages']); // UPLOAD PRODUCT IMAGES
        Route::delete('/superadmin/products/images/{image}', [SuperAdminProductController::class, 'destroyImage']);
        Route::put('/superadmin/products/images/{image}/primary', [SuperAdminProductController::class, 'setPrimaryImage']);
        Route::put('/superadmin/products/images/{image}/order', [SuperAdminProductController::class, 'updateImageOrder']);

        // ---------------- VARIANTS ----------------
        Route::post('/superadmin/products/{product}/variants', [SuperAdminProductController::class, 'storeVariant']);
        Route::put('/superadmin/products/{product}/variants/{variant}', [SuperAdminProductController::class, 'updateVariant']);
        Route::delete('/superadmin/products/{product}/variants/{variant}', [SuperAdminProductController::class, 'destroyVariant']);

        // ---------------- VARIANT IMAGES ----------------
        Route::post('/superadmin/products/{product}/variants/{variant}/images', [SuperAdminProductController::class, 'uploadVariantImages']);

        // ---------------- SELLER MANAGEMENT ----------------
        Route::get('/superadmin/seller-requests', [SuperAdminSellerController::class, 'sellerRequests']); // GET ALL PENDING ADMIN SIGNUP REQUESTS
        Route::get('/superadmin/sellers', [SuperAdminSellerController::class, 'sellers']);
        Route::post('/superadmin/seller-request/{user}/approve', [SuperAdminSellerController::class, 'approveSeller']);
        Route::post('/superadmin/seller-request/{user}/reject', [SuperAdminSellerController::class, 'rejectSeller']);
        Route::post('/superadmin/sellers/{user}/verification/approve', [SuperAdminSellerController::class, 'approveVerification']);
        Route::post('/superadmin/sellers/{user}/verification/reject', [SuperAdminSellerController::class, 'rejectVerification']);

        // GET ALL SELLERS (PENDING + APPROVED + REJECTED)
        Route::delete('/superadmin/sellers/{id}', [SuperAdminSellerController::class, 'deleteSeller']); // DELETE SELLER ACCOUNT

        // USER MANAGEMENT
        Route::get('/superadmin/users', [SuperAdminController::class, 'users']); // GET ALL USERS
        Route::post('/superadmin/user/block/{id}', [SuperAdminController::class, 'blockUser']); // BLOCK USER
        Route::post('/superadmin/user/unblock/{id}', [SuperAdminController::class, 'unblockUser']); // UNBLOCK USER
        Route::delete('/superadmin/users/{id}', [SuperAdminController::class, 'deleteUser']); // DELETE USER
        Route::get('/superadmin/users/{id}/orders', [SuperAdminController::class, 'userOrders']); // GET ALL ORDERS OF A USER
        
        // BRANDS
        Route::post('/superadmin/brands', [BrandController::class, 'store']);
        Route::put('/superadmin/brands/{id}', [BrandController::class, 'update']);
        Route::delete('/superadmin/brands/{id}', [BrandController::class, 'destroy']);
        Route::put('/superadmin/brands/{id}/categories', [BrandController::class, 'updateCategories']);

        // CATEGORIES
        Route::post('/superadmin/categories', [CategoryController::class, 'store']);
        Route::put('/superadmin/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/superadmin/categories/{id}', [CategoryController::class, 'destroy']);

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

        //PAYMENT
        Route::post('/superadmin/orders/{id}/verify-bank-transfer', [PaymentController::class, 'verifyBankTransfer']);

        // ORDERS OVERVIEW
        Route::get('/superadmin/orders', [SuperAdminOrderController::class, 'orders']); // VIEW ALL ORDERS
        Route::get('/superadmin/orders/{id}', [SuperAdminOrderController::class, 'show']); // VIEW SINGLE ORDER
        Route::put('/superadmin/orders/{id}', [SuperAdminOrderController::class, 'update']); // UPDATE ORDER
        Route::patch('/superadmin/orders/{id}/status', [SuperAdminOrderController::class, 'updateStatus']);
        Route::delete('/superadmin/orders/{id}', [SuperAdminOrderController::class, 'destroy']); // DELETE ORDER
        Route::get('/superadmin/orders', [SuperAdminOrderController::class, 'orders']);

        // REVIEWS
        Route::get('/superadmin/reviews', [ReviewController::class, 'adminIndex']); // GET ALL REVIEWS
        Route::get('/superadmin/reviews/{reviewId}', [ReviewController::class, 'adminShow']); // GET SINGLE REVIEW
        Route::put('/superadmin/reviews/{reviewId}', [ReviewController::class, 'adminUpdate']); // EDIT REVIEW
        Route::patch('/superadmin/reviews/{reviewId}/approve', [ReviewController::class, 'approve']); // APPROVE REVIEW
        Route::patch('/superadmin/reviews/{reviewId}/reject', [ReviewController::class, 'reject']); // REJECT REVIEW
        Route::delete('/superadmin/reviews/{reviewId}', [ReviewController::class, 'adminDestroy']); // DELETE REVIEW

         // =================== ANALYTICS ===================
        Route::get('/superadmin/analytics', [AnalyticsController::class, 'index']); // GET ANALYTICS DATA
    });
//  ---------------------- ---------------------------------------------------------------- -------------------------------