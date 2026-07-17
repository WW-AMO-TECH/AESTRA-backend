* Add OTP verification later.
* Test the notify-customer button end-to-end: place a pickup order as a customer, then as admin open that order and click 'Notify customer — ready for pickup' and confirm the toast and status change.
* Remember the product has name, description, brand, category, price, original price, discount, flash deal,  stock, warranty, grade, model, condition, techniacl specs( Ram, Battery, Camera, Cpu, Gpu, Display, Storage, Os, Connectivity ) and Image


🟢 1. CATEGORY & BRAND SYSTEM (NEXT)
Needed for:
product creation
filtering
homepage sections
Build:
CategoryController (CRUD)
BrandController (CRUD)
Public fetch endpoints
🟢 2. CART SYSTEM
Core ecommerce feature
Build:
add to cart
update quantity
remove item
get cart
Tables already exist:

✔ cart
✔ cart_items

🟢 3. CHECKOUT & ORDER SYSTEM
This is where money flow starts
Build:
create order from cart
calculate totals
choose:
delivery OR pickup
store address / pickup location
simulate payment (for now)
🟢 4. ORDER MANAGEMENT (ADMIN)
Build:
view all orders
update status:
pending → confirmed → shipped → delivered
cancel / decline order
🟢 5. ORDER TRACKING SYSTEM
You already created model
Build:
timeline updates
tracking history
status updates
🟢 6. REVIEW SYSTEM (FULL)

You partially did this.

Complete it:
link review to product + user
rating system (1–5)
fetch product reviews
prevent duplicate reviews per order
🟢 7. WISHLIST SYSTEM
Build:
add/remove wishlist
fetch wishlist
link to user + product
🟢 8. SEARCH + FILTER (UPGRADE PRODUCTS)

You skipped earlier — now bring it back:

search by name
filter:
category
brand
condition
price range
sort:
newest
price
popularity
🟢 9. USER DASHBOARD APIs
Build:
get user profile
update profile
order history
order details
download receipt (optional)
🟢 10. ADMIN DASHBOARD APIs
Build:
stats:
total users
total orders
revenue
recent orders
recent users
🟢 11. BLOG SYSTEM
Build:
BlogController
BlogCategoryController
CRUD (admin)
public listing
🟢 12. CONTACT SYSTEM
Build:
store contact messages
admin can view messages
🟢 13. NOTIFICATIONS (OPTIONAL BUT POWERFUL)
order updates
admin alerts
email (later)
🟢 14. SECURITY & POLISH (VERY IMPORTANT)
validation consistency
prevent unauthorized access in controllers
sanitize inputs
rate limiting (later)
🟢 15. PERFORMANCE (FINAL STAGE)
pagination
caching
query optimization



Dockerfile
.dockerignore

Using ngrok for temporary hosting
Run in bash
winget install Ngrok.Ngrok
Log in to your ngrok account
Copy your authentication token from the dashboard.
Run in bash
ngrok config add-authtoken YOUR_AUTH_TOKEN

Hosting frontend
npm run dev
It usually runs on: http://localhost:5173
Now expose it by running this in frontend terminal:
ngrok http 5173

Hosting backend
php artisan serve
Usually runs on: http://127.0.0.1:8000
Now expose it by running this in backend terminal
ngrok http 8000
You'll get
https://excess-macaw-sassy.ngrok-free.dev



I changed this
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

