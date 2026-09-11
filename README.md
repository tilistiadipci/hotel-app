# README #

### Requirements
* PHP 8 or higher
* MySQL database
* Composer

#### Run Project:
1. Clone the repository:
   ```
   git clone https://github.com/tilistiadipci/hotel-app.git
   ```
2. Navigate to the project folder, checkout branch main, install dependencies using Composer:
   ```
   cd hotel-app
   git checkout main
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
11. Copy lang.json.example to lang.json on folder settings
12. To using sync fitur on media library, please install ffmpeg first. Copy path like this on .env file FFPROBE_PATH=C:/ffmpeg/bin/ffprobe.exe

### Multi-hotel / SaaS

- Role `master`/`superadmin` is a platform account and uses `/superadmin/dashboard`.
- Superadmin creates hotels and hotel-admin accounts. Hotel admins can only manage operational users and data belonging to their own hotel.
- Media root and MQTT connection settings are stored in `hotel_configurations`. MQTT credentials are encrypted with `APP_KEY`.
- `MEDIA_STORAGE_PATH` and MQTT values in `.env` are migration/default fallbacks for the legacy hotel only. Runtime tenant requests use each hotel's database configuration.
- Apply the SaaS schema with `php artisan migrate`. Use a different media root for every hotel and keep `APP_KEY` stable so encrypted credentials remain readable.
