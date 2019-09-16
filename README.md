# rent collector

![Screen](/screen.png)

## Quick start with docker
```bash
docker run -v $(pwd)/.env.local:/app/.env.local --network host mrsuh/rent-collector php /app/bin/console
```

## Installation from source
```sh
sh bin/build.sh
```

## CLI
```sh
php bin/console app:explore --city=sankt-peterburg --valid-period="2 days" --search-query="снять квартиру спб" --max-valid-results=80 -vvv
php bin/console app:collect --city=sankt-peterburg --valid-period="2 weeks" -vvv
php bin/console app:consume --channel=collect -vvv
php bin/console app:consume --channel=parse -vvv
```