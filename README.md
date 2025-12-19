# AI Double Check

## SETUP

- Install Docker
- Clone the repo
- We are using Laravel Sail, First you need to install Laravel Sail by running 

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install
```

- `cp .env.example .env`

> You can create an alias for `./vendor/bin/sail` as `sail`

> We are using `8888` port for web interface, feel free to change it as you want in .env `APP_PORT` parameter 

- Run `./vendor/bin/sail up`
- Run `./vendor/bin/sail artisan key:generate`
- Run `./vendor/bin/sail artisan migrate`
- Run `./vendor/bin/sail artisan db:seed`
- Run `./vendor/bin/sail npm install`
- Run `./vendor/bin/sail npm run dev`
- Open the browser and navigate to `http://localhost:8888`

In production: we should always after deployment run `php artisan storage:link` 

We need to create a 2 databases inside of postgres. 
1. IN pgsql -> n8n_database
2. In pgsql_for_execution -> aidoublecheck
