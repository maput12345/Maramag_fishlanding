# Exact Paper Revisions For Chapter III and Chapter IV

The text below follows the existing format of the paper and is written to match the implemented Maramag Fish Landing system in this repository.

Use this as a ready-to-paste revision for the technical background and methodology chapters.

## CHAPTER III: TECHNICAL BACKGROUND

### 1. Overview of the System

The proposed project is a web-based Point-of-Sale (POS) and Inventory Management System developed for Maramag Fish Landing. The system was designed to address the problems observed in the existing manual process, particularly in sales recording, fish box tracking, payment monitoring, and report preparation. Under the manual setup, brokers and administrative personnel relied on handwritten records and manual checking of transactions, which made the process time-consuming, prone to error, and difficult to monitor consistently.

The developed system provides a centralized digital platform for recording sales transactions, managing fish-related inventory information, monitoring customer payments, and tracking fish boxes through QR codes. It supports faster transaction processing, better record organization, and improved transparency in daily operations. The system is intended for two major user groups: broker users and authorized administrative users under the Local Economic Enterprise Office (LEEO), specifically admin and staff accounts.

For broker users, the system provides modules for fish type management, fish price management, fish box registration, QR-based fish box identification, sales recording, payment entry, receipt printing, and fish box tracking. For administrative users, the system provides centralized monitoring of users, broker activities, sales summaries, and fish box tracking records. Through these modules, the system helps improve efficiency, accuracy, and accountability in Maramag Fish Landing operations.

### 2. Technologies Involved

#### i. Back End

The system was developed using PHP and the Laravel framework. Laravel was selected because it provides a structured and maintainable framework for developing secure web applications. It follows the Model-View-Controller (MVC) architectural pattern, which separates the business logic, presentation layer, and data access layer. This structure made the system easier to organize, maintain, and expand during development.

MySQL was used as the database management system for storing user information, broker profiles, buyer records, fish types, fish prices, fish boxes, sales transactions, payments, and fish box movement logs. Laravel migrations were used to define and manage the database structure, while Eloquent ORM was used to simplify database operations and model relationships.

XAMPP was used as the local development environment to run Apache and MySQL during development and testing. Composer was used to install and manage PHP package dependencies required by the Laravel application. The back-end implementation also used Laravel authentication, validation, middleware, route grouping, and CSRF protection to support secure access and transaction handling.

#### ii. Front End

The user interface of the system was developed using Laravel Blade templates, Tailwind CSS, HTML, CSS, and JavaScript. Blade was used as the server-side templating engine for constructing reusable page layouts and dynamic views. Tailwind CSS was used as the main styling framework for building a responsive and clean user interface suitable for desktop and mobile browser use.

Alpine.js was used in the interface for lightweight reactive behavior, such as modal state handling, form interaction, sidebar control, and filtering components. JavaScript was used to support client-side interaction and dynamic processing. Axios was included for HTTP requests, while SweetAlert2 and Toastr were used for user notifications and confirmation dialogs.

For QR-related functionality, the system used QR code generation and QR scanning libraries to support fish box identification and return processing. Laravel Mix and Node Package Manager (NPM) were used to compile and bundle front-end assets such as JavaScript and CSS files.

### 3. Architecture and Design Concepts

The system follows a client-server architecture in which the client side consists of browser-based user interfaces and the server side processes requests, applies business logic, and stores data in the database. Users interact with the system through a web browser, while the application server processes transactions and retrieves the necessary data from the MySQL database.

The implementation follows the Model-View-Controller (MVC) architecture. The models represent the application data and database relationships, the views provide the user interface, and the controllers handle user requests and system logic. This architectural approach was suitable for the project because it improved code organization and allowed the developers to manage the system in modular form.

The system also uses role-based access control. Broker users are allowed to access modules related to inventory, pricing, sales, payments, and fish box tracking. Administrative users under the LEEO office, specifically admin and staff accounts, are allowed to access account management, centralized monitoring, sales analysis, and fish box tracking views. This design helps protect sensitive information and ensures that each user can only access the features relevant to their role.

### 4. Platforms and Devices Supported

The system is designed as a web-based application and can be accessed using modern web browsers on desktop computers, laptops, tablets, and smartphones. Desktop and laptop devices are mainly intended for encoding, reporting, and administrative monitoring, while smartphones may be used for fish box QR scanning and general access to responsive system views.

