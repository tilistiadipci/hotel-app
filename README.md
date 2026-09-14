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
11. To use the media library sync feature, install ffmpeg and set its ffprobe path in `.env`, for example `FFPROBE_PATH=C:/ffmpeg/bin/ffprobe.exe`.

### Multi-hotel / SaaS

- Role `master`/`superadmin` is a platform account and uses `/superadmin/dashboard`.
- Superadmin creates hotels and hotel-admin accounts. Hotel admins can only manage operational users and data belonging to their own hotel.
- Each hotel's unique relative media folder is stored in `hotel_configurations.media_root`. Its physical path is always `MEDIA_STORAGE_PATH + media_root`. MQTT credentials are encrypted with `APP_KEY`.
- Hotel API clients send the hotel code in `X-Hotel-Code` and the secret license key in `X-Hotel-License`. The plain key is shown only once; only its hash is stored.
- `MEDIA_STORAGE_PATH` is the single physical media root for every hotel. The application automatically creates a unique subfolder from the hotel name.
- Each hotel's default dashboard language is stored in the tenant-scoped `settings.default_language` row and loaded into the session when hotel staff log in.
- Apply the SaaS schema with `php artisan migrate` and keep `APP_KEY` stable so encrypted MQTT credentials remain readable.
