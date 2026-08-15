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

I replaced all
http://localhost:5173
with
https://aestra-frontend.vercel.app


ORIGINAL .env for localhost
APP_NAME=AESTRA
APP_ENV=local
APP_KEY=base64:BE40/YjeN1RavKT8J2s2n44dTEWW2YYdagKzpZB1cd4=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000


APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aestra
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

PAYSTACK_PUBLIC_KEY=pk_test_5ec997afcca30c871a77727900ce61847912b51f
PAYSTACK_SECRET_KEY=sk_test_ae2c7ea97898c198041f6de6f0d924fcb5c97a10
PAYSTACK_PAYMENT_URL=https://api.paystack.co

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
