<?php

require_once 'WPMonitor.php';

$WPMonitor = new WPMonitor();

// Función para leer trabajos existentes




// Verificar si la solicitud es un POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['protecting'])) {
        // Leer el JSON enviado en la solicitud
        $postData = file_get_contents('php://input');

        // Convertir el JSON a un array asociativo
        $data = json_decode($postData, true);
        $errors = [];

        if (!isset($data['wp_action']) || !$WPMonitor->validateWPAction($data['wp_action'])) {
            $errors[] = 'Invalid or missing wp_action.';
        }

        if ($data['wp_action'] === 'get_logs') {

            // Validar url_domain
            if (!isset($data['url_domain']) || !$WPMonitor->validateUrlDomain($data['url_domain'])) {
                $errors[] = 'Invalid or missing url_domain.';
            }

            // Verificar si hay errores
            if (!empty($errors)) {
                // Enviar respuesta con errores
                http_response_code(400); // Bad Request
                echo json_encode(['errors' => $errors]);
                exit;
            }

            $json_data['logs'] = $WPMonitor->readWPLogs($data['url_domain'], $data['time_frame']);

            if(!empty($json_data['logs']) && !empty($data['log_pattern'])){

                $logs_mix = [];

                foreach ($json_data['logs'] AS $line){

                    if(str_contains($line, $data['log_pattern'])){

                        $logs_mix[] = $line;

                    }

                }

                if(empty($logs_mix)){

                    $json_data['logs_msg'] = $WPMonitor->getDefaultMsgTime($data['time_frame'], $data['log_pattern']);
                    $json_data['logs'] = $logs_mix;

                    echo json_encode($json_data);
                    die;

                }

                $json_data['logs'] = $logs_mix;
                echo json_encode($json_data);
                die;

            }

            if (empty($json_data['logs'])) {
                $json_data['logs_msg'] = $WPMonitor->getDefaultMsgTime($data['time_frame']);
            }


            echo json_encode($json_data);
            //{"action_log":"1","time_frame":"last_5_minutes","log_pattern":"helo","regexCheck":"on"}

            //echo json_encode($data);
            exit;
            die;
        }

        if ($data['wp_action'] === "do_jobs"){
            //{"wp_action":"do_jobs",
            //"wp_id":"4",
            //"wp_update":true,
            //"plugins":[{"name":"akismet","action":"update"},{"name":"all-in-one-seo-pack","action":"update"}],
            //"wp_theme":"twentytwenty"}
            // Validar wp_id
            if (!isset($data['wp_id']) || !$WPMonitor->validateID($data['wp_id'])) {
                $errors[] = 'Invalid or missing wp_id.';
            }

            // Validar wp_update
            if (!isset($data['wp_update']) || !$WPMonitor->validateBool($data['wp_update'])) {
                $errors[] = 'Invalid or missing wp_update.';
            }

            // Validar plugins
            if (!isset($data['plugins']) || !is_array($data['plugins'])) {
                $errors[] = 'Invalid or missing plugins.';
            } else {
                foreach ($data['plugins'] as $plugin) {
                    if (!isset($plugin['name']) || !$WPMonitor->validateName($plugin['name'])) {
                        $errors[] = 'Invalid plugin name.';
                    }
                    if (!isset($plugin['action']) || !$WPMonitor->validateAction($plugin['action'])) {
                        $errors[] = 'Invalid plugin action.';
                    }
                }
            }

            // Validar wp_theme
            if (isset($data['wp_theme']) && $data['wp_theme']!= false && !$WPMonitor->validateName($data['wp_theme'])) {
                $errors[] = 'Invalid wp_theme.';
            }

            // Verificar si hay errores
            if (!empty($errors)) {
                // Enviar respuesta con errores
                http_response_code(400); // Bad Request
                echo json_encode(['errors' => $errors]);
                exit;
            }

            // Formatear los trabajos
            $jobs = [];
            if ($data['wp_update']) {
                $jobs[] = "update_wp " . $data['wp_id'];
            }

            foreach ($data['plugins'] as $plugin) {
                $jobs[] = "{$plugin['action']}_plugin " . $data['wp_id'] . " " . $plugin['name'];
            }

            if (!empty($data['wp_theme'])) {
                $jobs[] = "update_template " . $data['wp_id'] . " " . $data['wp_theme'];
            }
            $result_jobs = [];
            // Agregar cada trabajo
            foreach ($jobs as $job) {
                $jobObj = new stdClass();
                $jobObj->job = $job;
                $jobObj->result = $WPMonitor->addJob($job);
                $result_jobs[] = $jobObj;
            }
            $success = true;
            foreach ($result_jobs AS $job){

                if ($job->result !== true) {
                    $success = false;
                }
            }
            echo json_encode(["success" => $success, $result_jobs]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (isset($_GET['id'])) {


        $id = $_GET['id'];

        $file = $WPMonitor->getDataPath() . "/" . $id . ".json";

        if (file_exists($file)) {

            $json = file_get_contents($file);
            $json_data = json_decode($json, true);
            $json_data['logs'] = $WPMonitor->readWPLogs($json_data['siteUrl']);

            if (empty($json_data['logs'])) {
                $json_data['logs_msg'] = $WPMonitor->getDefaultMsgTime();
            }

            echo json_encode($json_data);
        }

        return False;
    }

    if (isset($_GET['update']) && $_GET['update'] === "true") {

        $_SESSION['last_reload_time'] = date("Y-m-d H:i:s");

        $WPMonitor->printTableWP();
    }
}
