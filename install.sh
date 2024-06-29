#!/bin/bash

BASE_DIR=$(dirname "$(realpath "$0")")
PATH_DATA="$BASE_DIR/wpm_data/"

echo "WP Monior base dir: $BASE_DIR"

# Check that everything is configured correctly before proceed
check=true

# Parse no-check args
for arg in "$@"; do
    if [ "$arg" == "no-check" ]; then
        check=false
    fi
done

detect_control_panel() {
    for dir in "/usr/local/cpanel" "/var/cpanel" "/etc/cpanel"; do
        if [ -d "$dir" ]; then
            echo "cPanel detected"
            return 1
        fi
    done

    for dir in "/usr/local/psa" "/opt/psa" "/etc/psa"; do
        if [ -d "$dir" ];then
            echo "Plesk detected"
            return 2
        fi
    done

    echo "Installation not allowed"
    exit 1
}

check_wp_toolkit() {

    local control_panel=$1

    if [ "$control_panel" -eq 1 ]; then
        if command -v /usr/local/cpanel/bin/wp-toolkit &> /dev/null; then
            echo "WP Toolkit is installed in cPanel."
        else
            echo "WP Toolkit is not installed in cPanel. Installation not permitted."
            exit 1
        fi
    elif [ "$control_panel" -eq 2 ]; then
        if plesk bin extension --list | grep -q "wp-toolkit"; then
            echo "WP Toolkit is installed in Plesk."
        else
            echo "WP Toolkit is not installed in Plesk. Installation not allowed."
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
        document_root=$(plesk bin site --info $domain | grep "WWW-Root" | awk '{print $2}')
        if [ -z "$document_root" ];then
            document_root=$(plesk bin subdomain --info $domain | grep "WWW-Root" | awk '{print $2}')
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
        echo "Copying files to $document_root/"
        if [ "$check" = false ]; then
            cp -r "$BASE_DIR/wpm_www/"* "$document_root/"
        fi
    else
        echo "Creating directory $document_root/wp_monitor"
        if [ "$check" = false ]; then
            mkdir -p "$document_root/wp_monitor"
        fi
        echo "Copying files to $document_root/wp_monitor/"
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
        echo "Service installed from $source_service to $service_path"
    else
        echo "Service file not found at $source_service"
        exit 1
    fi
}

start_service() {
    if [ "$check" = false ]; then
        systemctl start wp_monitor_jobs.service
        systemctl enable wp_monitor_jobs.service
    fi
    echo "Service started and enabled"
}

# Update paths in wpm_jobs.sh
update_wpm_jobs_paths() {
    WPM_JOBS_FILE="$BASE_DIR/wpm_bash/wpm_jobs.sh"

    # Ensure the wpm_jobs.sh file exists
    if [[ ! -f "$WPM_JOBS_FILE" ]]; then
        echo "Error: $WPM_JOBS_FILE not found!"
        exit 1
    fi

    # Update paths in wpm_jobs.sh
    sed -i "s|^JOBS_FILE=.*|JOBS_FILE=\"$BASE_DIR/wp_monitor/jobs\"|" "$WPM_JOBS_FILE"
    sed -i "s|^JOBS_EXECUTED_FILE=.*|JOBS_EXECUTED_FILE=\"$BASE_DIR/jobs_executed\"|" "$WPM_JOBS_FILE"
    sed -i "s|^LOCK_FILE=.*|LOCK_FILE=\"$BASE_DIR/wpm_data/tmp/wp_monitor.lock\"|" "$WPM_JOBS_FILE"
}

# 1. Detect control panel
detect_control_panel
control_panel=$?

echo "Control Panel: $control_panel (1=cPanel, 2=Plesk)"

# Check if WP Toolkit is installed
check_wp_toolkit $control_panel

# 2. Ask for domain and validate
while true; do
    echo -n "What domain do you want to install the interface on?
? "
    read domain
    if validate_domain $domain; then
        document_root=$(find_document_root "$domain" $control_panel)
        if [ -n "$document_root" ];then
            echo "Domain found in $document_root"
            break
        else
            echo "The root directory of the domain was not found. Please re-enter the domain."
        fi
    else
        echo "Invalid domain. Please re-enter the domain."
    fi
