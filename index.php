<?php

/**
 * Shared-hosting front controller.
 *
 * Used only when the web server's document root is the project root
 * instead of public/ (e.g. cPanel). All requests are forwarded to the
 * real Laravel front controller in public/ — the root .htaccess handles
 * asset paths, and this file guarantees "/" bootstraps correctly even
 * without mod_rewrite.
 */

require __DIR__.'/public/index.php';
