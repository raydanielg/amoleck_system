<?php

/**
 * Shared-hosting front controller shim.
 *
 * Use this ONLY when the web server's document root points at the
 * repository root (e.g. cPanel/LiteSpeed uploads) and cannot be set
 * to public/. It forwards every request to Laravel's real front
 * controller in public/index.php.
 *
 * Preferred setup: point the document root at public/ and delete
 * this file and the root .htaccess.
 */

require __DIR__.'/public/index.php';
