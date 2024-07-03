<?php
ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include classes
require_once 'WPMonitorFunctions.php';
require_once 'WPMonitor.php';

try {
    $wpMonitor = new WPMonitor();
    //$wpMonitor->sendMessageToTelegram("Mensaje de prueba desde WPMonitor.");
    $initError = false;
    require_once 'WPMonitorAPI.php';
} catch (Exception $e) {
    $initError = true;
    $initErrors = $e->getMessage();
}

if (!$initErrors && $WPMonitor->telegramEnabled()) {
    try {
        $wpMonitor->sendMessageToTelegram("WP Monitor iniciado correctamente");
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

if (!isset($_SESSION['first_visit_time'])) {
    $_SESSION['first_visit_time'] = date("d-m-Y H:i:s");
}
?>
<!doctype html>
<html lang="en" data-bs-theme="auto">
    <head><script src="assets/bootstrap-5.3.3/assets/color-modes.js"></script>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
        <meta name="generator" content="Hugo 0.122.0">
        <title>WP monitor</title>

        <link rel="canonical" href="https://getbootstrap.com/docs/5.3/examples/headers/">


        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@docsearch/css@3">

        <link href="assets/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

        <meta name="theme-color" content="#712cf9">


        <style>
            .bd-placeholder-img {
                font-size: 1.125rem;
                text-anchor: middle;
                -webkit-user-select: none;
                -moz-user-select: none;
                user-select: none;
            }

            @media (min-width: 768px) {
                .bd-placeholder-img-lg {
                    font-size: 3.5rem;
                }
            }

            .b-example-divider {
                width: 100%;
                height: 3rem;
                background-color: rgba(0, 0, 0, .1);
                border: solid rgba(0, 0, 0, .15);
                border-width: 1px 0;
                box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
            }

            .b-example-vr {
                flex-shrink: 0;
                width: 1.5rem;
                height: 100vh;
            }

            .bi {
                vertical-align: -.125em;
                fill: currentColor;
            }

            .nav-scroller {
                position: relative;
                z-index: 2;
                height: 2.75rem;
                overflow-y: hidden;
            }

            .nav-scroller .nav {
                display: flex;
                flex-wrap: nowrap;
                padding-bottom: 1rem;
                margin-top: -1px;
                overflow-x: auto;
                text-align: center;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }

            .btn-bd-primary {
                --bd-violet-bg: #712cf9;
                --bd-violet-rgb: 112.520718, 44.062154, 249.437846;

                --bs-btn-font-weight: 600;
                --bs-btn-color: var(--bs-white);
                --bs-btn-bg: var(--bd-violet-bg);
                --bs-btn-border-color: var(--bd-violet-bg);
                --bs-btn-hover-color: var(--bs-white);
                --bs-btn-hover-bg: #6528e0;
                --bs-btn-hover-border-color: #6528e0;
                --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
                --bs-btn-active-color: var(--bs-btn-hover-color);
                --bs-btn-active-bg: #5a23c8;
                --bs-btn-active-border-color: #5a23c8;
            }

            .bd-mode-toggle {
                z-index: 1500;
            }

            .bd-mode-toggle .dropdown-menu .active .bi {
                display: block !important;
            }
            .table-header {
                font-size:0.8rem;
            }
            .table-cell {
                font-size:0.8rem;
            }
            .table-cell-center {
                font-size:0.8rem;
                text-align: center;
            }
            .cell-danger {
                background-color:#b02a37 !important;
            }
            .table-hover {
                cursor:pointer !important;
            }
            .table-hover tr:focus {
                border:solid 1px #666666;
            }
            .nav-tabs .nav-link{
                margin-bottom: calc(-1 * var(--bs-nav-tabs-border-width));
                border-right: 1px solid #ddd;
                border-top-left-radius: 0;
                border-top-right-radius: 0;
                border-top: none;
            }
            .wpm-title {
                text-align: right;
            }
            .progress {
                border-radius:0px !important;
            }
            .help-text {
                font-size:13px;
            }
            .disallowed {
                cursor: not-allowed;
            }
        </style>
    </head>
    <body>
        <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
    <symbol id="check2" viewBox="0 0 16 16">
        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
    </symbol>
    <symbol id="circle-half" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"/>
    </symbol>
    <symbol id="moon-stars-fill" viewBox="0 0 16 16">
        <path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"/>
        <path d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.734 1.734 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.734 1.734 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.734 1.734 0 0 0 1.097-1.097l.387-1.162zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L13.863.1z"/>
    </symbol>
    <symbol id="sun-fill" viewBox="0 0 16 16">
        <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/>
    </symbol>
    <symbol id="calendar3" viewBox="0 0 16 16">
        <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
        <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
    </symbol>
    </svg>
    <div class="dropdown position-fixed bottom-0 end-0 mb-3 me-3 bd-mode-toggle">
        <button class="btn btn-bd-primary py-2 dropdown-toggle d-flex align-items-center"
                id="bd-theme"
                type="button"
                aria-expanded="false"
                data-bs-toggle="dropdown"
                aria-label="Toggle theme (auto)">
            <svg class="bi my-1 theme-icon-active" width="1em" height="1em"><use href="#circle-half"></use></svg>
            <span class="visually-hidden" id="bd-theme-text">Toggle theme</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bd-theme-text">
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" aria-pressed="false">
                    <svg class="bi me-2 opacity-50" width="1em" height="1em"><use href="#sun-fill"></use></svg>
                    Light
                    <svg class="bi ms-auto d-none" width="1em" height="1em"><use href="#check2"></use></svg>
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                    <svg class="bi me-2 opacity-50" width="1em" height="1em"><use href="#moon-stars-fill"></use></svg>
                    Dark
                    <svg class="bi ms-auto d-none" width="1em" height="1em"><use href="#check2"></use></svg>
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto" aria-pressed="true">
                    <svg class="bi me-2 opacity-50" width="1em" height="1em"><use href="#circle-half"></use></svg>
                    Auto
                    <svg class="bi ms-auto d-none" width="1em" height="1em"><use href="#check2"></use></svg>
                </button>
            </li>
        </ul>

    </div>


    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
    <symbol id="bootstrap" viewBox="0 0 118 94">
        <title>WP Monitor</title>
        <path fill-rule="evenodd" clip-rule="evenodd" d="M24.509 0c-6.733 0-11.715 5.893-11.492 12.284.214 6.14-.064 14.092-2.066 20.577C8.943 39.365 5.547 43.485 0 44.014v5.972c5.547.529 8.943 4.649 10.951 11.153 2.002 6.485 2.28 14.437 2.066 20.577C12.794 88.106 17.776 94 24.51 94H93.5c6.733 0 11.714-5.893 11.491-12.284-.214-6.14.064-14.092 2.066-20.577 2.009-6.504 5.396-10.624 10.943-11.153v-5.972c-5.547-.529-8.934-4.649-10.943-11.153-2.002-6.484-2.28-14.437-2.066-20.577C105.214 5.894 100.233 0 93.5 0H24.508zM80 57.863C80 66.663 73.436 72 62.543 72H44a2 2 0 01-2-2V24a2 2 0 012-2h18.437c9.083 0 15.044 4.92 15.044 12.474 0 5.302-4.01 10.049-9.119 10.88v.277C75.317 46.394 80 51.21 80 57.863zM60.521 28.34H49.948v14.934h8.905c6.884 0 10.68-2.772 10.68-7.727 0-4.643-3.264-7.207-9.012-7.207zM49.948 49.2v16.458H60.91c7.167 0 10.964-2.876 10.964-8.281 0-5.406-3.903-8.178-11.425-8.178H49.948z"></path>
    </symbol>
    <symbol id="home" viewBox="0 0 16 16">
        <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4H2.5z"/>
    </symbol>
    <symbol id="speedometer2" viewBox="0 0 16 16">
        <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4zM3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707zM2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10zm9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5zm.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.518z"/>
        <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A7.988 7.988 0 0 1 0 10zm8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3z"/>
    </symbol>
    <symbol id="table" viewBox="0 0 16 16">
        <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h3a1 1 0 0 0 1-1v-2zm-5 3v-3H6v3h4zm-5 0v-3H1v2a1 1 0 0 0 1 1h3zm-4-4h4V8H1v3zm0-4h4V4H1v3zm5-3v3h4V4H6zm4 4H6v3h4V8z"/>
    </symbol>
    <symbol id="people-circle" viewBox="0 0 16 16">
        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
    </symbol>
    <symbol id="grid" viewBox="0 0 16 16">
        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/>
    </symbol>
    </svg>

    <main>
        <header data-bs-theme="dark">
            <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#">WP Monitor</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <a href="#" class="d-block link-body-emphasis text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="32" fill="currentColor" class="bi bi-wordpress" viewBox="0 0 16 16">
                            <path d="M12.633 7.653c0-.848-.305-1.435-.566-1.892l-.08-.13c-.317-.51-.594-.958-.594-1.48 0-.63.478-1.218 1.152-1.218q.03 0 .058.003l.031.003A6.84 6.84 0 0 0 8 1.137 6.86 6.86 0 0 0 2.266 4.23c.16.005.313.009.442.009.717 0 1.828-.087 1.828-.087.37-.022.414.521.044.565 0 0-.371.044-.785.065l2.5 7.434 1.5-4.506-1.07-2.929c-.369-.022-.719-.065-.719-.065-.37-.022-.326-.588.043-.566 0 0 1.134.087 1.808.087.718 0 1.83-.087 1.83-.087.37-.022.413.522.043.566 0 0-.372.043-.785.065l2.48 7.377.684-2.287.054-.173c.27-.86.469-1.495.469-2.046zM1.137 8a6.86 6.86 0 0 0 3.868 6.176L1.73 5.206A6.8 6.8 0 0 0 1.137 8"/>
                            <path d="M6.061 14.583 8.121 8.6l2.109 5.78q.02.05.049.094a6.85 6.85 0 0 1-4.218.109m7.96-9.876q.046.328.047.706c0 .696-.13 1.479-.522 2.458l-2.096 6.06a6.86 6.86 0 0 0 2.572-9.224z"/>
                            <path fill-rule="evenodd" d="M0 8c0-4.411 3.589-8 8-8s8 3.589 8 8-3.59 8-8 8-8-3.589-8-8m.367 0c0 4.209 3.424 7.633 7.633 7.633S15.632 12.209 15.632 8C15.632 3.79 12.208.367 8 .367 3.79.367.367 3.79.367 8"/>
                            </svg>
                        </a>
                        <ul class="dropdown-menu text-small">
                            <li><a class="dropdown-item" href="https://www.cvedetails.com/vulnerability-list/vendor_id-2337/product_id-4096/Wordpress-Wordpress.html" target="_blank">Vulnerabilidades Wordpress en "cvedetails.com"</a></li>
                            <li><a class="dropdown-item" href="https://cve.mitre.org/cgi-bin/cvekey.cgi?keyword=wordpress" target="_blank" >Últimos registros CVE en Mitre (Wordpress)</a></li>
                            <li><a class="dropdown-item" href="https://nvd.nist.gov/vuln/search/results?form_type=Basic&results_type=overview&query=Wordpress&search_type=all&isCpeNameSearch=false" target="_blank">National Vulnerability Database (Wordpress)</a></li>
                            <li><a class="dropdown-item" href="https://www.opencve.io/cve?vendor=wordpress&product=wordpress" target="_blank">OpenCVE.IO (Wordpress)</a>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>

        <div class="progress mt-5">
            <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        <div class="container-fluid">

            <?php
            if (!isProtectedDirectory() && !$initError) {

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['protecting'])) {

                    $username = $_POST['username'];
                    $password = $_POST['password'];

                    // Genera el contenido para el archivo .htpasswd
                    $htpasswdContent = $username . ':' . password_hash($password, PASSWORD_BCRYPT);
                    $htpasswdPath = __DIR__ . '/.htpasswd';

                    // Genera el contenido para el archivo .htaccess
                    $htaccessContent = <<<EOT
                                        AuthType Basic
                                        AuthName "Protected Area"
                                        AuthUserFile $htpasswdPath
                                        Require valid-user
                                        EOT;
                    $htaccessPath = __DIR__ . '/.htaccess';

                    // Muestra las instrucciones para que el usuario cree los archivos
                    echo '<div class="container mt-5">';
                    echo '<div class="row">';
                    echo '<div class="col-md-6 offset-md-3">';
                    echo "<h2>Instrucciones para proteger el directorio</h2>";
                    echo "<p>Por favor, crea los siguientes archivos en el directorio: " . __DIR__ . "</p>";

                    echo "<h3>1. Archivo .htpasswd</h3>";
                    echo "<pre>$htpasswdContent</pre>";

                    echo "<h3>2. Archivo .htaccess</h3>";
                    echo "<pre>$htaccessContent</pre>";

                    echo "<p>Después de crear los archivos, haz clic en el siguiente enlace para verificar:</p>";
                    echo '<a href="index.php" class="btn btn-primary">He creado los archivos</a>';
                    echo "</div></div></div>";
                    // Salimos para que el usuario pueda crear los archivos
                    exit;
                }
                ?>
                <div class="container mt-5">
                    <div class="row">
                        <div class="col-md-6 offset-md-3">
                            <h2>Protección del Directorio</h2>
                            <p>Para usar WP-Monitor debes proteger el directorio donde lo vas a ejecutar. A continuación puedes configurar los datos de acceso.</p>
                            <form method="post">
                                <input type="hidden" name="protecting" value="true">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Nombre de usuario</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Proteger Directorio</button>
                            </form>
                        </div>
                        <?php echo "<pre>"; print_r($_SERVER); echo "</pre>"; ?>
                    </div>
                </div>
            <?php } elseif (isProtectedDirectory() && !$initErrors) { ?>
                <div class="table-responsive">
                    <table id="table-wp" class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th class="table-header">Site</th>
                                <th class="table-header">Name</th>
                                <th class="table-header">Unsuported PHP</th>
                                <th class="table-header">Unsuported WP</th>
                                <th class="table-header">Broken</th>
                                <th class="table-header">Infected</th>
                                <th class="table-header">Outdated PHP</th>
                                <th class="table-header">Alive</th>
                                <th class="table-header">State</th>
                                <th class="table-header">Plugins</th>
                                <th class="table-header">Version</th>
                                <th class="table-header">Status</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $WPMonitor->printTableWP();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <div class="container">
            <footer class="py-0 my-0">
                <?php
                $firstVisitTime = $_SESSION['first_visit_time'];
                $lastReloadTime = isset($_SESSION['last_reload_time']) ? $_SESSION['last_reload_time'] : "Pendiente";
                ?>
                <p class='text-center text-body-secondary help-text'><svg class="bd-placeholder-img flex-shrink-0 me-2" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: 32x32" preserveAspectRatio="xMidYMid slice" focusable="false" width="14" height="14"><title>Placeholder</title><rect width="100%" height="100%" fill="#f8d7da"></rect><text x="50%" y="50%" fill="#f8d7da" dy=".3em">32x32</text></svg>Wordpress Outdated <svg class="bd-placeholder-img flex-shrink-0 me-2" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: 32x32" preserveAspectRatio="xMidYMid slice" focusable="false" width="14" height="14"><title>Placeholder</title><rect width="100%" height="100%" fill="#b02a37" style="margin-left:20px;"></rect><text x="50%" y="50%" fill="#b02a37" dy=".3em">32x32</text></svg>Outdated or insecure Plugins or Themes <svg class="bd-placeholder-img flex-shrink-0 me-2" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: 32x32" preserveAspectRatio="xMidYMid slice" focusable="false" width="14" height="14"><title>Placeholder</title><rect width="100%" height="100%" fill="#ffc107" style="margin-left:20px;"></rect><text x="50%" y="50%" fill="#ffc107" dy=".3em">32x32</text></svg>Running Jobs</p>
                <p class="text-center help-text"><strong>Conectado</strong>: <?= $firstVisitTime; ?> <strong>Actualizado</strong>: <span id="last_updated">Undefined</p>

            </footer>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="wpExtModal" tabindex="-1" aria-labelledby="wpExtModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="wpExtModalLabel">Modal title</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body-header">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <button class="nav-link active" id="nav-wordpress-tab" data-bs-toggle="tab" data-bs-target="#nav-wordpress" type="button" role="tab" aria-controls="nav-wordpress" aria-selected="true">Wordpress</button>
                                <button class="nav-link" id="nav-plugins-tab" data-bs-toggle="tab" data-bs-target="#nav-plugins" type="button" role="tab" aria-controls="nav-plugins" aria-selected="false">Plugins</button>
                                <button class="nav-link" id="nav-themes-tab" data-bs-toggle="tab" data-bs-target="#nav-themes" type="button" role="tab" aria-controls="nav-themes" aria-selected="false">Themes</button>
                                <button class="nav-link" id="nav-ssl-tab" data-bs-toggle="tab" data-bs-target="#nav-ssl" type="button" role="tab" aria-controls="nav-ssl" aria-selected="false">SSL</button>
                                <button class="nav-link" id="nav-logs-tab" data-bs-toggle="tab" data-bs-target="#nav-logs" type="button" role="tab" aria-controls="nav-logs" aria-selected="false">Logs</button>
                            </div>
                        </nav>


                    </div>
                    <div class="modal-body">
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-wordpress" role="tabpanel" aria-labelledby="nav-wordpress-tab" tabindex="0">
                                <div id="wp-container">

                                </div>
                            </div>
                            <div class="tab-pane fade" id="nav-plugins" role="tabpanel" aria-labelledby="nav-plugins-tab" tabindex="1">
                                <table id="table-plugins" class="table table-sm table-plugins">
                                    <thead>
                                        <tr>
                                            <th class="table-header">Name</th>
                                            <th class="table-header">Slug</th>
                                            <th class="table-header">Status</th>
                                            <th class="table-header">Version</th>
                                            <th class="table-header">Update</th>
                                            <th class="table-header">Auto Updates</th>
                                            <th class="table-header">Jobs</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>

                            </div>
                            <div class="tab-pane fade" id="nav-themes" role="tabpanel" aria-labelledby="nav-themes-tab" tabindex="2">

                                <table id="table-themes" class="table table-sm table-themes">
                                    <thead>
                                        <tr>
                                            <th class="table-header">Name</th>
                                            <th class="table-header">Slug</th>
                                            <th class="table-header">Status</th>
                                            <th class="table-header">Version</th>
                                            <th class="table-header">Update</th>
                                            <th class="table-header">Auto Updates</th>
                                            <th class="table-header">jobs</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>

                            </div>
                            <div class="tab-pane fade" id="nav-ssl" role="tabpanel" aria-labelledby="nav-ssl-tab" tabindex="3">
                                <table id="table-ssl" class="table table-sm table-ssl">
                                    <thead>
                                        <tr>
                                            <th class="table-header">SSL Enabled</th>
                                            <th class="table-header">Redirected SSL</th>
                                            <th class="table-header">URL HTTPS</th>
                                            <th class="table-header">Certifcate SSL</th>
                                            <th class="table-header">Self Signed</th>
                                            <th class="table-header">Updated</th>
                                            <th class="table-header">Issuer</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="nav-logs" role="tabpanel" aria-labelledby="nav-logs-tab" tabindex="4">
                                <form id="log-form" class="row row-cols-lg-auto g-3 align-items-center">
                                    <input id="wp_action" type="hidden" name="wp_action" value="get_logs">
                                    <input id="url_domain" type="hidden" name="url_domain" value="">
                                    <div class="col-12">
                                        <label class="visually-hidden" for="time_frame">Preference</label>
                                        <select class="form-select" id="time_frame" name="time_frame">
                                            <option selected>Log time...</option>
                                            <?php $WPMonitor->writeTimesMenu(); ?>
                                        </select>

                                    </div>

                                    <div class="col-12">
                                        <label class="visually-hidden" for="log_pattern">Search</label>
                                        <div class="input-group">
                                            <div class="input-group-text">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"></path>
                                                </svg>
                                            </div>
                                            <input type="text" class="form-control" id="log_pattern" name="log_pattern" placeholder="Search in logs">
                                        </div>
                                    </div>



                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="regexCheck" name="regexCheck">
                                            <label class="form-check-label" for="regexCheck">
                                                Regex
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button id="submit-log-search" type="submit" class="btn btn-primary">
                                            <div id="btn-submit" class="">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"></path>
                                                </svg>
                                                Submit
                                            </div>
                                            <div id="btn-searching" class="d-none">
                                                <span class="spinner-grow spinner-grow-sm" aria-hidden="true"></span>
                                                <span role="status">Searching...</span>
                                            </div>
                                        </button>
                                    </div>
                                </form>


                                <table id="table-logs" class="table table-sm table-logs">

                                    <tbody>

                                    </tbody>
                                </table>
                                <div class="bd-example-snippet bd-code-snippet">
                                    <div class="bd-example m-0 border-0">

                                        <div id="msg-logs" class="alert alert-secondary d-none" role="alert">
                                            No logs for "Last 5 minutes" period time
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input id="wp-install-id" name="wp-install-id" type="hidden" value="">
                        <button id="save-jobs" type="button" class="btn btn-primary btn-sm d-none">Save jobs</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="warningModalLabel">Warning</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?= $WPMonitor->translate("This Wordpress installation is running jobs, wait for it to finish to add new jobs."); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <script src="assets/bootstrap-5.3.3/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <?php if ($initErrors) { ?>
        <div class="container mt-5">
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <p class="text-center"><?= $initErrors; ?></p>
                </div>
            </div>
        </div>

    <?php } ?>
    <?php if (isProtectedDirectory()) { ?>
        <script>
            var app_url = "<?php echo $wpMonitor->getAppURL(); ?>/api";
            var app_reload_time = "<?php echo $wpMonitor->getReloadTime(); ?>";</script>
        <script src="assets/wpm.js"></script>
    <?php } ?>

</body>
</html>
