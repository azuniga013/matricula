FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM dunglas/frankenphp:php8.2-bookworm

RUN install-php-extensions gd intl pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

COPY --from=assets /app/public/build /app/public/build
COPY Caddyfile /etc/caddy/Caddyfile

RUN chown -R www-data:www-data storage bootstrap/cache

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
