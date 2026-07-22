# ============================================
# Stage 1: Build Vue + Inertia frontend assets
# ============================================
FROM node:22 AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.* ./

RUN npm run build


# ============================================
# Stage 2: Laravel + FrankenPHP
# ============================================
FROM dunglas/frankenphp:php8.4

WORKDIR /app

RUN install-php-extensions \
    pdo_pgsql \
    mbstring \
    bcmath \
    intl \
    zip \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

# Create Laravel cache directories BEFORE composer install
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Copy compiled Vue/Inertia/Vite assets
COPY --from=frontend /app/public/build ./public/build

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]