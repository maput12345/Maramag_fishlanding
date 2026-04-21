# Language and References Polish

This guide focuses only on:

- Chapter I awkward wording
- Chapter III awkward wording
- Chapter VI awkward wording
- References consistency check

Source reviewed:

- [tmp_capstone2_extract.txt](c:/xampp/htdocs/maramag_fishlanding/POS/tmp_capstone2_extract.txt)

## 1. Chapter I Polished Revisions

### A. Background of the Study

Use this cleaner version for the first four paragraphs:

> Sales tracking and inventory management are essential in businesses dealing with perishable products such as fish. In Maramag Fish Landing, manual documentation has resulted in operational problems such as missing fish boxes, incomplete sales records, and difficulty in tracking daily transactions. These inefficiencies reduce transparency, weaken accountability, and affect overall operational performance. As a result, brokers and the Local Economic Enterprise Office (LEEO) may experience both financial and administrative losses. Literature shows that manual stock systems are prone to error and inefficiency, which may lead to financial loss and slower business processes (Kumar & Suresh, 2021).  
>  
> Inventory control in Maramag Fish Landing still relies on handwritten records and manual data entry. This method is highly vulnerable to human error. Incomplete records may lead to disagreements between brokers and buyers, delayed transactions, and inaccurate financial records. Smith et al. (2022) noted that manual inventory procedures often result in data inconsistencies that make record reconciliation difficult. In addition, monitoring fish box movements between brokers and buyers remains a challenge because there is no systematic tracking mechanism in place (Rodriguez & Tan, 2023).  
>  
> To address these problems, the implementation of an inventory management system is necessary. A process-based system helps ensure that inventory transactions follow a consistent workflow, thereby reducing inconsistencies (Brown & Williams, 2021). A structured digital system can record and monitor each transaction while generating updated information on stock levels, sales, and financial records (Garcia & Lopez, 2022). Automation can also improve accuracy by reducing human error in calculations and documentation (Davis, 2023). Moreover, transparency allows stakeholders to track transactions more effectively, thereby reducing discrepancies and strengthening accountability (Nguyen & Patel, 2023).  
>  
> This study aims to design and implement a Point-of-Sale (POS) and Inventory Management System for Maramag Fish Landing to improve efficiency, accuracy, and transparency in daily operations. With the continued growth of digital technologies, many businesses are adopting automated inventory and sales systems to improve business performance. An integrated system supports data processing and automation, which helps improve operational efficiency (Lee et al., 2021). Inventory and POS systems have also been shown to improve transaction accuracy, minimize errors, and support better decision-making (Gonzalez & Martinez, 2022). By adopting such a system, Maramag Fish Landing can reduce operational inefficiencies and improve the reliability and transparency of market transactions.

### B. Statement of the Problem

Use this cleaner version:

> Maramag Fish Landing does not yet have an automated inventory and sales tracking system, resulting in significant operational inefficiencies. The current process still depends heavily on manual transaction recording by brokers, which leads to several problems.  
>  
> First, inventory and sales transactions are still recorded manually by brokers and LEEO personnel. This manual process often results in incomplete, lost, or inaccurate records, especially in a high-volume trading environment. As a result, accountability is reduced and transaction monitoring becomes difficult.  
>  
> Second, manual sales recording increases the likelihood of human error because transactions are handled in a fast-paced environment with many buyers each day. Incorrect amounts, missing entries, and incomplete records may lead to unreliable sales reports and difficulty in determining actual financial performance.  
>  
> Third, there is no systematic way to monitor who purchased the fish and how many fish boxes were sold each day. Fish boxes are costly and directly affect broker income because brokers depend on the number of boxes sold and returned. Without proper monitoring, missing or unreturned boxes often remain unaccounted for. In such cases, brokers are forced to treat the boxes as lost and replace them using their own earnings. This weakens accountability and reduces transparency in fish landing operations. Based on a survey of the eight brokers at Maramag Fish Landing, these tracking problems are common and significant.  
>  
> If these inefficiencies remain unresolved, brokers will continue to experience difficulty in accurately recording sales, preparing reliable financial reports, and maintaining proper inventory records. To address these concerns, this study proposes the development of a POS and Inventory Management System that automates processes, improves transparency, and supports fish box tracking and sales monitoring.

