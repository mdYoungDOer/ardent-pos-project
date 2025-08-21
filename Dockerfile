# Multi-stage build for production
FROM node:18-alpine AS frontend-build

WORKDIR /app
COPY frontend/package*.json ./
RUN npm install --legacy-peer-deps

COPY frontend/ ./
RUN npm run build

# PHP Backend with Apache
FROM php:8.2-apache AS backend

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache
RUN a2enmod rewrite headers
COPY backend/apache.conf /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create non-root user for Composer
RUN groupadd -r appuser && useradd -r -g appuser appuser

# Set working directory
WORKDIR /var/www/html

# Copy backend files
COPY backend/ ./

# Change ownership to appuser for Composer installation
RUN chown -R appuser:appuser /var/www/html

# Install PHP dependencies as non-root user
USER appuser
RUN composer install --no-dev --optimize-autoloader

# Switch back to root for final setup
USER root

# Copy frontend build files to root of public
COPY --from=frontend-build /app/dist/* ./public/
# Copy API files (ensure they don't get overwritten)
COPY backend/public/api ./public/api

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create uploads directory
RUN mkdir -p uploads && chown -R www-data:www-data uploads

EXPOSE 80

CMD ["apache2-foreground"]
