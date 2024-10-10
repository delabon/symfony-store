## About

Sell your digital goods and accept payments securely on your site.

[Limited demo](https://symfony-store.delabon.com/)

### Key features

- Accept Payments (Stripe)
- Fraud detection (FraudLabs Pro)
- Cart
- Receipt
- Refund
- Order management
- Registration
- User management 
- Product management
- Product search
- Category management
- Page management
- Secure
- Emails

### Tech stack

- PHP 8.2
- Symfony 7 (Doctrine & Twig)
- MySQL 8.3
- Nginx
- Docker
- Javascript
- CSS/SASS/Bootstrap 5.3

### To test this on your local machine, follow the instructions bellow

#### Up containers (host)

```bash
docker compose up -d
```

#### create .env.local inside the app directory

Copy the content of .env file and paste it in .env.local and then, Add the following

```dotenv
cp app/.env app/.env.local
DATABASE_URL="mysql://root:root@mysql-service:3306/my_store?serverVersion=8.3.0&charset=utf8mb4"
MAILER_DSN=smtp://mailpit:1025
```

#### create uploads folder inside app/public

```dotenv
mkdir app/public/uploads
```

#### Install composer packages

```bash
docker compose exec php-service composer install
```

#### Migrate database

```bash
docker compose exec php-service php bin/console doctrine:migrations:migrate
```

#### Load fixtures

```bash
docker compose exec php-service php bin/console doctrine:fixtures:load -n
```

#### Install node modules

Open a new terminal and run the following command

```bash
docker compose run --rm node-service npm install
docker compose run --rm node-service npm run build
```

#### Bowser

Now, open http://localhost:8011 in your browser

#### Mailpit

To see the emails sent by the app, open http://localhost:8025/ in your browser

#### PHPMyAdmin

To see the database, open http://localhost:8080/ in your browser
