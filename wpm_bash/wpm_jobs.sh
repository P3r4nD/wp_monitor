#!/bin/bash

# Rutas a los archivos
JOBS_FILE="/opt/wp_monitor/jobs"
JOBS_EXECUTED_FILE="/opt/wp_monitor/jobs_executed"
LOCK_FILE="/opt/wp_monitor/wpm_data/tmp/wp_monitor.lock"
PANEL=""

# Record the result of a job
log_job_execution() {
    local job=$1
    local status=$2
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "$timestamp - $job - $status" >> $JOBS_EXECUTED_FILE
}

# Update WordPress version
update_wordpress() {
    local wp_id=$1
    local job="Update WordPress ID $wp_id"
    echo "Updating WordPress for ID installation $wp_id"
    if [ "$PANEL" = "2" ]; then
        response=$(/usr/sbin/plesk ext wp-toolkit --update $wp_id 2>&1)
    elif [ "$PANEL" = "1" ]; then
        response=/usr/local/bin/wp-toolkit --update $wp_id 2>&1
    fi
    if [ $? -eq 0 ]; then
        log_job_execution "$job" "Success"
    else
        log_job_execution "$job" "Failed: $response"
    fi
}

# Update plugin
update_plugin() {
    local wp_id=$1
    local plugin_name=$2
    local job="Update plugin $plugin_name for installation ID $wp_id"
    echo "Updating plugin $plugin_name for installation ID $wp_id"
    if [ "$PANEL" = "2" ]; then
        response=$(/usr/sbin/plesk ext wp-toolkit --plugin update $wp_id --name=$plugin_name 2>&1)
    elif [ "$PANEL" = "1" ]; then
        response=/usr/local/bin/wp-toolkit --plugin update $wp_id --name=$plugin_name 2>&1
    fi
    if [ $? -eq 0 ]; then
        log_job_execution "$job" "Success"
    else
        log_job_execution "$job" "Failed: $response"
    fi
}

# Update theme
update_template() {
    local wp_id=$1
    local template_name=$2
    local job="Update template $template_name for installation ID $wp_id"
    echo "Updating template $template_name for installation ID $wp_id"
    if [ "$PANEL" = "2" ]; then
        response=$(/usr/sbin/plesk ext wp-toolkit --theme update $wp_id --name=$template_name 2>&1)
    elif [ "$PANEL" = "1" ]; then
        response=/usr/local/bin/wp-toolkit --theme update $wp_id --name=$template_name 2>&1
    fi
    if [ $? -eq 0 ]; then
        log_job_execution "$job" "Success"
    else
        log_job_execution "$job" "Failed: $response"
    fi
}

# Disable plugin
disable_plugin() {
    local wp_id=$1
    local plugin_name=$2
    local job="Disable plugin $plugin_name for installation ID $wp_id"
    echo "Disabling plugin $plugin_name for installation ID $wp_id"
    if [ "$PANEL" = "2" ]; then
        response=$(/usr/sbin/plesk ext wp-toolkit --plugin deactivate $wp_id --name=$plugin_name 2>&1)
    elif [ "$PANEL" = "1" ]; then
        response=/usr/local/bin/wp-toolkit --plugin deactivate $wp_id --name=$plugin_name 2>&1
    fi
    if [ $? -eq 0 ]; then
        log_job_execution "$job" "Success"
    else
        log_job_execution "$job" "Failed: $response"
    fi
}

while true; do
    # Try to acquire the lock
    exec 200>$LOCK_FILE
    flock -n 200 || { echo "The lock could not be acquired"; sleep 5; continue; }

    # Process each line in the job file
    while IFS= read -r line || [[ -n "$line" ]]; do
        # Skip empty lines
        [ -z "$line" ] && continue

        # Read job type and parameters
        job_type=$(echo $line | cut -d ' ' -f 1)
        wp_id=$(echo $line | cut -d ' ' -f 2)
        name=$(echo $line | cut -d ' ' -f 3)

        # Run job based on type
        case $job_type in
            update_wp)
                update_wordpress $wp_id
                ;;
            update_plugin)
                update_plugin $wp_id $name
                ;;
            update_template)
                update_template $wp_id $name
                ;;
            disable_plugin)
                disable_plugin $wp_id $name
                ;;
            *)
                echo "Unknown job type: $job_type"
                ;;
        esac
    done < $JOBS_FILE

    # Clean up the job file after processing
    > $JOBS_FILE

    # Release the lock
    flock -u 200

    # Wait 5 seconds before next iteration
    sleep 5
done