The system supports devices with camera functionality for QR code scanning. This allows broker users to scan fish box QR codes during sales processing or return transactions. Because the system is browser-based, it can be used on common platforms such as Windows and Android, provided that the device has an updated browser and a functioning network connection.

### 5. Existing Solutions or Benchmarks

Existing Point-of-Sale and inventory management systems are commonly used in retail and service-based businesses. These systems generally support transaction recording, inventory monitoring, and report generation. However, most available solutions are designed for general retail environments and do not specifically address the operational workflow of a fish landing facility that relies on reusable fish boxes, box-return monitoring, and broker-based transaction handling.

The developed system differs from generic POS applications because it was customized for the operational setting of Maramag Fish Landing. In addition to sales recording, it supports fish type management, fish price management, fish box registration, QR-based identification, partial payment monitoring, fish box return processing, and missing box monitoring. These functions directly support the needs of brokers and LEEO administrative users in the local fish landing environment.

The system was designed as a practical and low-cost web-based solution. Instead of using more complex technologies such as RFID, IoT, or blockchain, the project uses QR code identification and browser-based access to achieve a balance between usability, affordability, and operational effectiveness.

## CHAPTER IV: Methodology

### 1. Conceptual Framework

The conceptual framework of the study presents the interaction between the system users, application components, and database resources in the Point-of-Sale and Inventory Management System for Maramag Fish Landing. The framework consists of broker users, administrative users under the LEEO office, buyer records, fish boxes with QR codes, the web application, and the database.

The process begins when the broker user logs into the system and performs operational tasks such as managing fish types, assigning fish prices, registering fish boxes, recording sales, encoding buyer information, receiving payments, and updating fish box status. Each fish box is assigned a unique QR code that serves as its system identifier. During transactions, QR codes may be scanned to retrieve fish box information or to update the status of a fish box during return processing.

The web application processes the input entered by the users and applies the corresponding business logic. These operations include user validation, sales recording, payment computation, fish box status updating, and report generation. The processed information is then stored in the database. Authorized administrative users can access centralized reports and monitoring dashboards to review sales summaries, user records, and fish box tracking data. The framework shows how the system integrates operational encoding, inventory tracking, and administrative monitoring into one centralized platform.

### 2. Research Approach

This study employed a mixed-method developmental research design. Qualitative methods were used during the requirement gathering stage, while quantitative evaluation was applied after system development through User Acceptance Testing (UAT).

The qualitative component consisted of interviews and direct observation. Interviews were conducted to gather information from intended users regarding the difficulties they encountered in manual sales recording, fish box accountability, and payment monitoring. Direct observation was also used to examine the actual workflow at Maramag Fish Landing, including how sales were recorded, how fish boxes were monitored, and how payment records were handled under the manual system. These methods helped identify the operational problems that guided the development of the proposed system.

The quantitative component consisted of User Acceptance Testing using a structured evaluation instrument based on a 5-point Likert scale. The developed system was evaluated in terms of functionality, usability, accuracy, efficiency, and overall satisfaction. This research approach was appropriate because the study involved both the development of a software solution and the measurement of its acceptability among intended users.

### 3. Software Development Model

The development of the system followed the Waterfall Model. The Waterfall Model was used because it provided a clear and organized sequence of activities from requirement gathering up to testing and deployment. Since the study aimed to build a specific system based on identified operational problems, a sequential development model was appropriate for documenting the project from analysis to implementation.

#### Requirements Phase

During the requirements phase, the researchers conducted interviews and direct observation to identify the limitations of the existing manual process in Maramag Fish Landing. The main concerns identified included inconsistent sales recording, difficulty in identifying fish boxes, weak tracking of returned and missing boxes, and challenges in monitoring buyer balances and payments. From these findings, the functional and non-functional requirements of the system were defined.

The requirements gathered during this phase led to the identification of the major modules of the system, namely user and access management, fish type management, fish price management, fish box registration, sales recording, payment monitoring, receipt printing, analytics, and administrative monitoring. Ethical considerations such as proper data handling and confidentiality of user information were observed during the information-gathering process.

#### Design Phase

During the design phase, the structure of the system was planned based on the gathered requirements. The researchers developed the conceptual framework, use case diagram, entity relationship diagram, data flow diagram, and user interface structure. The database design was organized into related tables to support users, roles, employees, brokers, buyers, fish types, broker fish type assignments, fish prices, fish boxes, fish box purchase cycles, fish inventory logs, sales transactions, sales details, and payments.

