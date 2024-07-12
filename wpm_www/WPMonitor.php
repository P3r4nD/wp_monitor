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
    * @var bool   $sendMail          Flag to enable/disable Email functionalitiy
    * @var bool   $sendSMTP          Flag to enable/disable SMTP protocol
    * @var string $smtpServer        Name or IP of SMTP server
    * @var int    $smtpPort          SMTP server port number
    * @var string $smtpSecure        SMTP security (ssl, tls)
    * @var string $smtpUser          SMTP user (normally it's a email address)
    * @var string $smtpPassword      SMTP password
    * @var string $mailFrom          From field email header
    * @var string $mailFromTitle     Title or name for 'From field' email header
    * @var string $mailer            PHPMail or PHPMailer
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
    protected $sendMail;
    protected $sendSMTP;
    protected $smtpServer;
    protected $smtpPort;
    protected $smtpSecure;
    protected $smtpUser;
    protected $smtpPassword;
    protected $mailFrom;
    protected $mailFromTitle;
    protected $mailer;


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
        
        // If the environment variable is not defined raise exception error
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
        
        /**
         * Path to config file
         *
         */
        $configPath = $wpm_path . self::CONFIG_FILE;
        
        // If the config file is not configured, raise exception error
        if (!file_exists($configPath)) {
            $this->initStatus = False;
            throw new Exception($this->translate("The configuration file [config.json] does not exist."));
        }
        
        // Read config file
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
        * Whether to allow send mails from app.
        */
        $this->sendMail = $config['email'] ?? false;
        
        //Check email config
        if ($this->sendMail) {
            
            $this->sendSMTP = $config['smtp'] ?? false;
            
            if ($this->sendSMTP) {
                
                $this->smtpServer = $config['smtp_server'] ?? null;
                
                if((!$this->smtpServer)) {
                    throw new Exception($this->translate("To send emails using the SMTP protocol, you must configure an smtp server [smtp_server] in config.json"));
                }
                
                $this->smtpPort = $config['smtp_port'] ?? null;
                
                if((!$this->smtpPort)) {
                    throw new Exception($this->translate("To send emails using the SMTP protocol, you must configure an smtp port [smtp_port] in config.json"));
                }
                
                $this->smtpSecure = $config['smtp_secure'] ?? null;
                
                if((!$this->smtpSecure)) {
                    throw new Exception($this->translate("To send emails using the SMTP protocol, you must define a encryption mechanism [smtp_secure] in config.json"));
                }
                
                $this->smtpUser = $config['smtp_user'] ?? null;
                
                if((!$this->smtpUser)) {
                    throw new Exception($this->translate("To send emails using the SMTP protocol, you must define a username [smtp_user] in config.json"));
                }
                
                $this->smtpPassword = $config['smtp_password'] ?? null;
                
                if((!$this->smtpPassword)) {
                    throw new Exception($this->translate("To send emails using the SMTP protocol, you must define a password [smtp_password] in config.json"));
                }
                
                $this->mailFrom = $config['email_from'] ?? null;
                
                $this->mailFromTitle = $config['email_from_title'] ?? null;
                
            }
            
        }

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
    
    /**
    * Get init status
    */
    public function getInitStatus() {
        return $this->initStatus;
    }
    
    /**
    * Get current app url
    */
    private function getCurrentUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];

        return $protocol . $domainName;
    }
    
    /**
    * Check if current app url is callable
    */
    public function urlExists($url) {
        
        $headers = @get_headers($url);
        
        if ($headers && strpos($headers[0], '200') !== false) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
    * Set default language
    */
    public function setDefaultLocale($locale) {
        
        $this->appLocaleMessages = "wp_monitor_messages";
        $this->appMessagesDir = "locales/";
        
        $this->setLocale($locale);
    }
    
    /**
    * Set language
    */
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
    
    /**
    * Function to translate strings
    */
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
    
    /**
    * Method to get lower time limit options for menu select
    *
    */
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
    
    /**
    * Method to get domain from a URL
    *
    * @param string $domain_url Domain URL
    *
    */
    private function getDomain($domain_url) {
        $pieces = parse_url($domain_url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        }
        return false;
    }
    
    /**
    * Method to check if any plugin is aoutdated,
    * at the first one that is detected as outdated, we exit outdated=True
    *
    * @param string $domain_url Domain URL
    *
    */
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
    
    /**
    * Validate if WP code is valid hexadecimal format
    *
    * @param string $code wp_id=code
    *
    */
    public function validateHexCode($code){
        
        // Check if the output is a 10-character hexadecimal value
        if (preg_match('/^[0-9a-fA-F]{20}$/', trim($code))) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
    * Get WP code from file in wpm_data/x-code.json
    *
    * @param string $file wp_id=code
    *
    */
    private function readCodeFromFilename($file){
        
        $filename = basename($file, ".json");
        // Extract the CODE part of the filename (the part after the hyphen)
        $code = explode('-', $filename)[1];
        
        return $code;
    }
    
    /**
    * Read all WP data
    *
    *
    */
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
    
    /**
    * Read WP json data by code from wpm_data/id-code.json
    *
    * @param string $code wp_id=code
    *
    */
    public function readWPJsonData($code) {
        $wp_data_dir = $this->appPath."/wpm_data/";
        $files = glob($wp_data_dir . '*-' . $code . '.json');
        if (count($files) > 0) {
            $json_content = file_get_contents($files[0]);
            return json_decode($json_content, true);
        }
        return null;
    }
    
    /**
    * Get WP id by code from wpm_data/id-code.json
    *
    * @param string $code wp_id=code
    *
    */
    public function getIDbyCode($code) {
        
        $wp_data_dir = $this->appPath."/wpm_data/";
        $file = glob($wp_data_dir . '*-' . $code . '.json');

        if (count($file) > 0) {
            // Extract the filename without the extension
            $wp_file = basename($file[0], '.json');

            // Split the filename into ID and code parts
            list($id, $file_code) = explode('-', $wp_file, 2);

            // Check if the code matches
            if ($file_code === $code) {
                return $id;
            }
        }
        return null;
    }
    
    /**
    * Read WP installs data and print main table
    *
    */
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
    
    /**
    * Read WP installs data and print main table but this call is from WPMonitorAPI
    *
    */
    public function getTableWP() {

        $wp_installs = $this->readWPData();
        $html_table = "";
        $jobs = [];
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
                    $jobs[] = $json_data['name'];
                }
                $pending_jobs_class = ($pending_jobs == True) ? 'pending-jobs' : '';

                $html_table .= "<tr data-code='" . htmlspecialchars($this->readCodeFromFilename($wp_file)) . "' class='" . $row_class . " " . $pending_jobs_class ."'>";
                $html_table .= "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['siteUrl'] . "</td>";
                $html_table .= "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['name'] . "</td>";
                $html_table .= "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['unsupportedPhp'] . "</td>";
                $html_table .= "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['unsupportedWp'] . "</td>";
                $html_table .= "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['broken'] . "</td>";
                $html_table .= "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['infected'] . "</td>";
                $html_table .= "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['outdatedPhp'] . "</td>";
                $html_table .= "<td class='table-cell-center text-nowrap".$wp_stats_class."'>" . $json_data['alive'] . "</td>";
                $html_table .= "<td class='table-cell text-nowrap".$wp_stats_class."'>" . $json_data['stateText'] . "</td>";
                $html_table .= "<td class='table-cell-center " . $plugins_class . " text-nowrap".$wp_stats_class."'>" . count($json_data['plugins']) . "</td>";
                $html_table .= "<td class='table-cell-center text-nowrap".$wp_stats_class."'>" . $json_data['version'] . "</td>";
                $html_table .= "<td class='table-cell-center text-nowrap".$wp_stats_class."'>" . $wp_status . "</td>";
                $html_table .= "</tr>";
            }

            $message = [
                'html'  => $html_table,
                'title' => $this->translate('Updated data'),
                'timestamp' => $this->translate('Just now'),
                'message'   => $this->translate('No pending jobs.')
            ];

            if(!empty($jobs)){
                $message['message'] = $this->translate("Pending jobs. If message persists, jobs are not running!");
            }

            return $message;
        }else{
            return false;
        }
    }
    
    /**
    * Clean tmp files after decompress logs
    *
    */
    public function cleanTMP($tempDir) {

        // After finished working with the extracted files, delete the temporary directory
        $files = glob($this->appData."/tmp/*"); // Get all files in the temporary directory
        foreach ($files as $file) { // Delete each tmp file
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
    * Read WP logs compressed file by domain
    *
    * @param string $domain_url to search log file wpm_data/logs/domain.com_error_log.tar.gz
    *
    */
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
    
    /**
    * Get WP log by date
    *
    * @param array $log_data Contains array of log lines
    *
    */
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
    
    /**
    * Read email log by code
    *
    * @param string $code Code to find email log file
    *
    */
    public function readEmailLog($code) {
        
        // Obtain id for this code
        $wp_id = $this->getIDbyCode($code);
        $log_file_name = sprintf($this->appPath."/wpm_data/%s-email.log", $wp_id);
        
        if (!file_exists($log_file_name)) {
            return [];
        }
        
        $log_file = file_get_contents($log_file_name);
        $log_lines = explode("\n",$log_file);
        
        return $log_lines;
        
    }
    
    /**
    * Get APP URL
    *
    */
    public function getAppURL() {
        return $this->appUrl;
    }
    
    /**
    * Get APP path
    *
    */
    public function getAppPath() {
        return $this->appPath;
    }
    
    /**
    * Get app data path wp_monitor/wpm_data/
    *
    */
    public function getDataPath() {
        return $this->appData;
    }
    
    /**
    * Get the path to the logs directory
    *
    */
    public function getLogsPath() {
        return $this->logFilesPath;
    }
    
    /**
    * Get jobs file 
    *
    */
    public function getJobsFile() {
        return $this->jobsFile;
    }
    
    /**
    * Get jobs executed file 
    *
    */
    public function getJobsExecutedFile() {
        return $this->jobsExecutedFile;
    }
    
    /**
    * Get app reload time for interface web page
    *
    */
    public function getReloadTime() {
        return $this->appReloadTime;
    }
    
    /**
    * Write new job to jobs file
    *
    */
    private function writeJob($job) {
        file_put_contents($this->jobsFile, $job . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    /**
    * Write Update Wordpress job to jobs file
    *
    * @param int $wp_id WP installation ID
    */
    public function updateWordPress($wp_id) {
        $job = "update_wp $wp_id";
        $this->writeJob($job);
    }
    
    /**
    * Write Update Wordpress plugin job to jobs file
    *
    * @param int $wp_id WP installation ID
    * @param string $plugin_name WordPress Plugin slug
    */
    public function updatePlugin($wp_id, $plugin_name) {
        $job = "update_plugin $wp_id $plugin_name";
        $this->writeJob($job);
    }
    
    /**
    * Write Update Wordpress theme job to jobs file
    *
    * @param int $wp_id WP installation ID
    * @param string $template_name WordPress theme slug
    */
    public function updateTemplate($wp_id, $template_name) {
        $job = "update_template $wp_id $template_name";
        $this->writeJob($job);
    }
    
    /**
    * Write Disable Wordpress plugin job to jobs file
    *
    * @param int $wp_id WP installation ID
    * @param string $plugin_name WordPress Plugin slug
    */
    public function disablePlugin($wp_id, $plugin_name) {
        $job = "disable_plugin $wp_id $plugin_name";
        $this->writeJob($job);
    }
    
    /**
    * Validate if action is in available actions
    *
    * @param string $action Action slug
    */
    public function validateWPAction($action) {
        $valid_actions = ['do_jobs', 'get_logs'];
        return in_array($action, $valid_actions, true);
    }
    
    /**
    * Validate if WP id is int var
    *
    * @param int $id WordPress ID
    */
    public function validateID($id) {
        return filter_var($id, FILTER_VALIDATE_INT);
    }
    
    /**
    * Validate if var is bool
    *
    * @param bool $bool Boolean var
    */
    public function validateBool($bool) {
        return is_bool($bool);
    }
    
    /**
    * Validat plugin and theme names (slug-type)
    *
    * @param string $name Slug name for plugin or theme
    */
    public function validateName($name) {
        return preg_match('/^[a-zA-Z0-9\-]+$/', $name);
    }
    
    /**
    * Validate if plugin job action is in available actions
    *
    * @param string $action Action name
    */
    public function validateAction($action) {
        $valid_actions = ['update', 'disable'];
        return in_array($action, $valid_actions, true);
    }
    
    /**
    * Validate if domain url is valid url
    *
    * @param string $url Domain url
    */
    public function validateUrlDomain($url) {
        return filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https:\/\/([a-zA-Z0-9\-]+\.)?[a-zA-Z0-9\-]+\.[a-zA-Z]{2,}$/', $url);
    }
    
    /**
    * Read pending jobs in jobs file
    *
    * @param string $job_file Jobs file
    */
    private function readJobs($job_file) {
        if (!file_exists($job_file)) {
            return [];
        }
        $lines = file($job_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $lines;
    }
    
    /**
    * Write new job to jobs file
    *
    * @param string $new_job Jobs file
    */
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
    
    /**
    * Check if WordPress installation ID have pending jobs
    *
    * @param int $wp_id WordPress ID
    */
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
    
    /**
    * Check if app is configured to permit send mails, return bool true|false
    *
    */
    public function mailActive(){
        return $this->sendMail;
    }
    
    /**
    * Bridge function to manage the sending of emails and their corresponding logs
    *
    * @param array $mail_data contains:
    * mail_to=array of email addresses, wp_id, mail_title, mail_body, mail_level
    */
    public function prepareMailSend($mail_data) {
        
        $errors = [];
        
        foreach($mail_data['mail_to'] AS $email_to) {
            
            $email_send = $this->mailSend($mail_data['wp_id'], $email_to, $mail_data['mail_title'], $mail_data['mail_body']);
            
            sleep(1);
            
            if($email_send){
                $this->saveEmailLog($mail_data['wp_id'], $email_to, $mail_data['mail_title'], $mail_data['mail_body'], $mail_data['mail_level'], "SUCCESS");
            }else{
                $errors[] = true;
                $this->saveEmailLog($mail_data['wp_id'], $email_to, $mail_data['mail_title'], $mail_data['mail_body'], $mail_data['mail_level'], "ERROR:".$email_send);
            }  
        }
        
        if(empty($errors)){
            return ['success' => true, 'message' => "Emails sent correctly"];
        }
        
        return ['errors' => $errors];
        
    }
    
    /**
    * Send Email function
    *
    * @param int $wp_id WordPress installation ID:
    * @param string $email_to email address
    * @param string $email_subject email subject
    * @param string $email_body email body content
    */
    public function mailSend($wp_id, $email_to, $email_subject, $email_body) {
        
        require_once 'lib/phpmailer/src/Exception.php';
        require_once 'lib/phpmailer/src/PHPMailer.php';
        require_once 'lib/phpmailer/src/SMTP.php';

        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            //$this->mailer->SMTPDebug = 2;
            $this->mailer->isSMTP();
            $this->mailer->Host       = $this->smtpServer;
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $this->smtpUser;
            $this->mailer->Password   = $this->smtpPassword;
            $this->mailer->SMTPSecure = $this->smtpSecure;
            $this->mailer->Port       = $this->smtpPort;

            // Destinatarios
            $this->mailer->setFrom($this->mailFrom, $this->mailFromTitle);
            $this->mailer->addAddress($email_to);

            // Contenido del correo
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $email_subject;
            $this->mailer->Body    = $email_body;
            $this->mailer->AltBody = strip_tags($email_body);

            $this->mailer->send();
            return true;
            
        } catch (PHPMailer\PHPMailer\Exception $e) {
            return $this->mailer->ErrorInfo;
        }
    }
    
    /**
    * Write email log line
    *
    * @param int $id Used to find id-email.log
    * @param string $to email recipient
    * @param string $subject email subject
    * @param string $body email body content
    * @param string $priority email urgency orpriority
    * @param string $status status of sended email SUCCESS|ERROR
    */
    private function saveEmailLog($id, $to, $subject, $body, $priority, $status)
    {
        $dateTime = date('d-m-Y H:i:s');
        $logMessage = "{$dateTime} {$to} {$subject} {$body} {$priority} {$status}\n";
        $logFile = sprintf($this->appPath."/wpm_data/%s-email.log", $id);

        // Create x-mail.log file if doesn't exists
        if (!file_exists($logFile)) {
            touch($logFile);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
    * Sanitize html 
    *
    * @param string $input text or html from email form fields
    * 
    */
    public function sanitizeTextField($input) {
        // List of allowed HTML tags
        $allowed_tags = '<h1><h2><b><i><u><strong><em><p><br><ul><ol><li><a><img>';

        // Delete not allowed tags
        $sanitized = strip_tags($input, $allowed_tags);

        // Convertir caracteres especiales en entidades HTML
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');

        return $sanitized;
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