### C. Objectives of the Study

Current issues:

- `General Objective:This study...` needs spacing
- bullet structure is inconsistent
- `fish boxesusing` is missing a space

Use this improved version:

> **General Objective**  
> This study aims to design and implement a Point-of-Sale (POS) and Inventory Management System for Maramag Fish Landing to improve efficiency, accuracy, and transparency in fish box tracking and sales recording.  
>  
> **Specific Objectives**  
> 1. To conduct a needs assessment to identify the primary challenges related to inventory loss, sales tracking, and record management.  
> 2. To design, develop, and implement a centralized sales and inventory management system supported by a structured database.  
> 3. To design, develop, and implement a fish box tracking system using unique identifiers and QR codes.  
> 4. To design, develop, and implement a dashboard feature for brokers and LEEO administrators to monitor sales activity.  
> 5. To design and develop automated reports and analytical insights.

### D. Significance of the Study

Current:

> This study is significant as it developed a Point-of-Sale (POS) and Inventory Management System...

Better:

> This study is significant because it developed a Point-of-Sale (POS) and Inventory Management System that can improve the operational efficiency of Maramag Fish Landing.

## 2. Chapter III Polished Revisions

### A. Overview of the System

This section is already generally strong, but this paragraph can be smoother:

Current:

> The developed system provides a centralized digital platform for recording sales transactions, managing fish-related inventory information, monitoring customer payments, and tracking fish boxes through QR codes.

Polished:

> The developed system provides a centralized digital platform for recording sales transactions, managing fish-related inventory data, monitoring customer payments, and tracking fish boxes through QR codes.

### B. Existing Solutions or Benchmarks

Current:

> Existing Point-of-Sale and inventory management systems are commonly used in retail and service-based businesses.

Polished paragraph:

> Existing Point-of-Sale (POS) and inventory management systems are widely used in retail and service-oriented businesses. These systems commonly support transaction recording, inventory monitoring, and report generation. However, most available solutions are designed for general retail environments and do not address the specific operational workflow of a fish landing facility that depends on reusable fish boxes, return monitoring, and broker-based transaction handling.

### C. Use Case Diagram Discussion

Current:

> The use case diagram that you see in figure 3 presents the functional interaction between the users and the Point-of-Sale and Inventory Management System...

Polished:

> Figure 3 presents the functional interaction between the users and the Point-of-Sale and Inventory Management System for Maramag Fish Landing.

Current:

> The admin serves as the main administrator of the system.

Polished:

> The Admin serves as the primary administrator of the system.

### D. Non-Functional Requirements

Current:

> In terms of performance, the system must be capable of processing routine operational transactions such as sales entry, payment recording, and fish box updates within a reasonable time. In terms of maintainability, the system must be organized in modular form using Laravel MVC to support easier future updates and improvement.

Polished:

> In terms of performance, the system must be capable of processing routine operational transactions such as sales entry, payment recording, and fish box updates within a reasonable time. In terms of maintainability, the system must be organized in modular form using the Laravel MVC architecture to support easier future updates and improvements.

## 3. Chapter VI Polished Revisions

### A. Conclusion

Use this polished version:

