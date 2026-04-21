# System Build Documentation

This document explains how the Maramag Fish Landing Point-of-Sale and Inventory Management System was built based on the actual implementation in this repository.

You may use this as supporting documentation for the methodology chapter, system documentation, or defense preparation.

## 1. Development Environment Setup

The system was built as a Laravel-based web application. The development environment was prepared using the following tools:

- XAMPP for Apache and MySQL
- PHP 8.2 or higher
- Composer for PHP package management
- Node.js and NPM for front-end package management
- Laravel framework for application structure
- MySQL for the relational database

The project environment was configured through the `.env` file. Database connection details such as host, port, database name, username, and password were defined in the environment file. After configuration, the Laravel application key was generated and the project dependencies were installed.

## 2. Database Construction

After setting up the development environment, the database structure was created using Laravel migrations. Instead of manually building tables inside phpMyAdmin, the database schema was documented and implemented through migration files so that the structure could be recreated consistently.

The following major tables were built:

- `users`
- `roles`
- `user_roles`
- `employees`
- `brokers`
- `buyers`
- `fish_types`
- `broker_fish_type`
- `fish_prices`
- `fish_boxes`
- `fish_box_purchases`
- `fish_inventory`
- `sales`
- `sales_details`
- `payments`

The use of migrations made it possible to manage schema updates in an organized way as the system evolved.

## 3. User Access and Security Implementation

Authentication was implemented using Laravel's built-in authentication structure. User login was required before access to the system was granted. After authentication, role-based middleware was used to separate access between broker users and administrative users.

The role structure was implemented through the `roles` and `user_roles` tables. Broker-specific data was stored in the `brokers` table, while administrative profile data was stored in the `employees` table. Custom middleware was used so that:

- broker users could access broker modules
- admin and staff users could access administrative modules

Additional protection was provided through request validation, authenticated route groups, and CSRF protection.

## 4. Back-End Development

The back-end logic of the system was implemented using Laravel controllers, models, form request validation, constants, and repositories.

### Models

Eloquent models were created to represent the main entities of the system, such as:

- User
- Broker
- Employee
- Buyer
- FishType
- BrokerFishType
- FishPrice
- FishBox
- FishBoxPurchase
- InventoryLog
- Sales
- SalesDetails
- SalesPayment

Relationships were then defined between these models so that the system could connect brokers to sales, sales to buyers, fish boxes to fish types, and payments to transactions.

### Controllers

Controllers were created to handle the main modules of the application, including:

- broker dashboard
- admin dashboard
- user management
- fish type management
- fish price management
- fish box management
- sales management
- payment processing
- fish box tracking

These controllers handled request processing, validation, data retrieval, and redirection of users to the correct views.

### Validation

Form request classes were used to validate user inputs before saving data. Validation rules were applied to important forms such as:

- user creation and update
- fish type input
- fish price input
- fish box input
- sales transaction input
- payment input

This reduced invalid data entry and helped maintain data consistency.

## 5. Inventory and Fish Box Logic

The system was designed to manage reusable fish boxes rather than simple one-time inventory items. For this reason, a fish box was treated as a physical reusable unit with its own QR code and status.

The `fish_boxes` table stores the reusable fish box record, while the `fish_box_purchases` table stores the current stocking cycle of the box, including its fish type and cost price. This design made it possible to preserve the identity of the physical fish box while still recording updated fish content per cycle.

Fish box statuses were implemented using defined constants:

- `In Stock`
- `Sold`
- `Returned`
- `Missing`

Whenever a fish box status changed, the movement was recorded in the `fish_inventory` table so that the system could keep a history of fish box activity.

## 6. Sales and Payment Module Construction

The sales module was developed so that broker users could encode transactions in a structured manner. Each sale record stores the sales date, buyer reference, broker reference, total amount, and payment status.

The sales process was implemented as follows:

