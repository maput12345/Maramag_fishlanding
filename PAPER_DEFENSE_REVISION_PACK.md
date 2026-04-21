# Paper Defense Revision Pack

This file is focused on one goal: make the Maramag Fish Landing capstone paper cleaner, more accurate, and easier to defend.

Use this together with:

- `CAPSTONE_ALIGNMENT_NOTES.md`

## What To Fix First

If your defense is near, revise these in this order:

1. `Chapter I`:
   - Statement of the Problem
   - Objectives of the Study
   - Scope and Limitations
   - Significance of the Study
2. `Chapter II`:
   - remove weak or questionable references
   - rewrite the synthesis and gaps sections
3. `Chapter III / Technical Background`:
   - make the module descriptions match the real system
4. `Chapter IV / Methodology`:
   - fix research design
   - fix ERD and data dictionary
   - clarify testing and UAT
5. `Chapter V`:
   - connect actual results to objectives
6. `Chapter VI`:
   - rewrite conclusion and recommendations more formally

## Main Rule For Defense

Do not claim anything in the paper that is not clearly implemented in the system.

### Safe claims

- web-based system
- role-based access
- broker and admin or staff access
- QR-based fish box identification
- fish box status tracking
- buyer recording
- payment recording
- remaining balance tracking
- receipt printing
- broker analytics
- admin monitoring of broker sales and fish box movement

### Avoid claiming as implemented

- SMS alerts
- automatic customer text messaging
- offline PWA capability
- demand forecasting
- AI analytics
- public QR scan page for customers

Those should be written only as future recommendations if not actually implemented.

## Ready-To-Paste Revisions

## 1. Scope of the Study

Use this revised version:

This study focuses on the development and implementation of a web-based Point-of-Sale (POS) and Inventory Management System for Maramag Fish Landing. The system is intended to support the daily operations of brokers and authorized administrative personnel by digitizing fish type management, fish price management, fish box registration, QR-based fish box identification, sales recording, payment monitoring, receipt printing, and dashboard reporting.

Each fish box is assigned a unique QR code that is used within the system for identification and tracking. Through QR code scanning, brokers can retrieve fish box information for sales processing and update the status of fish boxes during return transactions. The system also records buyer information, monitors outstanding balances, and keeps track of fish boxes that are in stock, sold, returned, or missing.

The system provides role-based access for two major user groups: broker users and administrative users. Administrative users under the Local Economic Enterprise Office (LEEO), specifically authorized admin and staff accounts, can monitor broker activities, manage user accounts, review sales summaries, and view fish box tracking records through a centralized dashboard.

The system is designed specifically for Maramag Fish Landing and reflects its current operational workflow. Buyers do not directly access the system; their information is encoded by brokers during transaction processing.

## 2. Limitations of the Study

Use this revised version:

The system is specifically designed for the operational setup of Maramag Fish Landing. For this reason, it may require further modification before it can be applied to other fish markets or organizations with different business rules and workflows.

The study is limited to the implementation of core operational functions such as sales recording, buyer information management, fish type and price management, fish box tracking, payment recording, receipt printing, and dashboard reporting. Advanced features such as SMS notifications, offline-first mobile support, predictive analytics, and artificial intelligence-based forecasting are not included in the present system.

The application requires browser access and a functioning network environment for normal operation. The performance of the system may be affected in areas with unstable connectivity. In addition, only authorized broker, admin, and staff accounts can access the system, while buyers have no direct user access.

## 3. General Objective

Use this:

This study aims to design, develop, and evaluate a web-based Point-of-Sale and Inventory Management System for Maramag Fish Landing in order to improve efficiency, accuracy, and transparency in sales recording, payment monitoring, and fish box tracking.

## 4. Specific Objectives

Use this:

1. To assess the existing problems in manual sales recording, payment monitoring, and fish box tracking at Maramag Fish Landing.
2. To design and develop a web-based system for managing fish types, fish prices, fish boxes, sales transactions, and payments.
3. To implement QR code-based identification and tracking of fish boxes for sales and return processing.
4. To provide dashboards for broker users and authorized LEEO administrative users for monitoring transactions and fish box movement.
5. To evaluate the developed system in terms of functionality, usability, accuracy, efficiency, and overall user acceptability.

## 5. System Overview

Use this in the technical background or system overview:

The developed system is a web-based Point-of-Sale and Inventory Management System intended for Maramag Fish Landing. It was designed to address problems associated with manual record-keeping, such as missing sales records, delayed monitoring, inconsistent payment tracking, and difficulty in identifying missing or returned fish boxes.

The system has two major access groups. The first group consists of broker users, who are responsible for fish type and fish price management, fish box registration, sales recording, payment entry, receipt printing, and fish box return or missing-status processing. The second group consists of authorized administrative users under the Local Economic Enterprise Office (LEEO), specifically admin and staff accounts, who can monitor user accounts, broker performance, sales summaries, and fish box tracking records.

The system uses QR codes to uniquely identify fish boxes. During operations, brokers may scan a fish box QR code to retrieve box information for sales processing or to update the box status during return transactions. The system also stores buyer information, computes paid and remaining balances, and provides analytics and summary reports through dashboards.

## 6. Research Design

Use this instead of saying only "quantitative and observational":

This study employed a mixed-method developmental research design. Qualitative methods, specifically interviews and direct observation, were used during the requirements gathering stage to identify the operational problems encountered by brokers and administrative personnel at Maramag Fish Landing. Quantitative evaluation was then applied through User Acceptance Testing (UAT) using a 5-point Likert scale to assess the developed system in terms of functionality, usability, accuracy, efficiency, and overall satisfaction.

