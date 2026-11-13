# Production Planning & Material Requirement Planning Dashboard

A portfolio project that simulates ERP workflows for a fictional mineral water manufacturer. It demonstrates how product BOMs, material inventory, production plans, and production realizations work together in a small manufacturing application.

This project is a standalone simulation. It is not connected to a real company's ERP or production system.

## Features

- **Authentication** — session-based login and logout for application users.
- **Dashboard** — database-backed summary of active products and materials, planned production, material shortages, upcoming plans, and recent realizations.
- **Product Management** — create, view, update, search, and manage active products.
- **Material Management** — create, view, update, search, and manage active materials.
- **Bill of Materials (BOM)** — define the material quantities required for each product.
- **Material Inventory** — view balances, receive materials, record stock adjustments, and configure reorder levels with stock movement history.
- **Production Planning** — create production plans that retain a snapshot of the BOM used at planning time.
- **Material Requirements Planning (MRP)** — aggregate material requirements for planned production and compare them with current stock to identify shortages.
- **Production Realization** — create and post production realizations, recording material consumption and finished goods output.
- **Finished Goods Inventory** — maintain finished goods balances and production output movement history.

## Business Workflow

```text
Products → BOM → Material Inventory → Production Planning → MRP → Production Realization → Finished Goods Inventory
```

Products and their BOMs define what can be manufactured. Each production plan stores a BOM snapshot, so later master-data changes do not silently alter an existing plan. MRP aggregates requirements from plans with a planned status and compares them with current inventory. Posting a production realization records material consumption and adds the output to finished goods inventory.

## Application Screenshots

### Dashboard
![Dashboard summary](docs/dashboard.png)

### Product Management
![Product management](docs/produk.png)

### Material Management
![Material management](docs/material.png)

### Bill of Materials
![Bill of Materials](<docs/bill of materials.png>)

### Production Planning
![Production planning](<docs/rencana produksi.png>)

### Material Requirements Planning
![Material requirements planning](<docs/kebutuhan material.png>)

## Technology Stack

- PHP 8.2+
- Laravel 12
- MySQL
- Blade templates
- Tailwind CSS 4
- Vite
- PHPUnit

## Installation

### Requirements

- PHP 8.2 or later with the required Laravel PHP extensions
- Composer
- MySQL or MariaDB
- Node.js and npm

### Set up a development database

1. Clone the repository and install the PHP dependencies:

   ```bash
   composer install
   ```

2. Create a local environment file from the example:

   ```bash
   cp .env.example .env
   ```

   On Windows PowerShell, use:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Create a MySQL database for development. The current `.env.example` uses `erp` as the database name. In phpMyAdmin, open **Databases**, enter `erp`, select the `utf8mb4_unicode_ci` collation, and create the database.

4. Configure the database settings in your new `.env` file. For example:

   ```dotenv
   APP_ENV=local
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=erp
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Use the MySQL username and password configured on your machine. Never commit `.env`.

5. Generate the application key:

   ```bash
   php artisan key:generate
   ```

6. Confirm that the connection is set to the intended development database before applying migrations. Then run:

   ```bash
   php artisan migrate
   ```

7. Install frontend dependencies and build the assets:

   ```bash
   npm install
   npm run build
   ```

8. Start the Laravel development server:

   ```bash
   php artisan serve
   ```

   For frontend development with Vite, run `npm run dev` in a separate terminal.

## Automated Tests

Run the test suite with:

```bash
php artisan test
```

The PHPUnit configuration uses an in-memory SQLite database for automated tests; a MySQL database is not required to run the test suite.

## Disclaimer

All products, materials, plans, stock balances, and transactions in this application are fictional sample data for demonstration and learning purposes. They do not represent a real manufacturer, business, or production record.
