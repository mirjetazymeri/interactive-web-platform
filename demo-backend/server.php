<?php

/*------------------------------------------------------------------
[Master Stylesheet]
==========================================================================================================================================
Project                        :   Vyzor - Laravel Bootstrap Admin Template
==========================================================================================================================================
Created Date                    :  13/February/2025
------------------------------------------------------------------------------------------------------------------------------------------
Author & Copyright Ownership   :   Spruko Technologies Private Limited 
------------------------------------------------------------------------------------------------------------------------------------------
Author URL                     :   https://themeforest.net/user/spruko
------------------------------------------------------------------------------------------------------------------------------------------
Support		                   :   https://support.spruko.com/
------------------------------------------------------------------------------------------------------------------------------------------
License Details                :   https://spruko.com/licenses-details
------------------------------------------------------------------------------------------------------------------------------------------
*/

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
