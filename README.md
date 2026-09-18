# Laravel i container — Nginx + PHP-FPM + MySQL

Et lett skjelett for å komme raskt i gang med Laravel i Docker. Repoet inneholder
**kun konfigurasjon** — ingen applikasjonskode. Laravel installeres inn i `src/`
første gang du starter, og `src/` er bevisst holdt utenfor versjonskontroll.

| Tjeneste | Image / bygg      | Host          | Internt   |
| -------- | ----------------- | ------------- | --------- |
| `nginx`  | `nginx:stable-alpine` | `8000`    | `80`      |
| `app`    | `php:8.2-fpm`     | –             | `9000`    |
| `db`     | `mysql:8.0`       | `3307`        | `3306`    |

## Kom i gang

```bash
git clone https://github.com/jbpettersen/laravel-docker-universell.git minapp
cd minapp
docker compose up -d --build
docker compose exec app composer create-project laravel/laravel .
```

Appen ligger nå på **<http://localhost:8000>**.

> `composer create-project` krever at `src/` er tom, så steget kjøres bare
> første gang. Har du allerede en app, legg den i `src/` og hopp over steget.

## Bytt fra SQLite til MySQL

Laravel installeres med SQLite som standard. MySQL-containeren kjører allerede —
slik kobler du appen til den:

**1.** Rediger `src/.env`:

```ini
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=root
```

`DB_HOST=db` er tjenestenavnet i compose-nettverket, og `DB_PORT` er den
**interne** porten `3306` — ikke `3307`. 3307 gjelder kun når du kobler til
utenfra, f.eks. fra TablePlus eller DBeaver på host-maskinen.

**2.** Tøm config-cachen og kjør migrasjonene på nytt:

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan migrate:fresh
```

**3.** Bekreft at tabellene havnet i MySQL:

```bash
docker compose exec db mysql -uroot -proot -e "USE laravel; SHOW TABLES;"
```

Vil du beholde SQLite i stedet, kan `db`-tjenesten trygt fjernes fra
`docker-compose.yml`.

## Daglig bruk

```bash
docker compose up -d                                  # start
docker compose down                                   # stopp
docker compose logs -f app                            # følg logger
docker compose exec app bash                          # shell i PHP-containeren

docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app composer require <pakke>
```

### Frontend-assets

Node er ikke med i PHP-imaget. Bygg assets på host-maskinen:

```bash
cd src && npm install && npm run build     # eller: npm run dev
```

## Porter i bruk?

Host-portene kan overstyres uten å endre `docker-compose.yml` — lag en `.env`
i repo-roten (ikke i `src/`):

```ini
APP_PORT=8080
DB_PORT=3310
```

MySQL er lagt på **3307** fra start nettopp fordi 3306 ofte er opptatt av en
lokalt installert MySQL.

## Godt å vite

- **`src/` er gitignorert.** Repoet skal forbli et rent skjelett. Skal appen din
  i versjonskontroll, opprett et eget repo inne i `src/`, eller fjern
  `src`-linjene fra `.gitignore` i din egen fork.
- **`.env` blir aldri committet** — `.gitignore` blokkerer den, men beholder
  `.env.example`.
- **PHP-FPM kjører som root**, så filer appen skriver får root-eierskap på
  host-volumet. Greit lokalt; bytt til en ikke-privilegert bruker i
  `docker/php/Dockerfile` før dette brukes noe annet sted enn på utviklermaskin.
- **MySQL-passordet er `root`/`root`** og databasen heter `laravel`. Kun ment for
  lokal utvikling.
- **Data overlever `docker compose down`** — MySQL lagrer i volumet `dbdata`.
  Vil du nullstille databasen helt: `docker compose down -v`.

## PHP-utvidelser

Bygget med `pdo_mysql`, `pdo_sqlite`, `mbstring`, `exif`, `pcntl`, `bcmath`,
`gd`, `zip` og OPcache. Trenger du flere, legg dem til i
`docker/php/Dockerfile` og kjør `docker compose build app`.
