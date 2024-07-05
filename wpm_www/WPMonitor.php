<?php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Direct access not permitted');
}

/**
 * Class to manage wp-toolkit operations.
 *
 * This class provides methods to interact with wp-toolkit data

 *
 * @package WPMonitor
 */
class WPMonitor {
    /**
     * @file JSON Config file
     */
    const CONFIG_FILE = '/config.json';

    /**
    * $initStatus
    * @var string $appLocale         App locale config
    * @var string $appLocaleMessages App locale messages
    * @var string $appMessagesDir    App locale messages dir
    * @var string $appData           Path to application data directory
    * @var string $appPath           Path to application directory
    * @var string $appUrl            URL of the application
    * @var int    $appReloadTime     Application reload interval in seconds
    * @var string $logFilesPath      Path to log files directory
    * @var string $jobsFile          Path to jobs file
    * @var string $jobsExecutedFile  Path to executed jobs file
    * @var bool   $useCurl           Flag to use cURL for HTTP requests
    * @var string $defaultRangeType  Default range type for logs (e.g., 'last_24_hours')
    * @var string $telegramToken     Telegram bot token
    * @var string $telegramChatId    Telegram chat ID
    * @var bool   $telegramEnabled   Flag to enable/disable Telegram notifications
    */
    
    protected $initStatus;
    protected $appLocale;
    protected $appLocaleMessages;
    protected $appMessagesDir;
    protected $appData;
    protected $appPath;
    protected $appUrl;
    protected $appReloadTime;
    protected $logFilesPath;
    protected $jobsFile;
    protected $jobsExecutedFile;
    protected $useCurl;
    protected $defaultRangeType;
    protected $telegramToken;
    protected $telegramChatId;
    protected $telegramEnabled;


