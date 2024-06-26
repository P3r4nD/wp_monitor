<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$jobsDir = getenv('WPM_PATH');

if ($jobsDir === false) {
    // Manejar el error: la variable de entorno no está configurada
    error_log("WPM_PATH no está configurada");
    // Lanza una excepción o establece un valor predeterminado
    throw new Exception("WPM_PATH no está configurada");
}

$jobFilePath = $jobsDir . '/jobs.txt';
$jobExecutedFilePath = $jobsDir . '/jobs_executed';


var_dump($jobFilePath, $jobExecutedFilePath);

require_once 'WPMonitorFunctions.php';
require_once 'WPMonitor.php';

$WPMonitor = new WPMonitor();
$data = $WPMonitor->readWPData();

if($data){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}
?>