to this
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->api(prepend: [
            HandleCors::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();








    CORS FORMER 
    <?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // 'paths' => ['api/*', 'products', 'categories', 'brands', 'sanctum/csrf-cookie'],
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://aestra-frontend.vercel.app',
        // 'http://localhost:5173',
        // // 'http://127.0.0.1:5173',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];



APP.JS FORMER
import './bootstrap';

app.use(cors({
  origin: 'https://aestra-frontend.vercel.app'
}));







































6. config/session.php 



<?php



use Illuminate\Support\Str;



return [



    /*

    |--------------------------------------------------------------------------

    | Default Session Driver

    |--------------------------------------------------------------------------

    |

    | This option determines the default session driver that is utilized for

    | incoming requests. Laravel supports a variety of storage options to

    | persist session data. Database storage is a great default choice.

    |

    | Supported: "file", "cookie", "database", "memcached",

    |            "redis", "dynamodb", "array"

    |

    */



    'driver' => env('SESSION_DRIVER', 'database'),



    /*

    |--------------------------------------------------------------------------

    | Session Lifetime

    |--------------------------------------------------------------------------

    |

    | Here you may specify the number of minutes that you wish the session

    | to be allowed to remain idle before it expires. If you want them

    | to expire immediately when the browser is closed then you may

    | indicate that via the expire_on_close configuration option.

    |

    */



    'lifetime' => (int) env('SESSION_LIFETIME', 120),



    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),



    /*

    |--------------------------------------------------------------------------

    | Session Encryption

    |--------------------------------------------------------------------------

    |

    | This option allows you to easily specify that all of your session data

    | should be encrypted before it's stored. All encryption is performed

    | automatically by Laravel and you may use the session like normal.

    |

    */



    'encrypt' => env('SESSION_ENCRYPT', false),



    /*

    |--------------------------------------------------------------------------

    | Session File Location

    |--------------------------------------------------------------------------

    |

    | When utilizing the "file" session driver, the session files are placed

    | on disk. The default storage location is defined here; however, you

    | are free to provide another location where they should be stored.

    |

    */



    'files' => storage_path('framework/sessions'),



    /*

    |--------------------------------------------------------------------------

    | Session Database Connection

    |--------------------------------------------------------------------------

    |

    | When using the "database" or "redis" session drivers, you may specify a

    | connection that should be used to manage these sessions. This should

    | correspond to a connection in your database configuration options.

    |

    */



    'connection' => env('SESSION_CONNECTION'),



    /*

    |--------------------------------------------------------------------------

    | Session Database Table

    |--------------------------------------------------------------------------

    |

    | When using the "database" session driver, you may specify the table to

    | be used to store sessions. Of course, a sensible default is defined

    | for you; however, you're welcome to change this to another table.

    |

    */



    'table' => env('SESSION_TABLE', 'sessions'),



    /*

    |--------------------------------------------------------------------------

    | Session Cache Store

    |--------------------------------------------------------------------------

    |

    | When using one of the framework's cache driven session backends, you may

    | define the cache store which should be used to store the session data

    | between requests. This must match one of your defined cache stores.

    |

    | Affects: "dynamodb", "memcached", "redis"

    |

    */



    'store' => env('SESSION_STORE'),



    /*

    |--------------------------------------------------------------------------

    | Session Sweeping Lottery

    |--------------------------------------------------------------------------

    |

    | Some session drivers must manually sweep their storage location to get

    | rid of old sessions from storage. Here are the chances that it will

    | happen on a given request. By default, the odds are 2 out of 100.

    |

    */



    'lottery' => [2, 100],



    /*

    |--------------------------------------------------------------------------

    | Session Cookie Name

    |--------------------------------------------------------------------------

    |

    | Here you may change the name of the session cookie that is created by

    | the framework. Typically, you should not need to change this value

    | since doing so does not grant a meaningful security improvement.

    |

    */



    'cookie' => env(

        'SESSION_COOKIE',

        Str::slug((string) env('APP_NAME', 'laravel')).'-session'

    ),



    /*

    |--------------------------------------------------------------------------

    | Session Cookie Path

    |--------------------------------------------------------------------------

    |

    | The session cookie path determines the path for which the cookie will

    | be regarded as available. Typically, this will be the root path of

    | your application, but you're free to change this when necessary.

    |

    */



    'path' => env('SESSION_PATH', '/'),



    /*

    |--------------------------------------------------------------------------

    | Session Cookie Domain

    |--------------------------------------------------------------------------

    |

    | This value determines the domain and subdomains the session cookie is

    | available to. By default, the cookie will be available to the root

    | domain without subdomains. Typically, this shouldn't be changed.

    |

    */



    'domain' => env('SESSION_DOMAIN'),



    /*

    |--------------------------------------------------------------------------

    | HTTPS Only Cookies

    |--------------------------------------------------------------------------

    |

    | By setting this option to true, session cookies will only be sent back

    | to the server if the browser has a HTTPS connection. This will keep

    | the cookie from being sent to you when it can't be done securely.

    |

    */



    'secure' => env('SESSION_SECURE_COOKIE'),



    /*

    |--------------------------------------------------------------------------

    | HTTP Access Only

    |--------------------------------------------------------------------------

    |

    | Setting this value to true will prevent JavaScript from accessing the

    | value of the cookie and the cookie will only be accessible through

    | the HTTP protocol. It's unlikely you should disable this option.

    |

    */



    'http_only' => env('SESSION_HTTP_ONLY', true),



    /*

    |--------------------------------------------------------------------------

    | Same-Site Cookies

    |--------------------------------------------------------------------------

    |

    | This option determines how your cookies behave when cross-site requests

    | take place, and can be used to mitigate CSRF attacks. By default, we

    | will set this value to "lax" to permit secure cross-site requests.

    |

    | See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value

    |

    | Supported: "lax", "strict", "none", null

    |

    */



    'same_site' => env('SESSION_SAME_SITE', 'lax'),



    /*

    |--------------------------------------------------------------------------

    | Partitioned Cookies

    |--------------------------------------------------------------------------

    |

    | Setting this value to true will tie the cookie to the top-level site for

    | a cross-site context. Partitioned cookies are accepted by the browser

    | when flagged "secure" and the Same-Site attribute is set to "none".

    |

    */



    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),



];