The interface design was organized according to user roles. Broker views were designed for operational tasks such as fish box management, sales recording, payment entry, and QR scanning. Administrative views were designed for account management, dashboard reporting, broker monitoring, and fish box tracking. Role-based access and validation rules were incorporated into the design to help secure the application.

#### Implementation Phase

During the implementation phase, the system was built as a Laravel web application. The local development environment was prepared using XAMPP, Composer, Node Package Manager (NPM), and MySQL. The application environment was configured through the `.env` file, after which Laravel migrations were executed to create the database tables needed by the system.

The development process included the following implementation activities:

1. Configuration of the Laravel project environment and database connection.
2. Creation of database migrations for the main system tables.
3. Development of Eloquent models and model relationships.
4. Implementation of authentication and role-based middleware.
5. Development of broker-side modules for fish types, fish prices, fish boxes, sales, payments, analytics, and receipt printing.
6. Development of administrative modules for dashboards, user management, broker sales analysis, and fish box tracking.
7. Integration of QR code generation and QR scanning for fish box identification and return processing.
8. Compilation of front-end assets through Laravel Mix.

Through this phase, the researchers translated the planned design into a functioning web-based system.

#### Testing Phase

During the testing phase, the researchers examined the developed system through unit testing, integration testing, system testing, and User Acceptance Testing. The purpose of this phase was to confirm that the system performed according to the specified requirements and that the individual modules worked correctly both independently and when combined.

Errors and inconsistencies found during testing were corrected before final evaluation. This phase ensured that the developed system was functioning properly and was suitable for actual operational use.

#### Deployment and Maintenance Phase

After successful testing, the system was prepared for deployment in a usable environment. The researchers ensured that the application, database structure, and compiled front-end assets were properly configured. Initial user orientation and familiarization were also considered to support system use by intended users.

Maintenance refers to the continuous improvement of the system after deployment. This includes correcting discovered issues, refining the interface, and considering additional features in future versions based on user feedback and operational needs.

### 4. Requirement Analysis

The requirement analysis of the system was based on the actual operational needs identified during interviews and observation. The study found that brokers needed a more efficient way to register fish boxes, assign fish types and prices, record sales, store buyer information, track payments, print receipts, and monitor the status of fish boxes. Administrative users, on the other hand, needed a centralized system for user management, sales monitoring, and fish box movement tracking.

Based on these findings, the developed system was organized into role-based functional modules. Broker users were given access to modules for fish type management, fish price management, fish box registration, QR-based fish box identification, sales processing, payment entry, receipt printing, and analytics. Administrative users were given access to account management, dashboard summaries, broker sales analysis, and tracking of returned and missing fish boxes.

The use case structure of the system reflects these operational activities and clarifies the interactions between the users and the system. Through requirement analysis, the researchers ensured that the developed application directly responded to the actual workflow and transaction environment of Maramag Fish Landing.

### 5. System Requirements Specification

#### i. Functional Requirements

The functional requirements of the system describe the main services and features that must be provided to its users.

For broker users, the system must allow login and authenticated access, profile viewing and updating, management of fish types, recording of fish prices, registration and updating of fish boxes, QR-based identification of fish boxes, creation and updating of sales transactions, recording of buyer information, recording of payments, computation of remaining balances, receipt printing, and monitoring of fish box status.

For administrative users, the system must allow login and authenticated access, management of broker and admin or staff accounts, viewing of dashboard summaries, broker sales analysis, and monitoring of fish box movement records, particularly returned and missing fish boxes.

The system must also generate reports and summary information relevant to daily operations, including sales totals, fish box counts, outstanding balances, and fish box tracking records.

#### ii. Non-Functional Requirements

The non-functional requirements define the quality characteristics of the system.

In terms of security, the system must require authenticated login before user access is granted. It must enforce role-based access control so that broker users and administrative users only access the modules appropriate to their assigned roles. Input validation and protected request handling must be applied to minimize invalid or unauthorized transactions.

In terms of usability, the system must provide a responsive and understandable user interface that can be used by intended users with minimal difficulty. The interface must be suitable for both desktop and mobile browser access, especially for modules that use QR code scanning.

In terms of performance, the system must be capable of processing routine operational transactions such as sales entry, payment recording, and fish box updates within a reasonable time. In terms of maintainability, the system must be organized in modular form using Laravel MVC to support easier future updates and improvement.

