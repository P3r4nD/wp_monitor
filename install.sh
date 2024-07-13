#!/bin/bash

BASE_DIR=$(dirname "$(realpath "$0")")
PATH_DATA="$BASE_DIR/wpm_data/"

#Colors
greenColor="\e[0;32m\033[1m"
endColor="\033[0m\e[0m"
redColor="\e[0;31m\033[1m"
yellowColor="\e[0;33m\033[1m"
grayColor="\e[0;37m\033[1m"

# Check that everything is configured correctly before proceed
check=true

detect_control_panel() {
    for dir in "/usr/local/cpanel" "/var/cpanel" "/etc/cpanel"; do
        if [ -d "$dir" ]; then
            return 1
        fi
    done

    for dir in "/usr/local/psa" "/opt/psa" "/etc/psa"; do
        if [ -d "$dir" ];then
            return 2
        fi
    done

    return 3
}

check_wp_toolkit() {

    local control_panel=$1

    if [ "$control_panel" -eq 1 ]; then
        if command -v /usr/local/cpanel/bin/wp-toolkit &> /dev/null; then
            echo -e "${greyColor}\tWP Toolkit:${endColor} ${greenColor}Installed in cPanel.${endColor}"
        else
            echo -e "${greyColor}\tWP Toolkit:${endColor} ${redColor}Not installed in cPanel. Installation not permitted.${endColor}"
        fi
    elif [ "$control_panel" -eq 2 ]; then
        if plesk bin extension --list | grep -q "wp-toolkit"; then
            echo -e "${greyColor}\tWP Toolkit:${endColor} ${greenColor}Installed in Plesk.${endColor}"
        else
            echo -e "${greyColor}\tWP Toolkit:${endColor} ${redColor}Not installed in Plesk. Installation not permitted.${endColor}"
            exit 1
        fi
    fi
}

validate_domain() {
    local domain=$1
    if [[ $domain =~ ^[a-zA-Z0-9.-]+$ ]]; then
        return 0
    else
        return 1
    fi
}

