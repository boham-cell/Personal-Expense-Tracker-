# Personal Expense Tracker

A simple web app that helps you keep track of the money you receive and the money you spend. It gives you a clear picture of your spending habits through a dashboard, budgets, charts and reports.

Built with **PHP**, **MySQL**, **JavaScript** and **Bootstrap**.

## Features

- **Accounts:** register and log in securely (passwords are hashed, never stored as plain text)
- **Income and expenses:** record money received and money spent, with a date and a note
- **Categories:** every expense belongs to a category (Food, Transport, Data, Entertainment, Other)
- **Budgets:** set a monthly spending limit for each category and get a warning at 80% and an "over budget" alert at 100%
- **Dashboard:** shows total income, total expenses and remaining balance for the current month
- **Charts:** a doughnut chart of spending by category and a bar chart of income vs expenses over the last 6 months
- **Reports:** view your finances by day, week or month

## Example

A student receives GH₵2,000 in a month and spends:

| Category | Amount |
|---|---|
| Food | GH₵500 |
| Transport | GH₵200 |
| Data | GH₵150 |
| Entertainment | GH₵250 |
| Other | GH₵100 |

The dashboard shows:

- Total income: GH₵2,000
- Total expenses: GH₵1,200
- Remaining balance: GH₵800

## What you need

- [XAMPP](https://www.apachefriends.org/) (it includes PHP, Apache and MySQL/MariaDB)
- A web browser
- A code editor such as VS Code (optional)

## How to set it up

### 1. Get the project files

Download this repository (green **Code** button, then **Download ZIP**) and unzip it, or clone it:

```
git clone https://github.com/YOUR-USERNAME/Personal-Expense-Tracker-.git
```

Put the project folder inside XAMPP's `htdocs` folder and name it `expense-tracker`. The final path should look like this:

```
htdocs/expense-tracker/
```

### 2. Start XAMPP

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### 3. Create the database

1. Open `http://localhost/phpmyadmin` in your browser.
2. Click **New** in the left sidebar.
3. Name the database `expense_tracker` and click **Create**.

### 4. Create the tables

1. Click `expense_tracker` in the left sidebar so it is selected.
2. Click the **SQL** tab.
3. Paste the code below and click **Go**:

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(50) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT NULL,
  type ENUM('income','expense') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255),
  transaction_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE budgets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT NOT NULL,
  month_year CHAR(7) NOT NULL,
  limit_amount DECIMAL(10,2) NOT NULL,
  UNIQUE (user_id, category_id, month_year),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
```

You should now see four tables: `users`, `categories`, `transactions` and `budgets`.

### 5. Check the database connection

Open `config/db.php`. The default XAMPP settings should already work:

```php
$host = 'localhost';
$db   = 'expense_tracker';
$user = 'root';
$pass = '';
```

If you set a MySQL password, change `$pass` to match it.

### 6. Open the app

Go to:

```
http://localhost/expense-tracker/auth/register.php
```

Create an account, log in, and start adding transactions.

## How to use it

1. **Register** and log in.
2. **Add income** (for example your monthly allowance) from the **Add transaction** page.
3. **Add expenses** whenever you spend money, and pick a category for each one.
4. Open **Budgets** and set a monthly limit for each category.
5. Check the **Dashboard** to see your totals and charts.
6. Open **Reports** to look at a particular day, week or month.

## Project structure

```
expense-tracker/
├── config/
│   └── db.php                  database connection
├── auth/
│   ├── register.php            create an account
│   ├── login.php               log in
│   └── logout.php              log out
├── transactions/
│   ├── add.php                 add income or an expense
│   ├── list.php                view all transactions
│   └── delete.php              delete a transaction
├── budgets/
│   └── index.php               set and track monthly budgets
├── api/
│   ├── category_spending.php   data for the doughnut chart
│   └── monthly_summary.php     data for the bar chart
├── dashboard.php               income, expenses, balance and charts
└── reports.php                 daily, weekly and monthly reports
```

## Database design

| Table | What it stores |
|---|---|
| `users` | Account details and hashed passwords |
| `categories` | Each user's expense categories |
| `transactions` | Every income and expense record |
| `budgets` | Monthly spending limits per category |

Income and expenses share the `transactions` table and are told apart by the `type` column. Money is stored as `DECIMAL` to avoid rounding errors.

## Security

- Passwords are hashed with `password_hash()` and checked with `password_verify()`
- All database queries use PDO prepared statements to prevent SQL injection
- Output is escaped with `htmlspecialchars()` to prevent XSS
- Every query is filtered by the logged-in user, so people can only see their own data
- Private pages redirect to the login page if you are not logged in

## Troubleshooting

| Problem | Fix |
|---|---|
| "Database connection failed" | Make sure MySQL is running in XAMPP and the database is named `expense_tracker` |
| Page not found | Check the folder is named `expense-tracker` and is inside `htdocs` |
| Charts are blank | Press F12, open the Console tab and look for errors. Also open `api/category_spending.php` in the browser to check the data |
| Can't log in after registering | Register again, and make sure `password_hash` in `users` is `VARCHAR(255)` |

## Ideas for future improvements

- Shared navigation bar on every page
- Add, rename and delete custom categories
- Edit existing transactions
- CSRF protection on forms
- Export transactions to CSV
- Copy last month's budgets to the new month

## Author

Built as a learning project to practise PHP, MySQL, JavaScript and data visualisation.
