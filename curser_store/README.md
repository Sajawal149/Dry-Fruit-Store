# Northern Dry Fruits Store

A complete e-commerce web application for selling premium dry fruits from Northern Pakistan. Built with PHP, MySQL, and Bootstrap.

## Features

### Customer Features
- **Home Page**: Featured products showcase with modern design
- **Product Catalog**: Browse all products with filtering and search
- **Shopping Cart**: Add, remove, and update quantities
- **User Registration/Login**: Secure authentication system
- **Checkout Process**: Complete order placement with shipping details
- **Order Confirmation**: Detailed order summary and confirmation
- **About Page**: Information about the store and products

### Admin Features
- **Admin Dashboard**: Statistics and overview
- **Product Management**: Add, edit, and delete products
- **Order Management**: View and manage customer orders
- **Customer Management**: View customer information
- **Category Management**: Organize products by categories

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.1.3
- **Icons**: Font Awesome 6.0.0

## Installation

### Prerequisites
- XAMPP, WAMP, or similar local server environment
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Setup Instructions

1. **Clone/Download the Project**
   ```bash
   # Place the project in your web server directory
   # For XAMPP: C:\xampp\htdocs\curser_store
   # For WAMP: C:\wamp64\www\curser_store
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `dry_fruits_store`
   - Import the `database.sql` file to create tables and sample data

3. **Configuration**
   - Open `config/database.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'dry_fruits_store');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Access the Application**
   - Start your web server (Apache)
   - Open browser and navigate to: `http://localhost/curser_store`

## Default Admin Account

- **Username**: admin
- **Password**: password
- **Email**: admin@dryfruits.com

## File Structure

```
curser_store/
├── config/
│   └── database.php          # Database configuration
├── admin/
│   ├── dashboard.php         # Admin dashboard
│   └── products.php          # Product management
├── ajax/
│   └── add_to_cart.php      # AJAX cart handler
├── index.php                 # Home page
├── products.php              # Product catalog
├── cart.php                  # Shopping cart
├── checkout.php              # Checkout process
├── order_confirmation.php    # Order confirmation
├── login.php                 # User login
├── register.php              # User registration
├── about.php                 # About page
├── logout.php                # Logout functionality
├── database.sql              # Database schema
└── README.md                 # This file
```

## Key Features Explained

### Shopping Cart System
- Add products to cart with quantity selection
- Update quantities in cart
- Remove items from cart
- Real-time cart count display

### Admin Panel
- **Dashboard**: Overview with statistics
- **Products**: Full CRUD operations for products
- **Orders**: View and manage customer orders
- **Customers**: View customer information

### Security Features
- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Input validation and sanitization

### Responsive Design
- Mobile-friendly interface
- Bootstrap 5 responsive grid system
- Modern UI with gradients and animations

## Database Schema

### Main Tables
- **users**: Customer and admin accounts
- **products**: Product information
- **categories**: Product categories
- **cart**: Shopping cart items
- **orders**: Customer orders
- **order_items**: Order line items

## Customization

### Adding New Products
1. Login as admin
2. Go to Admin Panel > Products
3. Click "Add Product"
4. Fill in product details
5. Save the product

### Modifying Styles
- Edit CSS in individual PHP files
- Bootstrap classes for responsive design
- Custom gradients and animations

### Database Modifications
- Add new fields to existing tables
- Create new tables for additional features
- Update queries in PHP files accordingly

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database name exists

2. **Page Not Found (404)**
   - Check file paths and names
   - Ensure web server is running
   - Verify .htaccess configuration if using

3. **Cart Not Working**
   - Check if user is logged in
   - Verify AJAX file permissions
   - Check browser console for JavaScript errors

4. **Admin Panel Access**
   - Ensure user role is set to 'admin'
   - Check session variables
   - Verify login credentials

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## License

This project is created for educational and commercial purposes.

## Support

For support or questions, please contact:
- Email: info@northerndryfruits.com
- Phone: +92-300-1234567

## Future Enhancements

- Payment gateway integration
- Email notifications
- Product reviews and ratings
- Advanced search filters
- Inventory management
- Sales reports and analytics
- Mobile app development 