    /**
    * WPMonitor class constructor.
    *
    * Initializes config.
    *
    */
    public function __construct() {
        
        // Default init status
        $this->initStatus = True;
        
        // Default lang
        $this->appLocale = "es_ES";
        
        // Load config
        $this->loadConfig();
    }
    /**
     * Set all config values
     *
     */
    protected function loadConfig() {
        
        $this->setDefaultLocale($this->appLocale);
        
        $wpm_path = getenv('WPM_PATH');
        
        // If the environment variable is not defined, try loading from an .env file
        if (!$wpm_path) {
            $env_file = __DIR__ . '/.env';
            if (file_exists($env_file)) {
                $env_content = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($env_content as $line) {
                    if (strpos(trim($line), 'WPM_PATH=') === 0) {
                        $wpm_path = trim(str_replace('WPM_PATH=', '', $line));
                        break;
                    }
                }
            }
        }
        
        if (!$wpm_path) {
            $this->initStatus = False;
            throw new Exception($this->translate("The environment variable [WPM_PATH] is not defined. Make sure you do it."));
        }
        
        /**
         * Path to application directory
         *
         */
        $this->appPath = $wpm_path ?? null;
        
        // Check if the application path is configured.
        if (empty($this->appPath) || !$this->appPath || @!file_exists($this->appPath)) {
            $this->initStatus = False;
            throw new Exception($this->translate("The [app_path] is not set correctly. Make sure the directory you defined in the [WPM_PATH] environment variable exists."));    
        }
        
        $configPath = $wpm_path . self::CONFIG_FILE;
        
        if (!file_exists($configPath)) {
            $this->initStatus = False;
            throw new Exception($this->translate("The configuration file [config.json] does not exist."));
        }
        
        $configData = file_get_contents($configPath);
        $config = json_decode($configData, true);
        
        // Check for json errors in [config.json]
	if (json_last_error() !== JSON_ERROR_NONE) {
            $this->initStatus = False;
            $msg_error = $this->translate("Error decoding file [config.json]: %s");
            throw new Exception(sprintf($msg_error, json_last_error_msg()));
        }

        /**
        * Path to the application data directory.
        */
        $this->appData = $this->appPath.$config['app_data'] ?? null;
        
        // Check if the data application path is configured or exist.
        if (!is_dir($this->appData)) {
            $this->initStatus = False;
            throw new Exception($this->translate("Data directory [app_data] does not exist. Make sure it is the same one you configured in your bash script and modify [app_data] in [config.json]."));
        }
        
        /**
        * URL of the application.
        */
        $this->appUrl = $config['app_url'] ?? null;
        
        // Current url server
        $current_url = $this->getCurrentUrl();
        
        // Check if the app url is configured and is the same than server.
        if (empty($this->appUrl) || $this->appUrl === 'http://127.0.0.2:8001/' || $current_url != $this->appUrl) {
            $this->initStatus = False;
            $msg_error = $this->translate("The URL '%s' in [app_url] is not responding or is not configured correctly in the config.json file.");
            throw new Exception(sprintf($msg_error, $this->appUrl));
        }
        
        /**
        * Reload time for the application, in milliseconds.
        */
        $this->appReloadTime = $config['app_reload_time'] ?? 60000;

        /**
        * Path to the log files directory.
        */
        $this->logFilesPath = $this->appData.$config['log_files'];
        
        // Check if dir log is configured.
        if (!is_dir($this->logFilesPath)) {
            $this->initStatus = False;
            $msg_error = $this->translate("The log directory '%s' for does not exist. Make sure it's the same one you configured in your bash script and modify [log_files] in [config.json].");
            throw new Exception(sprintf($msg_error, $this->logFilesPath));
        }
        
        /**
        * Path to the jobs file.
        */
        $this->jobsFile = $this->appPath."/".$config['jobs_file'] ?? null;
        
        // Check if the jobs file is configured.
        if (!file_exists($this->jobsFile)) {
            $this->initStatus = False;
            $msg_error = $this->translate("The jobs file '%s' does not exist. Make sure it is inside the directory you defined in [WPM_PATH] and modify [jobs_file] in [config.json].");
            throw new Exception(sprintf($msg_error, $this->jobsFile));
        }
        
        /**
        * Path to the executed jobs file.
        */
        $this->jobsExecutedFile = $this->appPath."/".$config['jobs_executed_file'] ?? null;
        
        // Check if the jobs executed file is configured.
        if (!file_exists($this->jobsExecutedFile)) {
            $this->initStatus = False;
            $msg_error = $this->translate("The jobs executed file '%s' does not exist. Make sure it is inside the directory you defined in [WPM_PATH] and modify [jobs_executed_file] in [config.json].");
            throw new Exception(sprintf($msg_error, $this->jobsExecutedFile));
        }
        
        /**
        * Default range type for logs.
        */
        $this->defaultRangeType = $config['default_range_type'] ?? 'last_24_hours';

        /**
        * Telegram bot token.
        */
        $this->telegramToken = $config['telegram_token'] ?? null;

        /**
        * Telegram chat ID.
        */
        $this->telegramChatId = $config['telegram_chat_id'] ?? null;

        /**
        * Whether Telegram notifications are enabled.
        */
        $this->telegramEnabled = $config['telegram_enabled'] ?? null;
        
        // Check telegram message is enabled.
        if ($this->telegramEnabled) {

            if((!$this->telegramToken || $this->telegramToken === "telegram-bot-token") || (!$this->telegramChatId || $this->telegramChatId === "telegram-chat-id")) {
                throw new Exception("To activate sending messages to Telegram you must configure [telegram_token] and [telegram_chat_id] in your [config.json] file.");

            }
        }
        
        /**
        * Whether to use cURL for HTTP requests.
        */
        $this->useCurl = $config['use_curl'] ?? true;

        /**
        * Path to the application data directory.
        */
        $this->appLocale = $this->appPath.$config['app_locale'] ?? null;

        /**
        * Path to the application data directory.
        */
        $this->appMessagesDir = __DIR__ . $this->appPath.$config['app_messages_dir'] ?? null;

        /**
        * Path to the application data directory.
        */
        $this->appLocaleMessages = $this->appPath.$config['app_locale_messages'] ?? null;
        
        $this->setLocale($this->appLocale);
        
    }
    