#### iii. Input Requirements

The system requires several categories of input from users. For authentication, users must enter valid login credentials such as email and password. For inventory-related activities, broker users must enter fish type information, fish price information, fish box details, and QR-based fish box inputs.

For transaction processing, broker users must enter sales date, buyer name, optional buyer contact number, selected fish boxes, unit prices, subtotals, and payment information. For account management, administrative users must enter user profile information such as name, email, contact number, role, address, stall name, and position, depending on the account type being created or updated.

These input requirements support the system's operation in managing accounts, transactions, inventory, and monitoring functions.

#### iv. Output Requirements

The system produces several outputs needed for operational and administrative monitoring. These include dashboard summaries, sales records, payment summaries, remaining balance information, fish box status summaries, fish box tracking records, and printed receipts.

For broker users, the outputs include recent sales, daily sales summaries, outstanding balance information, payment history, and printed transaction receipts. For administrative users, the outputs include centralized dashboard summaries, broker sales analysis, top broker and fish type information, and records of returned and missing fish boxes.

These outputs support transaction confirmation, record review, and management decision-making within Maramag Fish Landing.

#### v. Software Requirements

The software requirements of the system include a web server environment capable of running PHP and Laravel, a MySQL database server, and a modern web browser. The application was developed and tested using XAMPP as the local web server package, Composer for PHP dependency management, Node Package Manager for front-end dependencies, and Laravel Mix for compiling JavaScript and CSS assets.

The system also requires browser support for JavaScript and camera access for QR scanning features. It may be accessed through browsers such as Google Chrome, Microsoft Edge, Mozilla Firefox, and other browser applications compatible with modern web standards.

### 6. Design of Software, System, Productions and Process

#### i. Entity Relationship Diagram

The Entity Relationship Diagram (ERD) of the system presents the major database entities and their relationships. At the center of the system is the `users` table, which stores account credentials and status. User roles are managed through the `roles` and `user_roles` tables. Administrative profile information is stored in the `employees` table, while broker profile information is stored in the `brokers` table.

The system stores buyer information in the `buyers` table. Sales transactions are stored in the `sales` table and are associated with both brokers and buyers. Each sale may contain multiple entries in the `sales_details` table, and payments related to a sale are stored in the `payments` table.

Fish classification and pricing are handled using `fish_types`, `broker_fish_type`, and `fish_prices`. The `fish_boxes` table stores the reusable physical fish boxes together with their unique QR codes and current status. The `fish_box_purchases` table stores the purchase-cycle details of a fish box, including the active fish type and cost price for a particular stocking cycle. Fish box movement history is recorded in the `fish_inventory` table, which stores the status changes of fish boxes such as In Stock, Sold, Returned, and Missing.

This relational structure allows the system to manage users, buyers, fish inventory, pricing, sales transactions, payments, and fish box tracking in an organized and consistent manner.

#### ii. Data Flow Diagram

The Data Flow Diagram (DFD) of the system illustrates the movement of data between users, processes, and stored records. The process begins when a user logs into the system. Broker users then interact with the sales and inventory modules by encoding fish types, assigning prices, registering fish boxes, scanning QR codes, recording buyer information, creating sales, and entering payments.

The system processes the encoded data and stores the corresponding records in the database. Sales information is saved in the sales-related tables, while fish box status updates are stored in fish box and inventory tracking records. Payment information is also recorded and used to compute current balances.

Administrative users access the stored records through monitoring modules. These modules retrieve information for dashboard summaries, broker activity monitoring, fish box tracking, and user account management. The DFD therefore shows the flow of operational input from brokers and the retrieval of management information by administrative users.

#### iii. Data Dictionary

Use the actual implemented tables below instead of the old list.

**Table 1. Users**

The `users` table stores the main authentication records of the system. It contains the email address, encrypted password, account status, and timestamp fields for each user. This table serves as the base record for broker and administrative accounts.

**Table 2. Roles**

The `roles` table stores the available user roles in the system. These roles include broker, admin, and staff. The table is used to classify user access privileges.

**Table 3. User Roles**

The `user_roles` table links users and roles. It supports role-based access control by determining which role is assigned to a specific user account.

**Table 4. Employees**

The `employees` table stores profile information for administrative users under the LEEO office. It contains name fields, contact number, position, and the linked user account reference.