7. Your Axios configuration (actually src/api/axios.ts) 

import axios from "axios"; 



/*

|--------------------------------------------------

| AXIOS INSTANCE

|--------------------------------------------------

| This is the single connection point between React and Laravel backend

*/

const api = axios.create({

  // baseURL: "https://aestra-backend-production-426b.up.railway.app/api",

  baseURL: import.meta.env.VITE_API_URL || "https://aestra-backend-production-426b.up.railway.app/api",

  headers: {

    "Content-Type": "application/json",

    Accept: "application/json",

  },

  withCredentials: true

});



/*

|--------------------------------------------------

| REQUEST INTERCEPTOR (ATTACH TOKEN)

|--------------------------------------------------

*/

api.interceptors.request.use((config) => {

  const token = localStorage.getItem("token");



  if (token) {

    config.headers.Authorization = `Bearer ${token}`;

  }



  return config;

});



/*

|--------------------------------------------------

| RESPONSE INTERCEPTOR (HANDLE ERRORS)

|--------------------------------------------------

*/

api.interceptors.response.use(

  (response) => response,

  (error) => {

    const status = error?.response?.status;



    // 🔥 Handle unauthorized (token expired / invalid)

    if (status === 401) {

      localStorage.removeItem("token");



      // ⚠️ DO NOT force redirect blindly

      // Let React Router/AuthContext handle navigation

      if (window.location.pathname !== "/login") {

        window.location.href = "/login";

      }

    }



    // Optional: handle forbidden

    if (status === 403) {

      console.warn("Access forbidden");

    }



    return Promise.reject(error);

  }

);



export default api;



8. package.json (frontend)

