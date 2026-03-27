# Product Requirements Document (PRD)
**Project:** Contractor Income & Tax Management Tool (Kova)
**Target Market:** Jamaica
**Tech Stack:** Laravel 13, Vue.js 3, Inertia.js, PrimeVue

---

## 1. Product Objective
The core objective of this application is to provide an intuitive, automated financial and tax management dashboard specifically tailored for independent contractors, freelancers, and self-employed professionals in Jamaica. 

Unlike full-time employees under PAYE, these individuals are burdened with managing their own statutory deductions, progressive income tax filings, withholding taxes, and GCT thresholds. This tool aims to eliminate the friction of TAJ compliance by automating calculations, projecting quarterly liabilities, and generating tax-ready documentation.

## 2. Target Audience
* Independent IT professionals, engineers, accountants, and consultants.
* Self-employed tradespersons (construction, haulage, tillage).
* Freelancers operating within the Jamaican market subject to TAJ regulations.

---

## 3. Functional Requirements

### 3.1. Income & Tax Estimation Engine
The system must act as a real-time financial projector to prevent unexpected year-end tax liabilities.
* **Progressive Tax Brackets:** The calculation engine must accurately apply current Jamaican tax thresholds to net income:
  * Tax-free threshold: JMD $1,700,088
  * 25% bracket: Income up to JMD $6,000,000
  * 30% bracket: Income exceeding JMD $6,000,000
* **Quarterly Estimates Dashboard:** The system must project estimated tax payments due on the four statutory deadlines (March 15, June 15, September 15, and December 15) based on invoiced and logged income.
* **Statutory Contributions:** The engine must calculate mandatory self-employed deductions:
  * National Insurance Scheme (NIS)
  * Education Tax

### 3.2. Withholding Tax & Contractors Levy Tracking
The platform must account for taxes deducted at the source to ensure users do not overpay the TAJ.
* **3% Withholding Tax Logic:** For users providing "specified services" (e.g., IT, engineering, management) to designated entities, the system must track invoices over JMD $50,000 and calculate the 3% withheld amount.
* **2% Contractors Levy:** For users in construction, tillage, or haulage, the system must track the 2% deduction.
* **Tax Credit Reconciliation:** The application must maintain a ledger of all withheld amounts. These logged amounts must be dynamically applied as tax credits against the user's total calculated tax liability for the year.

### 3.3. Expense Management & Deductions
To accurately determine taxable income, users must be able to offset their gross revenue with valid business expenses.
* **Expense Tracker:** Users must be able to log expenses incurred "wholly and exclusively" to generate income (e.g., equipment, fuel, rent, software).
* **Receipt Management:** The system should allow for receipt uploads/attachments for auditing purposes.
* **Dynamic Tax Recalculation:** Categorized expenses must automatically deduct from the gross income, instantly updating the estimated tax liability in the estimation engine.

### 3.4. GCT (General Consumption Tax) Management
The platform must monitor revenue to ensure GCT compliance.
* **Threshold Alerts:** The system must track annual turnover and trigger alerts when a user approaches the JMD $15,000,000 mandatory GCT registration threshold.
* **Invoice Appending:** Once a user is GCT-registered, the system must provide a configuration toggle that automatically calculates and appends a 15% GCT line item to all newly generated invoices.

### 3.5. Automated TAJ Form Preparation
The final output of the tool must bridge the gap between internal tracking and official government compliance.
* **Form S04 / IT01 Mapping:** The system must aggregate the user's income, categorized expenses, and reconciled withholding tax credits into a standardized format.
* **Document Generation:** The platform will generate a summarized PDF or data sheet. This document must mirror the exact line items required by the TAJ for the Self-Employed Annual Return (Form S04) or IT01, allowing the user to simply copy the values over to the official TAJ portal.

---

## 4. Success Metrics
* Reduction in time spent calculating quarterly estimated taxes for the user.
* Accuracy of the generated S04/IT01 summary sheets compared to final TAJ assessments.
* User retention spanning across the quarterly tax payment cycles.