find_document_root() {
    local domain=$1
    local control_panel=$2
    local document_root=""

    if [ "$control_panel" -eq 1 ]; then
        user=$(grep -lR $domain /var/cpanel/userdata | head -n 1 | awk -F/ '{print $5}')
        if [ -n "$user" ]; then
            document_root=$(grep "documentroot:" /var/cpanel/userdata/$user/$domain | awk '{print $2}')
            if [ -z "$document_root" ]; then
                document_root=$(grep "documentroot:" /var/cpanel/userdata/$user/*.$domain | awk '{print $2}')
            fi
        fi
    elif [ "$control_panel" -eq 2 ]; then
        document_root=$(plesk bin site --info $domain 2>/dev/null | grep "WWW-Root" | awk '{print $2}')
        if [ -z "$document_root" ];then
            document_root=$(plesk bin subdomain --info $domain 2>/dev/null | grep "WWW-Root" | awk '{print $2}')
        fi
    fi

    if [ -d "$document_root" ]; then
        echo $document_root
    else
        echo ""
    fi
}

install_wp_monitor() {
    local document_root=$1
    local install_in_root=$2

    if [ "$install_in_root" = true ]; then
        echo -e "\t${greyColor}Copying files to:${endColor} ${greenColor}$document_root/${endColor}"
        if [ "$check" = false ]; then
            cp -r "$BASE_DIR/wpm_www/"* "$document_root/"
        fi
    else
        echo -e "\t${greyColor}Creating directory:${endColor} ${greenColor}$document_root/wp_monitor${endColor}"
        if [ "$check" = false ]; then
            mkdir -p "$document_root/wp_monitor"
        fi
        echo -e "\t${greyColor}Copying files to:${endColor} ${greenColor}$document_root/wp_monitor/${endColor}"
        if [ "$check" = false ]; then
            cp -r "$BASE_DIR/wpm_www/"* "$document_root/wp_monitor/"
        fi
    fi
}

create_cron_job() {
    local interval=$1
    local script=$2
    (crontab -l ; echo "*/$interval * * * * $script") | crontab -
}

install_service() {
    local service_path="/etc/systemd/system/wp_monitor_jobs.service"
    local source_service="$BASE_DIR/wpm_bash/wpm_jobs_service.service"

    if [ -f "$source_service" ];then
        if [ "$check" = false ]; then

            # Make wpm_jobs.sh executable
            chmod +x "$BASE_DIR/wpm_bash/wpm_jobs.sh"

            # Modify the ExecStart path in the service file if necessary
            if [[ "$BASE_DIR" != "/opt/wp_monitor" ]]; then
                sed "s|ExecStart=/opt/wp_monitor/wpm_bash/wpm_jobs.sh|ExecStart=$BASE_DIR/wpm_bash/wpm_jobs.sh|" "$source_service" > "$service_path"
            else
                cp "$source_service" "$service_path"
            fi
        fi
        echo -e "\t${greyColor}Service installed from${endColor} ${greenColor}$source_service ${endColor} to ${endColor} ${greenColor}$service_path${endColor}"
    else
        echo -e "\t${redColor}Service file not found at $source_service${endColor}"
        exit 1
    fi
}

start_service() {
    if [ "$check" = false ]; then
        systemctl start wp_monitor_jobs.service
        systemctl enable wp_monitor_jobs.service
    fi
    echo -e "\t${greyColor}Service: ${endColor} ${greenColor}started and enabled${endColor}"
}

# Function to download PHPMailer
download_phpmailer() {
    local lib_path=$1
    mkdir -p "$lib_path"
    wget https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip -O "$lib_path/phpmailer.zip"
    unzip "$lib_path/phpmailer.zip" -d "$lib_path"
    mv "$lib_path/PHPMailer-master" "$lib_path/phpmailer"
    rm "$lib_path/phpmailer.zip"
}

# Update paths and panel in wpm_jobs.sh
update_wpm_jobs_file() {
    WPM_JOBS_FILE="$BASE_DIR/wpm_bash/wpm_jobs.sh"

    # Ensure the wpm_jobs.sh file exists
    if [[ ! -f "$WPM_JOBS_FILE" ]]; then
        echo "Error: $WPM_JOBS_FILE not found!"
        exit 1
    fi

    # Detect the control panel
    cp=$(detect_control_panel)

    # Update paths and panel in wpm_jobs.sh
    sed -i "s|^JOBS_FILE=.*|JOBS_FILE=\"$BASE_DIR/jobs\"|" "$WPM_JOBS_FILE"
    sed -i "s|^JOBS_EXECUTED_FILE=.*|JOBS_EXECUTED_FILE=\"$BASE_DIR/jobs_executed\"|" "$WPM_JOBS_FILE"
    sed -i "s|^LOCK_FILE=.*|LOCK_FILE=\"$BASE_DIR/wpm_data/tmp/wp_monitor.lock\"|" "$WPM_JOBS_FILE"
    sed -i "s|^LOG_FILE=.*|LOG_FILE=\"$BASE_DIR/wpm_data/logs/jobs.log\"|" "$WPM_JOBS_FILE"
    sed -i "s|^PANEL=.*|PANEL=\"$cp\"|" "$WPM_JOBS_FILE"
}

# Parse no-check args
for arg in "$@"; do
    if [ "$arg" == "no-check" ]; then
        check=false
        echo -e "${yellowColor}\n\nInstalling WP Monitor...${endColor}"
    fi
done

if [ "$check" = true ]; then
    echo -e "${yellowColor}\n\nChecking installation of WP Monitor.${endColor}"
fi

echo -e "${greyColor}\tWP Monior base dir:${endColor} ${greenColor}$BASE_DIR ${endColor}"

# 1. Detect control panel
detect_control_panel
control_panel=$?

if [ $control_panel -eq 1 ]; then
    echo -e "${greyColor}\tControl Panel:${endColor} ${greenColor}cPanel Detected${endColor}"
fi

if [ $control_panel -eq 2 ]; then
    echo -e "${greyColor}\tControl Panel:${endColor} ${greenColor}Plesk Detected${endColor}"
fi

if [ $control_panel -eq 3 ]; then
    echo -e "Installation not allowed"
    exit 1
fi


# Check if WP Toolkit is installed
check_wp_toolkit $control_panel

# 2. Ask for domain and validate
while true; do
    echo -n -e "\n${yellowColor}What domain do you want to install the interface on? ${endColor}"
    read domain
    if validate_domain $domain; then
        document_root=$(find_document_root "$domain" $control_panel)
        if [ -n "$document_root" ];then
            echo -e "\t${greyColor}Domain:${endColor} ${greenColor}found in $document_root${endColor}"
            break
        else
            echo -e "\t${greyColor}Domain: ${endColor} ${redColor}The root directory of the domain was not found. Please re-enter the domain.${endColor}"
        fi
    else
        echo "Invalid domain. Please re-enter the domain."
    fi
done

# 3. Copy wpm_www content
echo -n -e "\n${yellowColor}Do you want to install wp_monitor at the root of the domain? (Y/N): ${endColor}"
read install_in_root
if [[ "$install_in_root" == "Y" || "$install_in_root" == "y" ]];then
    install_wp_monitor "$document_root" true
else
    install_wp_monitor "$document_root" false
fi

echo -e "\n${yellowColor}Checking permissions: Setting [user:group] for www${endColor}"
# 3.1 Get user:group and apply permissions

user_group=$(stat -c "%U:%G" "$document_root")
echo -e "\t${greyColor}user:group: ${endColor} ${greenColor}$user_group${endColor}"

# Apply permissions conditionally based on installation directory
if [[ "$install_in_root" == "Y" || "$install_in_root" == "y" ]]; then
    echo -e "\t${greyColor}Permissions will be applied: ${endColor} ${greenColor} $user_group ${endColor} ${greyColor} to ${endColor} ${greenColor} $document_root${endColor}"
    if [ "$check" = false ]; then
        chown -R "$user_group" "$document_root"
    fi
else
    echo -e "\t${greyColor}Permissions will be applied: ${endColor} ${greenColor} $user_group ${endColor} ${greyColor} to ${endColor} ${greenColor} $document_root/wp_monitor${endColor}"
    if [ "$check" = false ]; then
        chown -R "$user_group" "$document_root/wp_monitor"
    fi
fi

echo -e "\t${greyColor}Permissions to 'jobs' file will be applied: ${endColor} ${greenColor} $user_group${endColor}"
if [ "$check" = false ]; then
    chown "$user_group" "$BASE_DIR/jobs"
fi

echo -e "\t${greyColor}Permissions will be applied: ${endColor} ${greenColor} $user_group ${endColor} ${greyColor} a ${endColor} ${greenColor} $BASE_DIR/wpm_data${endColor}"
if [ "$check" = false ]; then
    chown -R "$user_group" "$BASE_DIR/wpm_data"
fi

echo -e "\n${yellowColor}Updating CRON and JOBS files with new data: ${endColor}"

# Modify BASE_DIR in wpm_bash/wpm_jobs.sh
echo -e "\t${greyColor}[BASE_DIR] updated in: ${endColor} ${greenColor} wpm_bash/wpm_jobs.sh${endColor}"
if [ "$check" = false ]; then
    update_wpm_jobs_file
fi

# Modify USER_GROUP in CRON scripts
if [ $control_panel -eq 1 ]; then
    cron_file="$BASE_DIR/wpm_bash/cpanel/wpm_cron_cpanel.sh"
elif [ $control_panel -eq 2 ]; then
    cron_file="$BASE_DIR/wpm_bash/plesk/wpm_cron_plesk.sh"
fi

echo -e "\t${greyColor}Modify USER_GROUP=${endColor} ${greenColor}$user_group ${endColor} ${greyColor}in file ${endColor} ${greenColor}$cron_file ${endColor} ${greyColor}(CRON)${endColor}"

if [ "$check" = false ]; then
    sed -i "s/USER_GROUP=\"false\"/USER_GROUP=\"$user_group\"/" $cron_file
fi


echo -e "\t${greyColor}Modify PATH_DATA=${endColor} ${greenColor}$PATH_DATA ${endColor} ${greyColor}in file ${endColor} ${greenColor}$cron_file ${endColor} ${greyColor}(CRON)${endColor}"
if [ "$check" = false ]; then
    if [ "$BASE_DIR" != "/opt/wp_monitor" ]; then
        sed -i "s|PATH_DATA=\"/opt/wp_monitor/wpm_data/\"|PATH_DATA=\"$PATH_DATA\"|" "$cron_file"
    fi
fi

# 4. Ask for CRON creation
echo -n -e "\n${yellowColor}You want to create the CRON task automatically? (Y/N): ${endColor}"
read create_cron

if [[ "$create_cron" == "Y" || "$create_cron" == "y" ]]; then
    echo -n -e "\n${yellowColor}Every how many minutes should the CRON be executed?: ${endColor}"
    read interval

    # 4.2 Determine CRON to execute
    if [ "$control_panel" -eq 1 ];then
        cron_file="$BASE_DIR/wpm_bash/cpanel/wpm_cron_cpanel.sh"
    elif [ "$control_panel" -eq 2 ]; then
        cron_file="$BASE_DIR/wpm_bash/plesk/wpm_cron_plesk.sh"
    fi

    # 4.3 Make cron_file executable
    chmod +x "$cron_file"

    if [ "$check" = false ]; then
        create_cron_job "$interval" "$cron_file"
    fi

    echo -e "\t${greyColor}CRON job created to run every ${endColor} ${greenColor}$interval${endColor} ${greyColor} minuts ('*/$interval * * * * $script')${endColor}"
fi

# 5. Ask to install systemd service
echo -n -e "\n${yellowColor}You want to install the service 'jobs'? (Y/N): ${endColor}"
read install_service

if [[ "$install_service" == "Y" || "$install_service" == "y" ]]; then
    install_service
    # 5.1 Ask to start the service
    echo -n -e "\n${yellowColor}You want to start the service now? (Y/N):  ${endColor}"
    read start_service_now
    if [[ "$start_service_now" == "Y" || "$start_service_now" == "y" ]];then
        start_service
    fi
fi

# Ask the user about email configuration
echo -n -e "\n${yellowColor}Do you want to activate email sending? (Y/N): ${endColor}"
read activate_email
if [[ "$activate_email" == "Y" || "$activate_email" == "y" ]];then
    # Set email to true in config.json
    config_path="$BASE_DIR/wp_monitor/config.json"
    if [ "$check" = false ]; then
        jq '.email = true' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
    fi
    echo -n -e "\n${yellowColor}Do you want to use SMTP? (Y/N): ${endColor}"
    read use_smtp
    if [[ "$use_smtp" == "Y" || "$use_smtp" == "y" ]];then
        # Set smtp to true in config.json and ask for SMTP details
        if [ "$check" = false ]; then
            jq '.smtp = true' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
        echo -n -e "\n${yellowColor}Enter SMTP server: ${endColor}"
        read smtp_server
        if [ "$check" = false ]; then
            jq --arg smtp_server "$smtp_server" '.smtp_server = $smtp_server' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
        echo -n -e "\n${yellowColor}Enter SMTP port: ${endColor}"
        read smtp_port
        if [ "$check" = false ]; then
            jq --arg smtp_port "$smtp_port" '.smtp_port = $smtp_port' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
        echo -n -e "\n${yellowColor}Enter SMTP secure (ssl/tls): ${endColor}"
        read smtp_secure
        if [ "$check" = false ]; then
            jq --arg smtp_secure "$smtp_secure" '.smtp_secure = $smtp_secure' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
        echo -n -e "\n${yellowColor}Enter SMTP user (normally email address): ${endColor}"
        read smtp_user
        if [ "$check" = false ]; then
            jq --arg smtp_user "$smtp_user" '.smtp_user = $smtp_user' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
        echo -n -e "\n${yellowColor}Enter SMTP password: ${endColor}"
        read -s smtp_password
        if [ "$check" = false ]; then
            jq --arg smtp_password "$smtp_password" '.smtp_password = $smtp_password' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
        echo -n -e "\n\n${yellowColor}Enter email from (normally same address in SMTP user): ${endColor}"
        read email_from
        if [ "$check" = false ]; then
            jq --arg email_from "$email_from" '.email_from = $email_from' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
        echo -n -e "\n${yellowColor}Enter email from title (ex. WP Monitor): ${endColor}"
        read email_from_title
        if [ "$check" = false ]; then
            jq --arg email_from_title "$email_from_title" '.email_from_title = $email_from_title' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
        # Download PHPMailer
        lib_path="$document_root/lib"
        if [ "$check" = false ]; then
            download_phpmailer "$lib_path"
        fi
    else
        # Ask for the email from address for mail (php)
        echo -n -e "\n${yellowColor}Enter email from wich you want to send emails: ${endColor}"
        read email_from
        if [ "$check" = false ]; then
            jq --arg email_from "$email_from" '.email_from = $email_from' "$config_path" > "$config_path.tmp" && mv "$config_path.tmp" "$config_path"
        fi
    fi
fi
echo -e "\n\n${greenColor}--------------- WP Monitor has been installed successfully ---------------${endColor}"
