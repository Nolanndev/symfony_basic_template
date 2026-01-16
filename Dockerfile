FROM php:8.4-apache

ARG UID=1000
ARG GID=1000

# Config Apache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

# Créer un utilisateur non-root
RUN groupadd -g $GID symfony && \
    useradd -u $UID -g symfony -m -s /bin/bash symfony && \
    sed -i "s/www-data/symfony/g" /etc/apache2/envvars

# Installer dépendances système minimales (+ sudo)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    zip\
    unzip \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libxslt-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    libpq-dev \
    librabbitmq-dev \
    curl \
    ca-certificates \
    sudo \
    && rm -rf /var/lib/apt/lists/*


# Installer extensions PHP nécessaires
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install intl pdo pdo_pgsql zip opcache xsl gd

# Installer et activer AMQP
RUN pecl install amqp && docker-php-ext-enable amqp

# Timezone
RUN echo "date.timezone=Europe/Paris" > /usr/local/etc/php/conf.d/timezone.ini

# Installer Composer depuis l’image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installer le CLI Symfony
RUN curl -sS https://get.symfony.com/cli/installer | bash

# Installer Node + Yarn
RUN apt-get update && apt-get install -y --no-install-recommends \
    nodejs \
    npm \
    && npm install -g yarn \
    && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*

# Configuration PHP
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 120M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = -1" >> /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "max_execution_time = 5000000" >> /usr/local/etc/php/conf.d/max-execution-time.ini

# Alias pratiques
RUN echo "alias bc='php bin/console'" >> /home/symfony/.bashrc && \
    echo "alias bcc='php bin/console cache:clear'" >> /home/symfony/.bashrc && \
    echo "alias bcce='php bin/console cache:clear -e prod'" >> /home/symfony/.bashrc && \
    echo "alias ci='composer install'" >> /home/symfony/.bashrc && \
    echo "alias libinstall='composer install && yarn install'" >> /home/symfony/.bashrc && \
    echo "alias yw='yarn watch'" >> /home/symfony/.bashrc && \
    echo "alias l='ls -al'" >> /home/symfony/.bashrc && \
    echo "alias gs='git status'" >> /home/symfony/.bashrc && \
    echo "alias gm='git commit'" >> /home/symfony/.bashrc && \
    echo "alias gp='git pull'" >> /home/symfony/.bashrc && \
    chown symfony:symfony /home/symfony/.bashrc

# Installation docker-cli
RUN apt-get update && apt-get install -y docker-cli && rm -rf /var/lib/apt/lists/*

# Donner l'accès au socket Docker à l'utilisateur qui exécute Apache/PHP (symfony)
RUN groupadd -for -g 20 docker && usermod -aG docker symfony

# Autoriser symfony et www-data à utiliser docker via sudo sans mot de passe
RUN echo 'symfony ALL=(ALL) NOPASSWD: /usr/bin/docker' > /etc/sudoers.d/symfony-docker \
    && echo 'www-data ALL=(ALL) NOPASSWD: /usr/bin/docker' > /etc/sudoers.d/www-data-docker \
    && chmod 440 /etc/sudoers.d/symfony-docker /etc/sudoers.d/www-data-docker

WORKDIR /var/www/html
COPY . .

EXPOSE 80

USER symfony