done

# 3. Copy wpm_www content
echo -n "Do you want to install wp_monitor at the root of the domain? (Y/N):"
read install_in_root
if [[ "$install_in_root" == "Y" || "$install_in_root" == "y" ]];then
    install_wp_monitor "$document_root" true
else
    install_wp_monitor "$document_root" false
fi

# 3.1 Get user:group and apply permissions
echo "Getting user:group for www"
user_group=$(stat -c "%U:%G" "$document_root")
echo "user:group: $user_group"

# Apply permissions conditionally based on installation directory
if [[ "$install_in_root" == "Y" || "$install_in_root" == "y" ]]; then
    echo "Permissions will be applied $user_group a $document_root"
    if [ "$check" = false ]; then
        chown -R "$user_group" "$document_root"
    fi
else
    echo "Permissions will be applied $user_group a $document_root/wp_monitor"
    if [ "$check" = false ]; then
        chown -R "$user_group" "$document_root/wp_monitor"
    fi
fi

echo "Permissions will be applied $user_group a $BASE_DIR/wpm_data"
if [ "$check" = false ]; then
    chown -R "$user_group" "$BASE_DIR/wpm_data"
fi

# Modify BASE_DIR in wpm_bash/wpm_jobs.sh
echo "BASE_DIR updated in wpm_bash/wpm_jobs.sh"
if [ "$check" = false ]; then
    update_wpm_jobs_paths
fi

# Modify USER_GROUP in CRON scripts
if [ $control_panel -eq 1 ]; then
    cron_file="$BASE_DIR/wpm_bash/cpanel/wpm_cron_cpanel.sh"
elif [ $control_panel -eq 2 ]; then
    cron_file="$BASE_DIR/wpm_bash/plesk/wpm_cron_plesk.sh"
fi

echo "Modify USER_GROUP=$user_group in file $cron_file (CRON)"

if [ "$check" = false ]; then
    sed -i "s/USER_GROUP=\"false\"/USER_GROUP=\"$user_group\"/" $cron_file
fi


echo "Modify PATH_DATA=$PATH_DATA in file $cron_file (CRON)"
if [ "$check" = false ]; then
    if [ "$BASE_DIR" != "/opt/wp_monitor" ]; then
        sed -i "s|PATH_DATA=\"/opt/wp_monitor/wpm_data/\"|PATH_DATA=\"$PATH_DATA\"|" "$cron_file"
    fi
fi

# 4. Ask for CRON creation
echo -n "You want to create the CRON task automatically? (Y/N): "
read create_cron

if [[ "$create_cron" == "Y" || "$create_cron" == "y" ]]; then
    echo -n "Every how many minutes should the CRON be executed?: "
    read interval

    # 4.2 Determine CRON to execute
    if [ "$control_panel" -eq 1 ];then
        cron_file="$BASE_DIR/wpm_bash/cpanel/wpm_cron_cpanel.sh"
    elif [ "$control_panel" -eq 2 ]; then
        cron_file="$BASE_DIR/wpm_bash/plesk/wpm_cron_plesk.sh"
    fi

    # 4.3 Make cron_file executable
    chmod +x "$cron_file"

    # 4.2 Modify USER_GROUP in the corresponding script and create CRON
    echo "The USER_GROUP variable of the file is set $cronfile to USER_GROUP=$user_group"
    if [ "$check" = false ]; then
        sed -i "s/USER_GROUP=\"false\"/USER_GROUP=\"$user_group\"/" "$cron_file"
        create_cron_job "$interval" "$cron_file"
    fi


    echo "CRON job created to run ever $interval minutos ('*/$interval * * * * $script')"
fi

# 5. Ask to install systemd service
echo -n "You want to install the service 'jobs'? (Y/N): "
read install_service

if [[ "$install_service" == "Y" || "$install_service" == "y" ]]; then
    install_service
    # 5.1 Ask to start the service
    echo -n "You want to start the service now? (Y/N): "
    read start_service_now
    if [[ "$start_service_now" == "Y" || "$start_service_now" == "y" ]];then
        start_service
    fi
fi

echo "WP Monitor has been installed successfully"