**Table 5. Brokers**

The `brokers` table stores the profile information of broker users. It contains the linked user account, broker name fields, address, stall name, and timestamp fields. It identifies the broker responsible for sales and fish box ownership.

**Table 6. Buyers**

The `buyers` table stores buyer information used in sales transactions. It contains first name, middle name, last name, contact information, and timestamps. This allows buyer records to be reused across transactions.

**Table 7. Fish Types**

The `fish_types` table stores the list of fish categories used in the system. It contains the fish type name, description, and timestamp fields. It serves as the master list of fish classifications.

**Table 8. Broker Fish Type**

The `broker_fish_type` table links brokers with the fish types assigned to them. It allows fish types to be managed per broker and serves as the basis for price assignment.

**Table 9. Fish Prices**

The `fish_prices` table stores price records for broker-assigned fish types. It contains the linked broker fish type reference, price, price date, and timestamps. It is used to maintain the current selling prices of fish types.

**Table 10. Fish Boxes**

The `fish_boxes` table stores the reusable physical fish boxes. It contains the broker reference, unique QR code, current box status, timestamps, and soft delete information. Each fish box is uniquely identified within the system.

**Table 11. Fish Box Purchases**

The `fish_box_purchases` table stores the purchase-cycle information of fish boxes. It contains the linked fish box, fish type, created by user reference, purchase date, cost price, and timestamps. This table supports the tracking of fish boxes across stocking cycles.

**Table 12. Fish Inventory**

The `fish_inventory` table stores movement logs for fish boxes. It records the linked fish box purchase cycle, the user who created the record, the status value, and timestamps. It serves as the tracking history for In Stock, Sold, Returned, and Missing statuses.

**Table 13. Sales**

The `sales` table stores the summary record of each sales transaction. It contains the sales date, broker reference, buyer reference, total amount, status, and timestamps. This table represents the main sales transaction header.

**Table 14. Sales Details**

The `sales_details` table stores the itemized records under each sale. It contains the linked sale, fish box purchase reference, unit price, subtotal, discount, and timestamps. It connects each sale to the corresponding fish box and fish type data.

**Table 15. Payments**

The `payments` table stores the payment records related to sales transactions. It contains the linked sale, paid amount, payment date, payment method, and timestamps. This table supports partial and full payment monitoring.

### 7. Development and Testing: Levels of Software Testing

#### i. Unit Testing

Unit testing was used to examine individual parts of the system independently. The researchers checked specific components such as login validation, user management logic, fish box registration, sales processing, payment entry, QR scanning response, and fish box status updating. The purpose of this level of testing was to verify that each module performed its intended function correctly before being combined with other modules.

#### ii. Integration Testing

Integration testing was performed after the individual modules had been verified. At this stage, the interaction between connected components was tested. Examples include the connection between sales transactions and fish box status updates, the connection between payment records and computed sales balance, and the connection between fish box movement updates and inventory tracking logs. This testing confirmed that the integrated modules exchanged data correctly and supported the overall workflow of the application.

#### iii. System Testing

System testing was conducted to assess the performance of the complete system as one whole application. The researchers tested the combined operation of authentication, inventory management, QR processing, sales entry, payment recording, dashboard reporting, and account management. This testing was used to verify that the application satisfied both its functional and non-functional requirements.

#### iv. User Acceptance Testing (UAT)

User Acceptance Testing was conducted to evaluate the developed system from the perspective of its intended users. The evaluation involved selected respondents from among brokers, staff, and administrative personnel connected with Maramag Fish Landing. The respondents used the system and assessed it through a 5-point Likert scale based on functionality, usability, accuracy, efficiency, and overall satisfaction.

The purpose of UAT was to determine whether the developed Point-of-Sale and Inventory Management System was understandable, reliable, and suitable for actual use in Maramag Fish Landing. The results of this evaluation served as the basis for determining the acceptability of the system.

## Important Correction Notes

When revising the paper, also apply these corrections:

- Replace references to a standalone `LEEO` table with `authorized administrative users under the LEEO office`.
- Replace `inventory_logs` with `fish_inventory`.
- Replace `sales_payments` with `payments`.
- Include `buyers`, `roles`, `user_roles`, `employees`, `broker_fish_type`, `fish_prices`, and `fish_box_purchases` in the ERD and data dictionary.
- Do not describe SMS alerts, offline PWA features, or public QR pages as already implemented.