> The Point-of-Sale (POS) and Inventory Management System for Maramag Fish Landing was successfully developed and implemented to address the limitations of the previous manual process, particularly in sales recording, fish box tracking, inventory monitoring, and administrative transparency.  
>  
> The system was properly planned, analyzed, designed, implemented, and tested based on the operational requirements of brokers, staff, and the Local Economic Enterprise Office (LEEO). It integrates key features such as user authentication, QR code-based fish box tracking, sales and payment recording, inventory management, receipt generation, and report monitoring in one web-based platform.  
>  
> User Acceptance Testing (UAT) was conducted with eight (8) respondents and produced an overall mean score of 4.70, verbally interpreted as Strongly Acceptable. This indicates that the system is functional, accurate, efficient, and user-friendly for the intended users of Maramag Fish Landing.  
>  
> Based on the implementation results and user evaluation, the system may be considered a reliable digital tool for improving transaction accuracy, reducing manual workload, strengthening inventory management, and increasing transparency in fish landing operations.

### B. Recommendations

Use this improved introduction:

> The Point-of-Sale (POS) and Inventory Management System for Maramag Fish Landing produced satisfactory results during testing. However, the following recommendations are proposed to further improve the system's performance and user experience:

Use this improved recommendation wording:

> **i. Message Alerts for Remaining Balance**  
> It is recommended that a message alert feature be added to notify customers of their remaining balance. Since the system already stores customer phone numbers, this feature can be used to send text message reminders and help customers stay informed about their unpaid balances.  
>  
> **ii. Message Alerts for Payment Confirmation**  
> Another recommended enhancement is the addition of a message alert feature that notifies customers whenever a payment has been received and recorded by the system. This can improve customer awareness and strengthen transaction transparency.  
>  
> By implementing these suggested features, the Point-of-Sale (POS) and Inventory Management System for Maramag Fish Landing can be further improved, providing better convenience, timely updates, and improved service for both users and customers.

## 4. References Consistency Review

### A. Good news

Your references are already mostly in one academic style and appear close to APA 7 format:

- alphabetical order is correct
- year placement is consistent
- journal titles and volume formatting are generally consistent
- DOI entries are already written as URLs
- thesis entries are reasonably formatted

### B. Main problem: missing references

These in-text citations appear in the paper but do **not** appear in the references list:

- `Kumar & Suresh, 2021`
- `Smith et al., 2022`
- `Rodriguez & Tan, 2023`
- `Brown & Williams, 2021`
- `Garcia & Lopez, 2022`
- `Davis, 2023`
- `Nguyen & Patel, 2023`
- `Lee et al., 2021`
- `Gonzalez & Martinez, 2022`
- `Sullivan & Artino, 2013`

You must do one of these:

1. Add the full references for these sources to the reference list
2. Remove or replace these citations with sources that are already in your bibliography

If you do not fix this, the panel may say that your citations and references are inconsistent.

### C. APA-style cleanup to apply in Word

In your final Word document, make sure all references follow these rules consistently:

- use hanging indent for every reference entry
- keep titles in sentence case
- italicize book, thesis, journal title, and journal volume where required
- keep issue numbers in parentheses after the volume
- use `https://doi.org/...` format for DOIs
- do not mix plain DOI numbers and URL-style DOI links
- ensure every source cited in the text appears in the references list
- ensure every source in the references list is cited in the text

### D. References that seem okay structurally

These entries look generally acceptable in structure:

- APEC. (2024)...
- Bastan et al. (2004)...
- Carmen et al. (2023)...
- Dalida et al. (2023)...
- Jose et al. (2024)...
- Li et al. (2024)...
- Magbanua et al. (2007)...
- Mangmang (2018)...
- Muchaendepi et al. (2019)...
- Oliveira et al. (2021)...
- Parreño-Marchante et al. (2014)...
- Rodriguez-Salvador and Calvo-Dopico (2020)...
- Tian and Wang (2022)...
- Valencia et al. (2023)...
- Yuwono et al. (2024)...

## 5. Best Next Step

Revise in this order:

1. Fix the missing references first
2. Replace the awkward Chapter I paragraphs
3. Replace the Chapter VI conclusion and recommendations
4. Smooth the Chapter III wording
5. Apply final APA formatting in Word

