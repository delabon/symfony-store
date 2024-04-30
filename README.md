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

***Now, open crypto-store.test in your browser***
