# Setup Instructions for Northern Dry Fruits Store

## 🔧 **Step 1: Fix Login Issues**

1. **Import the database** (if not already done):
   - Open phpMyAdmin
   - Create database: `dry_fruits_store`
   - Import `database.sql`

2. **Fix password hashes** (IMPORTANT):
   - Open your browser
   - Go to: `http://localhost/curser_store/fix_passwords.php`
   - This will update the password hashes
   - Delete the `fix_passwords.php` file after running it

## 👤 **Login Credentials**

### **Admin Accounts:**
```
Username: admin
Password: admin123
Email: admin@dryfruits.com

Username: admin2  
Password: admin123
Email: admin2@dryfruits.com
```

### **Customer Account:**
```
Username: customer
Password: customer123
Email: customer@example.com
```

## 🎯 **How to Test the System**

### **1. Test Customer Login:**
1. Go to `http://localhost/curser_store`
2. Click "Login" in navbar
3. Select "Customer" radio button
4. Enter: `customer` / `customer123`
5. Should redirect to home page

### **2. Test Admin Login:**
1. Go to `http://localhost/curser_store`
2. Click "Login" in navbar
3. Select "Admin" radio button
4. Enter: `admin` / `admin123`
5. Should redirect to admin dashboard

### **3. Test Navigation:**
- **Products link** in navbar → Should go to products page
- **Shop Now** button on home → Should go to products page
- **View All Products** button → Should go to products page
- **Details** button on products → Should go to product details page

### **4. Test Cart Functionality:**
1. Login as customer
2. Go to products page
3. Click "Add to Cart" on any product
4. Click cart icon in navbar
5. Should show cart with products

### **5. Test Admin Features:**
1. Login as admin
2. Access admin dashboard
3. Go to "Products" to manage products
4. Go to "Categories" to manage categories

## 🛠️ **Troubleshooting**

### **If login still doesn't work:**
1. Check if database connection is working
2. Verify `config/database.php` has correct credentials
3. Make sure you ran `fix_passwords.php`

### **If buttons don't work:**
1. Check if all files are in the correct location
2. Verify file permissions
3. Check browser console for JavaScript errors

### **If cart doesn't work:**
1. Make sure you're logged in
2. Check if `ajax/add_to_cart.php` exists
3. Verify database tables are created

## 📁 **File Structure Check**

Make sure these files exist:
```
curser_store/
├── config/database.php
├── ajax/add_to_cart.php
├── admin/dashboard.php
├── admin/products.php
├── admin/categories.php
├── index.php
├── products.php
├── product_details.php
├── cart.php
├── checkout.php
├── cashout.php
├── login.php
├── register.php
├── about.php
├── logout.php
└── database.sql
```

## 🎉 **Success Indicators**

✅ Login works for both customer and admin  
✅ Navigation links work properly  
✅ Products page shows all products  
✅ Add to Cart functionality works  
✅ Cart page displays items correctly  
✅ Admin panel is accessible  
✅ Product details page works  
✅ All buttons redirect correctly  

## 🚀 **Next Steps**

1. **Test all functionality** using the credentials above
2. **Add real products** through admin panel
3. **Customize the design** as needed
4. **Add more features** like payment gateway

---

**Need Help?** Check the browser console for errors and ensure all files are in the correct location. 