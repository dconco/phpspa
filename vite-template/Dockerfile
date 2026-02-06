# syntax=docker/dockerfile:1

# ---- Stage 1: Node build (Vite assets) ----
FROM node:20-slim AS node-build

WORKDIR /app

# Enable pnpm via Corepack (bundled with Node)
RUN corepack enable

# Copy project and build assets
COPY . .

# Install deps (use lockfile if present)
RUN if [ -f pnpm-lock.yaml ]; then pnpm install --frozen-lockfile; else pnpm install; fi
RUN pnpm build


# ---- Stage 2: Composer deps (on PHP 8.4) ----
FROM php:8.4-cli AS composer-build

WORKDIR /app

# Tools Composer commonly needs for dist installs
RUN apt-get update \
	&& apt-get install -y --no-install-recommends git unzip libzip-dev \
	&& docker-php-ext-install zip \
	&& rm -rf /var/lib/apt/lists/*

# Copy Composer binary (no need to curl installer)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader


# ---- Stage 3: Final runtime (PHP 8.4) ----
FROM php:8.4-cli

WORKDIR /var/www/html

ENV APP_ENV=production

# Copy source (excluding node_modules/vendor via .dockerignore)
COPY . .

# Bring in production vendor/ + built assets
COPY --from=composer-build /app/vendor ./vendor
COPY --from=node-build /app/public/assets ./public/assets

EXPOSE 8000

# Use index.php as the router script (SPA-friendly)
CMD ["php", "-S", "0.0.0.0:8000", "index.php"]