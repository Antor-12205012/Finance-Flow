***FinanceFlow***

Personal Finance Management System

About the Project

FinanceFlow is a web-based Personal Finance Management System developed as an academic project.

It helps users track income, expenses, budgets, savings goals, cost-cutting plans, and financial streaks through a simple and user-friendly interface.

The system is built using PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap.

It focuses on clarity, security, and everyday usability without relying on any third-party financial services.

->>Features

User registration and login with secure password hashing

Income and expense tracking with categories and dates

Monthly budget planning with progress visualization

Savings goals with target amounts and deadlines

Cost-cutting goals for reducing spending

Daily and monthly financial streak tracking

Financial reports with charts and summaries

Responsive and mobile-friendly user interface



->>Tech Stack

Frontend:

HTML5

CSS3

Bootstrap 5

JavaScript

Backend:

PHP
Session-based authentication

bcrypt password hashing


Database:

MySQL 

Project Structure


financeflow/
│
├── index.php                  # Dashboard (main page)
├── login.php                  # User login
├── register.php               # User registration
├── database.sql               # Full database schema + sample data
├── README.md
│
├── includes/
│   ├── config.php             # DB connection, session, helper functions
│   ├── header.php             # Sidebar navigation + topbar
│   └── footer.php             # Scripts + closing tags
│
├── pages/
│   ├── transactions.php       # Income & expense CRUD with filters
│   ├── budget.php             # Monthly budget management
│   ├── savings.php            # Savings goals tracker
│   ├── cost_cutting.php       # Spending reduction goals
│   ├── streaks.php            # Streaks & achievement badges
│   ├── reports.php            # Monthly reports with charts
│   └── logout.php             # Session destroy + redirect
│
└── assets/
    ├── css/
    │   └── style.css        
    └── js/
        └── main.js          



->>Future Enhancements

Mobile app for on-the-go tracking

Enhanced data visualization: interactive charts and dashboards

Export reports (PDF/Excel) & scheduled email summaries

Budget alerts and notifications

AI-driven insights: spending suggestions.