    public function getInitStatus() {
        return $this->initStatus;
    }
    
    private function getCurrentUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];

        return $protocol . $domainName;
    }
    public function urlExists($url) {
        
        $headers = @get_headers($url);
        
        if ($headers && strpos($headers[0], '200') !== false) {
            return true;
        } else {
            return false;
        }
    }
    
    public function setDefaultLocale($locale) {
        
        $this->appLocaleMessages = "wp_monitor_messages";
        $this->appMessagesDir = "locales/";
        
        $this->setLocale($locale);
    }
    
    public function setLocale($locale) {
        $this->appLocale = $locale;

        // Set the locale information
        putenv("LC_ALL={$this->appLocale}");
        setlocale(LC_ALL, $this->appLocale);

        // Set the domain and bind it to the directory
        bindtextdomain($this->appLocaleMessages, $this->appMessagesDir);
        textdomain($this->appLocaleMessages);
        bind_textdomain_codeset($this->appLocaleMessages, 'UTF-8');
    }

    public function translate($message) {
        return gettext($message);
    }

    /**
     * Get logs by timeframe and/or pattern.
     *
     * @param string $timeframe Log time interval, default = False.
     * @param string $mixed_pattern Regex patter to search in logs.
     * @throws Exception Si ocurre un error al insertar la publicación en la base de datos.
     */
    public function getDefaultMsgTime($timeframe=False,$mixed_pattern=False) {
        
        $timeObject = $this->lowerTimeLimitMenuObj();
        
        if(!$timeframe){
            $tf = $this->defaultRangeType;
        }else{
            $tf = $timeframe;
        }
        
        if($mixed_pattern){
            return sprintf("No logs for '%s' time and pattern '%s'.", $timeObject[$tf], $mixed_pattern);
        }
        
        return sprintf("No logs for '%s' time", $timeObject[$tf]);
    }
    
    private function lowerTimeLimitMenuObj(){
    
        $timeLimits = array(
            'last_minute' => 'Last minute',
            'last_5_minutes' => 'Last 5 minutes',
            'last_half_hour' => 'Last half hour',
            'last_hour' => 'Last hour',
            'last_24_hours' => 'Last 24 hours',
            'last_week' => 'Last week',
            'last_month' => 'Last month',
            'last_year' => 'Last year'
            );
        
        return $timeLimits;
        
    }
    
    /**
    * Method to print time intervals menu select
    *
    */
    public function writeTimesMenu() {
        
        $objectMenu = $this->lowerTimeLimitMenuObj();
        
        foreach($objectMenu AS $key => $value){
            
            echo "<option value='" . $key . "'>" . $value . "</option>\n";
        }
    }
    
    /**
    * Method to get lower time limit based on range type
    *
    * @param string $rangeType Range time
    *
    */
    public function getLowerTimeLimit($rangeType) {
        $currentTimestamp = time();
        switch ($rangeType) {
            case 'last_minute':
                return strtotime('-1 minute', $currentTimestamp);
            case 'last_5_minutes':
                return strtotime('-5 minutes', $currentTimestamp);
            case 'last_half_hour':
                return strtotime('-30 minutes', $currentTimestamp);
            case 'last_hour':
                return strtotime('-1 hour', $currentTimestamp);
            case 'last_24_hours':
                return strtotime('-24 hours', $currentTimestamp);
            case 'last_week':
                return strtotime('-1 week', $currentTimestamp);
            case 'last_month':
                return strtotime('-1 month', $currentTimestamp);
            case 'last_year':
                return strtotime('-1 year', $currentTimestamp);
            default:
                return false;
        }
    }

    private function getDomain($domain_url) {
        $pieces = parse_url($domain_url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        }
        return false;
    }

    public function checkForOutdatedPlugins($plugins) {

        $outdated = False;

        foreach ($plugins AS $plugin) {

            if ($plugin['update_version']) {

                $outdated = True;
                break;
            }
        }

        return $outdated;
    }
    
    public function validateHexCode($code){
        
        // Check if the output is a 10-character hexadecimal value
        if (preg_match('/^[0-9a-fA-F]{20}$/', trim($code))) {
            return true;
        } else {
            return false;
        }
    }
    
    private function readCodeFromFilename($file){
        
        $filename = basename($file, ".json");
        // Extract the ID part of the filename (the part before the hyphen)
        $code = explode('-', $filename)[1];
        
        return $code;
    }
    
    public function readWPData() {
        
        $wp_files = glob($this->appPath."/wpm_data/*.json");

        if($wp_files) {
            
            $files_with_numbers = [];
            
            foreach ($wp_files as $file) {
                
                $filename = basename($file, ".json");

                // Extract the ID part of the filename (the part before the hyphen)
                $id = explode('-', $filename)[0];

                // Check if the extracted ID is numeric
                if (ctype_digit($id)) {
                    $files_with_numbers[(int)$id] = $file;
                }
            }

            // Sort the associative array by numeric key
            ksort($files_with_numbers, SORT_NUMERIC);

            // Get the array of sorted files
            $sorted_files = array_values($files_with_numbers);

            return $sorted_files;
        }
        
        return false;
        
        
    }
    
    public function readWPJsonData($code) {
        $wp_data_dir = $this->appPath."/wpm_data/";
        $files = glob($wp_data_dir . '*-' . $code . '.json');
        if (count($files) > 0) {
            $json_content = file_get_contents($files[0]);
            return json_decode($json_content, true);
        }
        return null;
    }
    
    public function printTableWP() {

        $wp_installs = $this->readWPData();
        
        if($wp_installs){
            foreach ($wp_installs as $wp_file) {
                $wp_status = "No jobs";
                $wp_stats_class = "";
                $json = file_get_contents($wp_file);
                $json_data = json_decode($json, true);
                $row_class = ($json_data['outdatedWp'] == True) ? 'table-danger' : '';
                $plugins_outdated = $this->checkForOutdatedPlugins($json_data['plugins']);
                $plugins_class = ($plugins_outdated == True) ? 'cell-danger' : '';
                $pending_jobs = $this->searchInJobs($json_data['id']);
                if($pending_jobs){
                    $wp_status = "Running jobs";
                    $wp_stats_class = " text-bg-warning disallowed";
                }
                $pending_jobs_class = ($pending_jobs == True) ? 'pending-jobs' : '';
                
                echo "<tr data-code='" . htmlspecialchars($this->readCodeFromFilename($wp_file)) . "' class='" . $row_class . " " . $pending_jobs_class ."'>";
                echo "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['siteUrl'] . "</td>";
                echo "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['name'] . "</td>";
                echo "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['unsupportedPhp'] . "</td>";
                echo "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['unsupportedWp'] . "</td>";
                echo "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['broken'] . "</td>";
                echo "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['infected'] . "</td>";
                echo "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['outdatedPhp'] . "</td>";
                echo "<td class='table-cell-center text-nowrap".$wp_stats_class."'>" . $json_data['alive'] . "</td>";
                echo "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['stateText'] . "</td>";
                echo "<td class='table-cell-center " . $plugins_class . " text-nowrap".$wp_stats_class."'>" . count($json_data['plugins']) . "</td>";
                echo "<td class='table-cell-center text-nowrap".$wp_stats_class."'>" . $json_data['version'] . "</td>";
                echo "<td class='table-cell-center text-nowrap".$wp_stats_class."'>" . $wp_status . "</td>";
                echo "</tr>";
            }
        }else{
            return false;
        }
    }

    public function cleanTMP($tempDir) {

        // After finished working with the extracted files, delete the temporary directory
        $files = glob($this->appData."/tmp/*"); // Get all files in the temporary directory
        foreach ($files as $file) { // Delete each tmp file
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    public function readWPLogs($domain_url, $time_frame=False) {
        
        $domain = $this->getDomain($domain_url);
        // Path to the .tar.gz compressed file
        $compressedFile = sprintf("%s/%s_error_log.tar.gz", $this->logFilesPath, $domain);
        // Path to the temporary directory where the files will be extracted
        $tempDir = $this->appData . "/tmp/";

        $tarFile = str_replace('.gz', '', $compressedFile);

        // Check if the .tar file exists and delete it if necessary
        if (file_exists($tarFile)) {
            unlink($tarFile);
        }
        // Decompress the .tar.gz file
        $phar = new PharData($compressedFile);
        $phar->decompress();

        // Extract the .tar file to the temporary directory
        $phar = new PharData($tarFile);
        $phar->extractTo($tempDir);

        // Delete .tar file
        unlink($tarFile);

        $log_data = file_get_contents($tempDir . "error_log");
        
        $this->cleanTMP($tempDir);

        return $this->readLogByDate($log_data, $time_frame);
    }

    private function readLogByDate($log_data, $timeframe=False) {

        // Array to store the lines of the file that are within the last X months
        $logsWithinDate = [];

        // Obtener la fecha y hora actual en formato de tiempo UNIX
        $currentTimestamp = time();
        
        $rangeType = $this->defaultRangeType;
        
        if($timeframe){
            
            $objectTimes = $this->lowerTimeLimitMenuObj();
            
            if(array_key_exists($timeframe, $objectTimes)) {
                
                $rangeType = $timeframe;
            }
        }

        $lowerTimeLimit = $this->getLowerTimeLimit($rangeType);

        // Split file content into lines
        $lines = explode("\n", $log_data);

        // Iterate over each line of the file
        foreach ($lines as $line) {
            // Get the date and time of the current line
            preg_match('/^\[(.*?)\]/', $line, $matches);
            if (!empty($matches[1])) {
                // Convert date and time to UNIX timestamp
                $dateTime = DateTime::createFromFormat('D M j H:i:s.u Y', $matches[1]);
                if ($dateTime !== false) {
                    $timestamp = $dateTime->getTimestamp();
                    // Compare current timestamp with search lower limit
                    if ($timestamp >= $lowerTimeLimit && $timestamp <= $currentTimestamp) {
                        // Keep the line if it is within the last X months
                        $logsWithinDate[] = $line;
                        //echo "La línea está dentro de los últimos 4 meses.\n";
                    } else {
                        //echo "La línea NO está dentro de los últimos 4 meses.\n";
                    }
                } else {
                    //echo "No se pudo parsear la fecha.\n";
                }
            }
        }

        return $logsWithinDate;
    }

    public function getAppURL() {
        return $this->appUrl;
    }

    public function getAppPath() {
        return $this->appPath;
    }

    public function getDataPath() {
        return $this->appData;
    }

    public function getLogsPath() {
        return $this->logFilesPath;
    }
    public function getJobsFile() {
        return $this->jobsFile;
    }
    public function getJobsExecutedFile() {
        return $this->jobsExecutedFile;
    }
    public function getReloadTime() {
        return $this->appReloadTime;
    }
    // WP-Tookit actions
    private function writeJob($job) {
        file_put_contents($this->jobsFile, $job . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function updateWordPress($wp_id) {
        $job = "update_wp $wp_id";
        $this->writeJob($job);
    }

    public function updatePlugin($wp_id, $plugin_name) {
        $job = "update_plugin $wp_id $plugin_name";
        $this->writeJob($job);
    }

    public function updateTemplate($wp_id, $template_name) {
        $job = "update_template $wp_id $template_name";
        $this->writeJob($job);
    }

    public function disablePlugin($wp_id, $plugin_name) {
        $job = "disable_plugin $wp_id $plugin_name";
        $this->writeJob($job);
    }

    public function validateWPAction($action) {
        $valid_actions = ['do_jobs', 'get_logs'];
        return in_array($action, $valid_actions, true);
    }

    public function validateID($id) {
        return filter_var($id, FILTER_VALIDATE_INT);
    }

    public function validateBool($bool) {
        return is_bool($bool);
    }
    //Validat plugin and theme names (slug-type)
    public function validateName($name) {
        return preg_match('/^[a-zA-Z0-9\-]+$/', $name);
    }

    public function validateAction($action) {
        $valid_actions = ['update', 'disable'];
        return in_array($action, $valid_actions, true);
    }

    public function validateUrlDomain($url) {
        return filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https:\/\/([a-zA-Z0-9\-]+\.)?[a-zA-Z0-9\-]+\.[a-zA-Z]{2,}$/', $url);
    }

    private function readJobs($job_file) {
        if (!file_exists($job_file)) {
            return [];
        }
        $lines = file($job_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $lines;
    }
    // Function to add a single job

    public function addJob($new_job) {
        $job_file = $this->getJobsFile();
        $existing_jobs = $this->readJobs($job_file);
        $success_jobs = [];
        $error_jobs = [];
        // If the job doesn't exist, add it
        if (!in_array($new_job, $existing_jobs)) {
            try {
                // Write the new job to the file
                file_put_contents($job_file, $new_job . "\n", FILE_APPEND | LOCK_EX);
                return true;
            } catch (Exception $e) {
                // Handle error
                return "error_writing: " . $e->getMessage();
            }
        } else {
            return "duplicated";
        }
    }

    private function searchInJobs($wp_id) {

        $job_file = $this->getJobsFile();
        // Check if the jobs file exists
        if (!file_exists($job_file)) {
            return false;
        }

        // Open the file in reading mode
        $file = fopen($job_file, 'r');
        if (!$file) {
            throw new Exception("No se pudo abrir el archivo: $job_file");
        }

        // Read the file line by line
        while (($line = fgets($file)) !== false) {
            // Check if the line contains the WordPress installation ID
            if (strpos($line, " $wp_id ") !== false || strpos($line, " $wp_id\n") !== false || strpos($line, "$wp_id ") === 0 || trim($line) === "$wp_id") {
                fclose($file);
                return true;
            }
        }

        // Close file
        fclose($file);

        // No pending jobs found for the given ID
        return false;
    }
    // BONUS Telegram
    // Public function to send a message to Telegram
    public function sendMessageToTelegram($message) {
        if (empty($this->telegramToken) || empty($this->telegramChatId)) {
            throw new Exception("To activate sending messages to Telegram you must configure [telegram_token] and [telegram_chat_id] in your [config.json] file.");
        }

        if ($this->useCurl) {
            return $this->sendMessageToTelegramCurl($message);
        } else {
            return $this->sendMessageToTelegramFileGetContents($message);
        }
    }
    public function telegramEnabled(){

        return $this->telegramEnabled;
    }
    private function sendMessageToTelegramCurl($message) {
        $url = "https://api.telegram.org/bot{$this->telegramToken}/sendMessage";
        $data = [
            'chat_id' => $this->telegramChatId,
            'text' => $message
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Error de cURL: ' . curl_error($ch));
        }
        curl_close($ch);

        $response = json_decode($result, true);
        if (!$response['ok']) {
            $msg_error = $this->translate("Telegram API error: %s");
            throw new Exception(sprintf($msg_error, $response['description']));
        }

        return $response;
    }

    private function sendMessageToTelegramFileGetContents($message) {
        $url = "https://api.telegram.org/bot{$this->telegramToken}/sendMessage";
        $data = [
            'chat_id' => $this->telegramChatId,
            'text' => $message
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        
        $context  = stream_context_create($options);
        
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            throw new Exception("Error sending message using [file_get_contents].");
        }

        $response = json_decode($result, true);
        if (!$response['ok']) {
            $msg_error = $this->translate("Telegram API error: %s");
            throw new Exception(sprintf($msg_error, $response['description']));
        }

        return $response;
    }

}
