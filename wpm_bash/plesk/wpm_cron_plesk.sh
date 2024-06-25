#!/bin/bash

PLESK_CMD="/usr/sbin/plesk"
BASE_DOMAINS_DIR="/var/www/vhosts"
ERROR_LOG_FILE="error_log"
USER_GROUP="false"
PATH_DATA="/opt/wp_monitor/wpm_data/"
PATH_LOGS="logs/"
WPM_INFO_JSON="wpi.json"
RECIPIENT="example @ gmail . com"
SUBJECT="WP_Monitor Summary"
BODY="WP_Monitor Summary"

# Array to save unique domains
declare -a unique_domains=()

# Get the list of WordPress installations and save to a JSON file
$PLESK_CMD ext wp-toolkit --list -format json > "$PATH_DATA$WPM_INFO_JSON"

# Run the command to list WordPress installations and loop through each line
$PLESK_CMD ext wp-toolkit --list | while read -r line; do
    # Extract the column ID
    id=$(echo "$line" | awk '{print $1}')

    # Checks if the line contains a valid ID
    if [[ "$id" =~ ^[0-9]+$ ]]; then
        # Run the command to get additional information about the Wordpress ID
        $PLESK_CMD ext wp-toolkit --info -format json -instance-id "$id" > "$PATH_DATA$id.json"
    fi
done

# Path to global WP INFO JSON
JSON_FILE="$PATH_DATA$WPM_INFO_JSON"

# Extract list of unique domains from "siteUrl" column using jq and awk to get only root domains
unique_domains=$(jq -r '.[].siteUrl' "$JSON_FILE" | awk -F/ '{print $3}' | awk -F[/:] '{print $1}' | awk -F. '{print $(NF-1)"."$NF}' | sort -u)

#printf '%s\n' "$unique_domains"

# Directory where the compressed logs will be saved
LOGS_DIR="$PATH_DATA$PATH_LOGS"

# Iterate over the list of unique domains
for domain in $unique_domains; do
    # Check if the "error_log" file exists in the domain directory
    if [ -f "$BASE_DOMAINS_DIR/$domain/$PATH_LOGS$ERROR_LOG_FILE" ]; then
        # Create a compressed copy of the "error_log" file
        tar -czf "$LOGS_DIR${domain}_$ERROR_LOG_FILE.tar.gz" -C "$BASE_DOMAINS_DIR/$domain/$PATH_LOGS" $ERROR_LOG_FILE
    fi
done

# If USER_GROUP is set and not false, change directory ownership recursively
if [ ! -z "$USER_GROUP" ] && [ "$USER_GROUP" != "false" ]; then
    chown -R "$USER_GROUP" "$PATH_DATA"
fi

# Send the summary by email with the JSON file attached
#echo "$BODY" | mail --attach=$PATH_DATA$WPM_INFO_JSON -s "$SUBJECT" $RECIPIENT
