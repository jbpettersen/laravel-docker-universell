# Laravel i container — Nginx + PHP-FPM + MySQL

Et lett skjelett for å komme raskt i gang med Laravel i Docker. Repoet inneholder
**kun konfigurasjon** — ingen applikasjonskode. Laravel installeres inn i `src/`
første gang du starter, og `src/` er bevisst holdt utenfor versjonskontroll.

| Tjeneste | Innhold | Host | Internt |
| -------- | ------- | ---- | ------- |
| `nginx`  | `nginx:stable-alpine` | `8000` | `80` |
| `app`    | PHP 8.2-FPM, Composer, Node 22 + npm | – | `9000` |
| `db`     | `mysql:8.0` | `3307` | `3306` |

Alt bygges for vertens egen arkitektur — både `amd64` og `arm64` (Apple Silicon)
kjører nativt, uten emulering.

## Kom i gang

```bash
git clone https://github.com/jbpettersen/laravel-docker-universell.git minapp
cd minapp
```

**På Linux**, kjør dette først, slik at filene containeren lager tilhører deg:

```bash
printf 'UID=%s\nGID=%s\n' "$(id -u)" "$(id -g)" >> .env
```

Så, på alle plattformer:

```bash
docker compose up -d --build
docker compose exec -u www-data app composer create-project laravel/laravel .
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
docker compose exec -u www-data app php artisan config:clear
docker compose exec -u www-data app php artisan migrate:fresh
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
docker compose exec -u www-data app bash              # shell i PHP-containeren

docker compose exec -u www-data app php artisan migrate
docker compose exec -u www-data app php artisan test
docker compose exec -u www-data app composer require <pakke>
```

`-u www-data` gjør at filer som opprettes tilhører deg og ikke root. På macOS og
Windows spiller det ingen rolle, men det er gratis å ha med, og på Linux er det
forskjellen på et prosjekt du kan slette og ett som krever `sudo`.

### Frontend-assets

Node og npm ligger i `app`-containeren, så du trenger ingen lokal
Node-installasjon:

```bash
docker compose exec -u www-data app npm install
docker compose exec -u www-data app npm run build     # eller: npm run dev
```

## Porter i bruk?

Host-portene kan overstyres uten å endre `docker-compose.yml` — legg dem i en
`.env` i repo-roten (ikke i `src/`):

```ini
APP_PORT=8080
DB_PORT=3310
```

MySQL er lagt på **3307** fra start nettopp fordi 3306 ofte er opptatt av en
lokalt installert MySQL.

## Filrettigheter på Linux

På macOS og Windows oversetter Docker Desktop eierskap gjennom VM-laget, så
dette er et ikke-problem. På Linux slår bind mounts rått gjennom: uid-en inne i
containeren blir uid-en på verten.

Derfor bygges `app`-imaget med `UID`/`GID` som byggeargumenter, og `www-data`
flyttes til de id-ene. Standardverdien er `1000`, som er første vanlige bruker på
de fleste Linux-systemer — har du den, virker det uten konfigurasjon. Ellers
legger du dine egne verdier i `.env` som vist under «Kom i gang», og bygger med
`docker compose build`.

## Godt å vite

- **`src/` er gitignorert.** Repoet skal forbli et rent skjelett. Skal appen din
  i versjonskontroll, opprett et eget repo inne i `src/`, eller fjern
  `src`-linjene fra `.gitignore` i din egen fork.
- **`.env` blir aldri committet** — `.gitignore` blokkerer den, men beholder
  `.env.example`.
- **php-fpm sin master-prosess kjører som root**, slik den må for å kunne slippe
  rettigheter ned til poolen. Selve poolen — og dermed alt appen skriver —
  kjører som `www-data`. Før dette brukes andre steder enn på en
  utviklermaskin, bør resten av imaget gås etter i sømmene.
- **MySQL-passordet er `root`/`root`** og databasen heter `laravel`. Kun ment for
  lokal utvikling.
- **Data overlever `docker compose down`** — MySQL lagrer i volumet `dbdata`.
  Vil du nullstille databasen helt: `docker compose down -v`.
- **Windows:** bruk WSL2, og legg prosjektet inne i WSL2-filsystemet. Ligger det
  under `/mnt/c/`, blir I/O merkbart tregere.

## PHP-utvidelser

Bygget med `pdo_mysql`, `pdo_sqlite`, `mbstring`, `exif`, `pcntl`, `bcmath`,
`gd`, `zip` og OPcache. Trenger du flere, legg dem til i
`docker/php/Dockerfile` og kjør `docker compose build app`.