1. The broker selects or scans fish boxes.
2. The system retrieves the corresponding fish type and box information.
3. Buyer details are encoded.
4. The sale is saved in the `sales` table.
5. The itemized fish box records are saved in the `sales_details` table.
6. The status of the selected fish boxes is updated from `In Stock` to `Sold`.
7. Fish box movement is logged in the inventory tracking table.

The payment module was then built to support partial and full payments. Payment records are saved in the `payments` table. The current balance of a sale is computed using the total amount minus the sum of all related payments. This makes it possible to monitor unpaid and partially paid transactions.

## 7. QR Code Integration

QR code functionality was added to support faster and more accurate fish box identification. Each fish box is assigned a unique QR code during creation.

Two major QR-related functions were implemented:

- retrieving a fish box during sales entry
- returning a fish box through QR scanning

The browser camera is used during QR scanning. When a QR code is read, the system checks the corresponding fish box record and validates whether it belongs to the current broker and whether the requested action is allowed.

If valid, the system either:

- adds the fish box to the sales process, or
- updates its status to `Returned`

This reduced the need for manual fish box lookup and improved operational speed.

## 8. Front-End Development

The user interface was built using Laravel Blade templates, Tailwind CSS, HTML, CSS, Alpine.js, and JavaScript.

Blade templates were used to structure the layout and reusable partials such as:

- navigation bars
- sidebars
- dashboard panels
- modal forms
- report views

Tailwind CSS was used as the main styling framework for responsive layout and interface consistency. Alpine.js was used for lightweight interactivity, such as:

- filter state handling
- sidebar toggling
- modal behavior
- simple reactive form features

Additional front-end packages were used for:

- alerts and confirmations through SweetAlert2
- notifications through Toastr
- QR scanning through QR scanner libraries
- asset compilation through Laravel Mix

## 9. Reporting and Dashboard Development

The dashboard and analytics features were developed to provide summarized operational information to both broker and administrative users.

For brokers, the dashboard includes:

- daily orders
- total sales
- outstanding balance
- recent sales
- graphs for sales trends
- top items sold

For administrative users, the dashboard includes:

- total brokers
- total fish boxes sold
- missing fish boxes
- returned fish boxes
- top brokers
- top fish types sold
- broker-based sales analysis
- fish box tracking history

These outputs were built by combining controller logic, repository queries, and Blade views.

## 10. Asset Compilation

Front-end assets were compiled using Laravel Mix. JavaScript and CSS source files under the `resources` directory were processed and exported to the `public` directory.

The build process included:

- compiling `resources/js/app.js`
- compiling module-specific JavaScript files
- compiling CSS files from the `resources/css` directory
- copying additional asset files needed by the interface

This allowed the system to deliver browser-ready front-end files during testing and use.

## 11. Testing Process

After implementation, the system underwent multiple levels of testing:

- unit testing for individual functions and modules
- integration testing for connected modules
- system testing for full workflow validation
- User Acceptance Testing for actual user evaluation

The testing process was used to verify that the modules worked as intended and that the complete system responded correctly to user inputs and operational scenarios.

## 12. Deployment Preparation

Before deployment, the following were prepared:

- configured environment file
- database schema through migrations
- compiled front-end assets
- user accounts for testing and evaluation

This ensured that the application could run in an operational environment with the required dependencies and database structure in place.

## 13. Summary of the Build Process

In summary, the system was built through the following major steps:

1. Set up the Laravel development environment using XAMPP, Composer, NPM, and MySQL.
2. Configure the application environment and database connection.
3. Create the database schema using Laravel migrations.
4. Build models, relationships, and validation rules.
5. Implement authentication, user roles, and middleware.
6. Develop broker and administrative modules.
7. Integrate QR code generation and QR-based fish box processing.
8. Build the user interface using Blade, Tailwind CSS, Alpine.js, and JavaScript.
9. Compile front-end assets with Laravel Mix.
10. Test the application through unit, integration, system, and user acceptance testing.

This development process resulted in a functional web-based Point-of-Sale and Inventory Management System tailored to the workflow of Maramag Fish Landing.
