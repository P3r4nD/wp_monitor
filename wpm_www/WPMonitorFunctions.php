<?php

function isProtectedDirectory() {

    // Check HTTP authorization headers
    if (isset($_SERVER['HTTP_AUTHORIZATION']) || isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return true;
    }elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        return true;
    }elseif (isset($_SERVER['AUTH_TYPE'])) {
        return true;
    } else {
        return false;
    }
}

$initErrors = false;
?>
