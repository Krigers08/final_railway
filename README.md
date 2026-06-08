# Northwind PHP App - Railway Deployment

## Setup steps

1. Push this folder to a GitHub repo
2. In Railway, create a new project from that repo
3. Add a Postgres service if you haven't already
4. Railway auto-injects DATABASE_URL into your app - no manual config needed
5. Add one env var: IMPORT_SECRET=some_random_string (used to protect setup endpoints)

## First deploy steps

After the app goes live:

1. Visit https://your-app.railway.app/setup.php?secret=YOUR_SECRET
   This creates all the database tables.

2. Visit https://your-app.railway.app/import.php?secret=YOUR_SECRET
   This loads all the CSV data. It may take 30-60 seconds (large Order Details file).

3. Visit https://your-app.railway.app to use the app.

## File structure

- index.php       Main app (dashboard, customers, products, orders, employees, suppliers)
- db.php          DB connection (reads DATABASE_URL or PG* env vars)
- schema.sql      PostgreSQL table definitions
- setup.php       Creates tables (run once)
- import.php      Loads CSV data (run once)
- csv/            All Northwind CSV data files
- nixpacks.toml   Tells Railway which PHP extensions to install
- railway.json    Railway deploy config