This design was appropriate because the study involved both the development of a software solution and the evaluation of its acceptability among its intended users.

## 7. Methodology Paragraph For Data Gathering

Use this:

Data gathering began with interviews and direct observation of the existing manual workflow at Maramag Fish Landing. These activities helped identify common problems in sales recording, buyer monitoring, payment tracking, and fish box accountability. The information gathered was used as the basis for determining the functional and non-functional requirements of the proposed system.

After development, User Acceptance Testing was conducted among selected intended users composed of brokers, staff, and administrative personnel. A structured evaluation form using a 5-point Likert scale was used to measure the users' assessment of the system's functionality, usability, accuracy, efficiency, and overall satisfaction.

## 8. Correct System Actor Description

Use this:

The main users of the system are broker users and administrative users. Broker users perform day-to-day operational tasks such as encoding sales, recording payments, printing receipts, and managing fish box status. Administrative users, composed of authorized admin and staff accounts under the LEEO office, oversee account management, sales monitoring, and fish box tracking.

## 9. QR Code Description

Use this:

Each fish box is assigned a unique QR code upon registration in the system. The QR code serves as the digital identifier of the fish box and supports faster and more accurate processing during sales and return transactions. By scanning the QR code, brokers can retrieve the corresponding fish box information and update the box status within the system, thereby reducing encoding errors and improving tracking efficiency.

## 10. Results Section Intro

Use this:

The developed Point-of-Sale and Inventory Management System was successfully implemented as a web-based application for Maramag Fish Landing. The system supports fish type and fish price management, fish box registration, QR code-based fish box identification, sales transaction recording, payment monitoring, receipt printing, dashboard reporting, and fish box tracking for returned and missing units. The developed features directly addressed the problems identified during the requirement gathering stage.

## 11. Chapter V Discussion Angle

When discussing your screenshots and modules, connect each one to the problem it solves.

Example lines you can use:

- The sales module addressed the problem of incomplete and inaccurate manual transaction recording.
- The payment module improved monitoring of balances by allowing partial and full payments to be recorded systematically.
- The fish box tracking module improved accountability by monitoring whether fish boxes were in stock, sold, returned, or missing.
- The QR code feature reduced manual identification errors and made fish box processing faster.
- The admin dashboard improved transparency by allowing authorized personnel to monitor broker activity and fish box movement.

## 12. Conclusion

Use this revised version:

The study successfully designed, developed, and evaluated a web-based Point-of-Sale and Inventory Management System for Maramag Fish Landing. The system was created to address the limitations of the previous manual process, particularly in sales recording, payment monitoring, buyer recording, and fish box tracking.

The developed system provided practical modules for fish type and fish price management, fish box registration, QR code-based identification, sales entry, payment recording, receipt printing, broker analytics, and administrative monitoring. These features improved the efficiency and organization of day-to-day operations and supported better accountability in fish box movement and transaction monitoring.

User Acceptance Testing showed that the system was highly acceptable to its intended users. Based on the evaluation results and the actual implemented features, the system may be considered a reliable and useful tool for improving operational efficiency, accuracy, and transparency at Maramag Fish Landing.

## 13. Recommendations

Use this revised version:

Based on the results of the study, the following are recommended for future improvement of the system:

1. Add an SMS notification feature for balance reminders and payment confirmations, if needed by future users.
2. Enhance the system with backup and recovery features to further protect transaction and inventory records.
3. Develop additional reports and filtering tools for more detailed administrative monitoring.
4. Explore offline-capable or mobile-enhanced functionality for use in areas with unstable connectivity.
5. Conduct further testing with a larger number of users and for a longer operational period to gather more evaluation data.

## Defense Questions You Should Expect

Prepare short answers for these:

### 1. Why did you choose QR code instead of RFID or blockchain?

Suggested answer:

QR code was chosen because it is more practical, affordable, and easier to implement in the operational setting of Maramag Fish Landing. Unlike RFID or blockchain-based solutions, QR codes do not require expensive infrastructure and are easier for local users to adopt while still improving fish box identification and tracking.

### 2. Why is your study mixed-method?

Suggested answer:

It is mixed-method because qualitative techniques such as interviews and observation were used to gather requirements and identify operational problems, while quantitative evaluation was used during User Acceptance Testing through a Likert-scale assessment.

### 3. What makes your system different from a generic POS?

Suggested answer:

The system is tailored to the workflow of Maramag Fish Landing. It does not only record sales, but also manages reusable fish boxes, tracks their movement through status changes, supports QR-based identification, and allows administrative monitoring of fish box returns and missing boxes.

### 4. What are the main limitations of the system?

Suggested answer:

The system is limited to the workflow of Maramag Fish Landing and currently focuses on core operational functions. It does not yet include SMS alerts, offline-first capability, predictive analytics, or advanced forecasting tools.

### 5. Why did you include admin and staff under LEEO?

Suggested answer:

Because administrative monitoring in the actual implemented system is handled through authorized administrative accounts. These accounts represent LEEO personnel who supervise transactions, users, and fish box tracking.

## Final Defense Advice

- Keep your claims simple and true to the system.
- Do not oversell features that are not implemented.
- Always connect each module to a problem from Chapter I.
- If a panel asks about a missing feature, frame it as a recommendation for future enhancement.
- Make sure your paper, ERD, scope, and oral explanation use the same terms:
  - broker users
  - admin or staff users
  - buyers
  - fish boxes
  - QR code identification
  - payments
  - returned and missing fish box tracking
