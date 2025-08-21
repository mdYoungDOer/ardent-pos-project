# Multi-stage build for production
FROM node:18-alpine AS frontend-build

WORKDIR /app
COPY frontend/package*.json ./
RUN npm install --legacy-peer-deps

COPY frontend/ ./
# Set environment variables for build
ENV VITE_API_URL=/api
RUN npm run build

# Debug: List the build output
RUN ls -la dist/
RUN ls -la dist/assets/ || echo "No assets directory found"

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

# Configure Apache - enable modules properly
RUN a2enmod rewrite headers mime
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
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Switch back to root for final setup
USER root

# Copy frontend build files to public directory
COPY --from=frontend-build /app/dist/* ./public/

# Debug: List what was copied
RUN ls -la public/
RUN ls -la public/assets/ || echo "No assets directory found in public"

# Ensure API directory exists and copy API files
RUN mkdir -p ./public/api
COPY backend/public/api ./public/api

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create uploads directory
RUN mkdir -p uploads && chown -R www-data:www-data uploads

# Create a simple health check file
RUN echo '<?php echo json_encode(["status" => "ok", "timestamp" => date("c")]); ?>' > ./public/health.php

# Test Apache configuration
RUN apache2ctl configtest

EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

CMD ["apache2-foreground"]
