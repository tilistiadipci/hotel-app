# README #

### Requirements
* PHP 8 or higher
* MySQL database
* Composer

#### Run Project:
1. Clone the repository:
   ```
   git clone https://adi27-admin@bitbucket.org/bioexperience/assets.git
   ```
2. Navigate to the project folder, checkout branch dev, install dependencies using Composer:
   ```
   cd assets
   git checkout dev
   composer install
   ```
3. Create a .env file by copying the contents of .env.example:
   ```
   cp .env.example .env
   ```
4. Update the .env file with your database connection details (If the database doesn't exist, create a new one database)
   ```
   DB_USERNAME= 
   DB_PASS=
   DB_PORT=
   DB_DATABASE=
   ``` 
   
5. Generate the application key:
   ```
   php artisan key:generate
   ```
6. Clear cache and save changes:
   ```
   php artisan optimize:clear
   ```
7. Run database migrations to create tables:
   ```
   php artisan migrate
   ```
8. Seed the database with dummy data:
   ```
   php artisan db:seed
   ```
9. Start the Laravel server:
   ```
   php artisan serve
   ```
10. Access the application in your browser at: `http://127.0.0.1:8000`
