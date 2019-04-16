# WordPress Git Installer
A command line tool for setting up WordPress Sites for development. Inspired by the Laravel Installer.


First, download the installer using Composer:

    composer global require niklasdahlqvist/wordpress-git-installer

Make sure to place composer's system-wide vendor bin directory in your `$PATH` so the wordpress-git-installer executable can be located by your system. This directory exists in different locations based on your operating system; however, some common locations include:

- macOS: `$HOME/.composer/vendor/bin`
- GNU / Linux Distributions: `$HOME/.config/composer/vendor/bin`
- Windows: `%USERPROFILE%\AppData\Roaming\Composer\vendor\bin`



Once installed, the `wp-git-installer new` command will create a fresh WordPress installation in the directory you specify. For instance, `wp-git-installer new blog` will create a directory named `blog` containing a fresh WordPress installation:

    wp-git-installer new blog
