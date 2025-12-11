# FullOfShip Local Development Setup

## Quick Start

### 1. Start Docker Environment
```bash
cd ~/projects/fullofship
docker-compose up -d
```

This will start:
- **WordPress**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

### 2. Initial WordPress Setup
1. Open http://localhost:8080
2. Select language: English
3. Fill in site info:
   - Site Title: "FullOfShip Dev"
   - Username: admin
   - Password: admin (or choose your own)
   - Email: your-email@example.com
4. Click "Install WordPress"

### 3. Install Required Plugins

#### Install WooCommerce
```bash
# Open WordPress admin at http://localhost:8080/wp-admin
# Go to Plugins > Add New
# Search for "WooCommerce"
# Click "Install Now" then "Activate"
# Follow the WooCommerce setup wizard (can skip most steps for testing)
```

#### Install Dokan
```bash
# In WordPress admin, go to Plugins > Add New
# Search for "Dokan"
# Install "Dokan - WooCommerce Multivendor Marketplace"
# Click "Activate"
# Follow Dokan setup wizard
```

**OR use WP-CLI (faster):**
```bash
# Install WooCommerce
docker exec fullofship-wordpress wp plugin install woocommerce --activate --allow-root

# Install Dokan
docker exec fullofship-wordpress wp plugin install dokan-lite --activate --allow-root

# Activate FullOfShip
docker exec fullofship-wordpress wp plugin activate fullofship --allow-root
```

### 4. Configure WooCommerce
1. Go to WooCommerce > Settings
2. **General Tab**:
   - Address: 123 Main St, New York, NY 10001, US
   - Currency: USD
3. **Shipping Tab**:
   - Add a Shipping Zone (e.g., "United States")
   - Add FullOfShip as shipping method
4. Save changes

### 5. Create Test Vendor
```bash
# Create vendor user
docker exec fullofship-wordpress wp user create vendor1 vendor1@test.com \
  --role=seller \
  --user_pass=vendor123 \
  --display_name="Test Vendor" \
  --allow-root
```

**OR manually:**
1. Go to Users > Add New
2. Username: vendor1
3. Email: vendor1@test.com
4. Role: Seller (Dokan role)
5. Password: vendor123

### 6. Configure FullOfShip Settings
1. Go to WooCommerce > Settings > FullOfShip
2. **General Tab**:
   - Check "Require Box Configuration"
   - Fallback Rate: $10.00
3. **Carriers Tab**:
   - Enable "UPS" (toggle on, no API keys needed for now)
4. **Advanced Tab**:
   - Enable "Debug Mode"
   - Cache Duration: 30 minutes
5. Save changes

### 7. Test as Vendor

#### Set up Shipping Boxes
1. Log out and log in as vendor1 / vendor123
2. Go to Vendor Dashboard > Shipping Boxes
3. Click "Add New Box"
4. Create a box:
   - Name: Small Box
   - Dimensions: 12 × 8 × 6 in
   - Max Weight: 10 lbs
5. Click "Save Box"

#### Create Test Product
1. In Vendor Dashboard, go to Products > Add New
2. Fill in:
   - Title: "Test Product"
   - Price: $25.00
   - Weight: 2 lbs (in Product Data > Shipping)
3. Scroll to "Shipping Boxes" section
4. Check "Small Box"
5. Publish product

### 8. Test Checkout Flow
1. Log out (or use incognito)
2. Add test product to cart
3. Go to Cart > Proceed to Checkout
4. Fill in shipping address
5. You should see shipping rate calculated:
   - "Shipping from Test Vendor (Standard): $7.00"
   - (2 lbs × $1 + $5 base = $7.00)

### 9. View Debug Logs
1. Log in as admin
2. Go to WooCommerce > Status > Logs
3. Select latest `fullofship-` log file
4. You'll see:
   - Package splitting details
   - Box packing calculations
   - Rate calculations

---

## Useful Commands

### Stop Docker Environment
```bash
docker-compose down
```

### Restart (keeps data)
```bash
docker-compose restart
```

### Reset Everything (WARNING: deletes all data)
```bash
docker-compose down -v
docker-compose up -d
```

### View WordPress Logs
```bash
docker-compose logs -f wordpress
```

### Access WordPress Container
```bash
docker exec -it fullofship-wordpress bash
```

### Database Access (phpMyAdmin)
- URL: http://localhost:8081
- Username: wordpress
- Password: wordpress

---

## Multi-Vendor Testing

### Create Second Vendor
```bash
docker exec fullofship-wordpress wp user create vendor2 vendor2@test.com \
  --role=seller \
  --user_pass=vendor123 \
  --display_name="Second Vendor" \
  --allow-root
```

### Test Split Cart
1. Create products from both vendors
2. Add both to cart
3. At checkout, you should see TWO separate shipping packages
4. Each vendor's products ship separately

---

## Troubleshooting

### FullOfShip Not Showing in Plugins
```bash
# Check if mounted correctly
docker exec fullofship-wordpress ls -la /var/www/html/wp-content/plugins/fullofship

# Reinstall composer dependencies
cd ~/projects/fullofship
composer install --no-dev
```

### Database Tables Not Created
```bash
# Deactivate and reactivate plugin
docker exec fullofship-wordpress wp plugin deactivate fullofship --allow-root
docker exec fullofship-wordpress wp plugin activate fullofship --allow-root
```

### Can't See Debug Logs
1. Ensure Debug Mode is enabled in FullOfShip > Advanced
2. Check WooCommerce > Status > Logs
3. Perform a test checkout to generate logs

---

## What Works vs. What Doesn't

### ✅ Working Features
- Vendor box management (create/edit/delete boxes)
- Product-box assignments
- Multi-vendor cart splitting
- Box packing algorithm
- Placeholder shipping rates
- Debug logging

### ⏳ Not Yet Implemented
- Real carrier API rates (UPS, FedEx, USPS, DHL)
- Shipping label generation
- Rate caching (structure exists, not active)

---

## Port Reference
- WordPress: http://localhost:8080
- WordPress Admin: http://localhost:8080/wp-admin
- phpMyAdmin: http://localhost:8081

## Default Credentials
- **WordPress Admin**: admin / admin
- **Vendor 1**: vendor1 / vendor123
- **Database**: wordpress / wordpress