{

  "name": "vite_react_shadcn_ts",

  "private": true,

  "version": "0.0.0",

  "type": "module",

  "scripts": {

    "dev": "vite",

    "build": "vite build",

    "build:dev": "vite build --mode development",

    "lint": "eslint .",

    "preview": "vite preview",

    "test": "vitest run",

    "test:watch": "vitest"

  },

  "dependencies": {

    "@hookform/resolvers": "^3.10.0",

    "@radix-ui/react-accordion": "^1.2.11",

    "@radix-ui/react-alert-dialog": "^1.1.14",

    "@radix-ui/react-aspect-ratio": "^1.1.7",

    "@radix-ui/react-avatar": "^1.1.10",

    "@radix-ui/react-checkbox": "^1.3.2",

    "@radix-ui/react-collapsible": "^1.1.11",

    "@radix-ui/react-context-menu": "^2.2.15",

    "@radix-ui/react-dialog": "^1.1.14",

    "@radix-ui/react-dropdown-menu": "^2.1.15",

    "@radix-ui/react-hover-card": "^1.1.14",

    "@radix-ui/react-label": "^2.1.7",

    "@radix-ui/react-menubar": "^1.1.15",

    "@radix-ui/react-navigation-menu": "^1.2.13",

    "@radix-ui/react-popover": "^1.1.14",

    "@radix-ui/react-progress": "^1.1.7",

    "@radix-ui/react-radio-group": "^1.3.7",

    "@radix-ui/react-scroll-area": "^1.2.9",

    "@radix-ui/react-select": "^2.2.5",

    "@radix-ui/react-separator": "^1.1.7",

    "@radix-ui/react-slider": "^1.3.5",

    "@radix-ui/react-slot": "^1.2.3",

    "@radix-ui/react-switch": "^1.2.5",

    "@radix-ui/react-tabs": "^1.1.12",

    "@radix-ui/react-toast": "^1.2.14",

    "@radix-ui/react-toggle": "^1.1.9",

    "@radix-ui/react-toggle-group": "^1.1.10",

    "@radix-ui/react-tooltip": "^1.2.7",

    "@tanstack/react-query": "^5.83.0",

    "axios": "^1.15.0",

    "class-variance-authority": "^0.7.1",

    "clsx": "^2.1.1",

    "cmdk": "^1.1.1",

    "date-fns": "^3.6.0",

    "embla-carousel-autoplay": "^8.6.0",

    "embla-carousel-react": "^8.6.0",

    "framer-motion": "^12.38.0",

    "input-otp": "^1.4.2",

    "lucide-react": "^0.462.0",

    "next-themes": "^0.3.0",

    "react": "^18.3.1",

    "react-day-picker": "^8.10.1",

    "react-dom": "^18.3.1",

    "react-hook-form": "^7.61.1",

    "react-resizable-panels": "^2.1.9",

    "react-router-dom": "^6.30.1",

    "recharts": "^2.15.4",

    "sonner": "^1.7.4",

    "tailwind-merge": "^2.6.0",

    "tailwindcss-animate": "^1.0.7",

    "vaul": "^0.9.9",

    "zod": "^3.25.76"

  },

  "devDependencies": {

    "@eslint/js": "^9.32.0",

    "@tailwindcss/postcss": "^4.2.0",

    "@tailwindcss/typography": "^0.5.16",

    "@testing-library/jest-dom": "^6.6.0",

    "@testing-library/react": "^16.0.0",

    "@types/axios": "^0.9.36",

    "@types/node": "^22.16.5",

    "@types/react": "^18.3.23",

    "@types/react-dom": "^18.3.7",

    "@vitejs/plugin-react": "^5.1.4",

    "@vitejs/plugin-react-swc": "^3.11.0",

    "autoprefixer": "^10.4.21",

    "eslint": "^9.32.0",

    "eslint-plugin-react-hooks": "^5.2.0",

    "eslint-plugin-react-refresh": "^0.4.20",

    "globals": "^15.15.0",

    "jsdom": "^20.0.3",

    "lovable-tagger": "^1.1.13",

    "postcss": "^8.5.6",

    "tailwindcss": "^3.4.17",

    "typescript": "^5.8.3",

    "typescript-eslint": "^8.38.0",

    "vite": "^5.4.19",

    "vitest": "^3.2.4"

  }

}



9. routes/api.php
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












