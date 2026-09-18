FROM php:8.4-fpm

# På Linux slår bind mounts rått gjennom: filer containeren skriver får
# containerens uid/gid også på verten. Ved å flytte www-data til verts-
# brukerens id slipper man root-eide filer i src/. 1000 treffer de fleste
# Linux-brukere; på macOS og Windows er verdien uten betydning.
ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
    zip unzip curl libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Node + npm hentes fra det offisielle node-imaget. Samme Debian-utgave
# (trixie) som php-imaget, så ABI-en stemmer, og begge er multi-arch.
COPY --from=node:22-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-trixie-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN if [ "$(id -u www-data)" != "${UID}" ]; then \
      groupmod -o -g "${GID}" www-data \
      && usermod -o -u "${UID}" -g "${GID}" www-data; \
    fi

# www-data har /var/www som hjemmekatalog, men den er root-eid i base-imaget.
# Uten skrivetilgang her kjører Composer uten cache og npm feiler helt.
ENV COMPOSER_HOME=/var/www/.composer \
    NPM_CONFIG_CACHE=/var/www/.npm
RUN mkdir -p /var/www/.composer /var/www/.npm \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www/html
