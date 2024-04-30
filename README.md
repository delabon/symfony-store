## CryptoStore

Create products for your customers and accept crypto instead of real money.

### To test this on your local machine, follow the instruction bellow

#### Add domain to /etc/hosts (host)

```bash
sudo vi /etc/hosts
127.0.0.111  crypto-store.test
```

#### Install mkcert (host)

```bash
sudo apt install libnss3-tools
curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
chmod +x mkcert-v*-linux-amd64
sudo mv mkcert-v*-linux-amd64 /usr/local/bin/mkcert
cd ssls/
mkcert -install crypto-store.test
```

#### Up containers (host)

```bash
docker-compose up --build -d
```

#### create .env.local inside the app directory

Copy the content of .env file and paste it in .env.local and then, Add the following

```dotenv
DATABASE_URL="mysql://root:root@127.0.0.1:3306/crypto_app?serverVersion=8.3.0&charset=utf8mb4"
MAILER_DSN=smtp://mailpit:1025
```

#### Composer

```bash
composer install
```

#### Migrate database

```bash
php bin/console doctrine:migrations:migrate
```

***Now, open crypto-store.test in your browser***
