# ============================================
# Stage 1: Build Vite frontend assets
# ============================================
FROM node:22 AS frontend

WORKDIR /app

# Copy package files first for Docker layer caching
COPY package*.json ./

RUN npm install

# Copy the frontend source files
COPY resources ./resources
COPY public ./public
COPY vite.config.* ./

# Build Vite assets
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

# Copy Laravel project
COPY . .

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Copy the compiled Vite assets from the frontend stage
COPY --from=frontend /app/public/build ./public/build

# Set Laravel permissions
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]