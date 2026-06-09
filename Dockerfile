# Fetch the Castle browser SDK from npm (served at runtime from node_modules).
FROM node:20-slim AS frontend
WORKDIR /app
COPY package.json ./
RUN npm install --omit=dev --no-audit --no-fund

FROM php:8.2-cli

WORKDIR /app

# git/unzip for Composer; libcurl for the SDK's cURL transport.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json ./
RUN composer install --no-dev --no-interaction --no-progress

COPY . .
COPY --from=frontend /app/node_modules ./node_modules

ENV location=docker
ENV PORT=80

# Only the Castle credentials are needed at runtime (e.g. docker run -e ...);
# the simulated demo user values are baked in as code defaults.

EXPOSE 80

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} router.php"]
