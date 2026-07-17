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

