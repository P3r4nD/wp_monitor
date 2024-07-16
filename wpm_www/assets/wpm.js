/**
 * @fileoverview Functions for WordPress Whatcher interface logic
 * @version 1.0.4
 * @date 2024-07-11
 * 
 * @summary This javascript includes functions to handle events of forms, 
 * API calls and different structural modifications to the DOM of the web interface.
 * 
 * @description This file contains JavaScript functions essential for the WP Monitor interface logic. 
 * It includes event handlers for forms, functions for making API calls, and utilities for modifying 
 * the DOM structure of the web interface. The goal is to enhance user interaction and ensure 
 * smooth communication with the backend services.
 * 
 * @author @P3r4nD
 * @license GPL v3.0
 */

document.addEventListener("DOMContentLoaded", function () {

    const api_url = app_url; // Api url. [app_url] is printed by PHP in index.php
    const wpModalElement = document.getElementById('wpm-modal'); // Main modal
    const wpmModal = new bootstrap.Modal(wpModalElement, {
        backdrop: 'static',
        keyboard: false
    }); // Init bootstrap modal for main WP Monitor modal
    const pluginTable = document.getElementById('table-plugins'); // Plugins table inside modal
    const themeTable = document.getElementById('table-themes'); // Themes table inside modal
    const tableBody = document.querySelector("#table-wpm tbody"); // Table body for main table. Thistable shows WordPress instalations.
    const updateWpCheckbox = document.getElementById('update-wp-checkbox'); //Checkbox to mark update wordpress job
    const sendButton = document.getElementById('send-mail'); // Send email button
    const saveButton = document.getElementById("save-jobs"); // Save jobs button
    const confirmModalElement = document.getElementById('confirmModal');
    const confirmModal = new bootstrap.Modal(confirmModalElement, {
        backdrop: 'static',
        keyboard: false
    });
    let jobs = {};
    const jobsList = document.getElementById('jobs-list');
    const confirmJobsButton = document.getElementById('confirm-jobs');

    const logForm = document.getElementById("log-form"); // Form to search in logs
    const checkboxMailCopy = document.getElementById('mail-copy'); // Checkbox to show email-copy-data input field
    const inputMailsCopy = document.getElementById('mail-copy-data'); // Comma separated emails to which a copy of the email will be sent
    const alertPlaceholder = document.getElementById('boxAlert'); // Box alerts for Email tab section
    const progressBar = document.getElementById('progressBar'); // Progress bar showing time interval beteen reloads
    const interval = app_reload_time; // Total interval in milliseconds before reload data
    const updateTime = 100; // Progress update time in milliseconds
    let progress = 0; // Progress bar initial status
    let increment = 100 / (interval / updateTime); // Progress bar incremental status

    /**
     * Function to show save job button
     *
     */
    const showSaveButton = () => {
        saveJobsBtn = document.getElementById("save-jobs");
        saveJobsBtn.classList.remove('d-none');
    };
    /**
     * Function to hide save job button
     *
     */
    const hideSaveButton = () => {
        saveJobsBtn = document.getElementById("save-jobs");
        saveJobsBtn.classList.add('d-none');
    };

    /**
     * Function to check if element has a class
     *
     * @param {HTMLElement} element - The element to search class
     * @param {string} cls - The class name
     * @returns {boolean} `true` if element has class, false if not
     */
    function hasClass(element, cls) {
        return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
    }

    /**
     * Function to show messages in logs tab.
     *
     * @param {string} message - The message to show
     * 
     */
    function displayLogMessage(message) {

        var alertBox = document.getElementById("msg-logs");

        alertBox.innerHTML = message;

        if (false == hasClass(alertBox, 'd-none')) {

        } else {
            alertBox.classList.toggle("d-none");
        }
    }

    /**
     * Function to show toast messages.
     *
     * @param {string} imgSrc - Image url.
     * @param {string} title - The toast title
     * @param {string} time - Time of execution
     * @param {string} message - The toast message
     */
    function showToastedMessage(imgSrc, title, time, message) {

        const toastBox = document.getElementById('liveToastedMessage');

        if (toastBox) {

            // Clone the template
            const newToast = toastBox.cloneNode(true);

            // Update the content
            //newToast.querySelector('img').src = imgSrc;
            newToast.querySelector('strong').textContent = title;
            newToast.querySelector('small').textContent = time;
            newToast.querySelector('.toast-body').textContent = message;

            // Append the new toast to the container
            const toastContainer = document.querySelector('.toast-container');
            toastContainer.innerHTML = "";
            toastContainer.appendChild(newToast);

            // Initialize and show the toast using Bootstrap's Toast API
            const bsToast = new bootstrap.Toast(newToast);
            bsToast.show();
        } else {
            console.error('Toast template not found!');
        }
    }

    /**
     * Function to toggle button submit or searchiing
     *
     * @param {string} imgSrc - Image url
     * 
     */
    function btnSubmitChange(status) {

        var btn_submit = document.getElementById("btn-submit");
        var btn_searching = document.getElementById("btn-searching");

        if (status == "searching") {
            btn_submit.classList.add("d-none");
            btn_searching.classList.remove("d-none");
        } else {
            btn_submit.classList.remove("d-none");
            btn_searching.classList.add("d-none");
        }
    }

    /**
     * Function to clear the main wp-container, where main table is loaded
     *
     */
    function clearWordpressContainer() {

        var tlb = document.querySelector('#wp-container');
        tlb.innerHTML = "";
    }

    /**
     * Function to clear the plugins table
     *
     */
    function clearPluginsTable() {

        var tlb = document.querySelector('#table-plugins tbody');
        tlb.innerHTML = "";
    }

    /**
     * Function to clear themes table
     *
     */
    function clearThemesTable() {

        var tlb = document.querySelector('#table-themes tbody');
        tlb.innerHTML = "";
    }

    /**
     * Function to clear logs table
     *
     */
    function clearLogsTable() {

        var tlb = document.querySelector('#table-logs tbody');
        tlb.innerHTML = "";
    }
    /**
     * Function to reset email form
     *
     */
    function clearEmailForm() {
        // Get the form element
        const form = document.getElementById('email-form');
        // Reset the form
        if (form) {
            form.reset();

            // Hide the mail-copy-data input if the checkbox is not checked
            const mailCopyData = document.getElementById('mail-copy-data');
            const mailCopyCheckbox = document.getElementById('mail-copy');
            if (!mailCopyCheckbox.checked) {
                mailCopyData.classList.add('d-none');
            }
        }
    }

    /**
     * Hide logs message box
     *
     */
    function hideLogMessage() {

        var alertBox = document.getElementById("msg-logs");
        alertBox.innerHTML = "";
        alertBox.classList.add("d-none");

    }

    /**
     * Main function to get wp installation data and open modal with wp data table.
     * Also it's used to manage events in wp installation table, change the status of rows, and reloa data
     *
     */
    function addRowHandlers() {

        var wpc = document.querySelector('#wp-container');
        //Table plugins
        var tp = document.querySelector('#table-plugins');
        var tpb = document.querySelector('#table-plugins tbody');
        //Table themes
        var tt = document.querySelector('#table-themes');
        var ttb = document.querySelector('#table-themes tbody');
        //Table SSL
        var ts = document.querySelector('#table-ssl');
        var tsb = document.querySelector('#table-ssl tbody');
        //Table Logs
        var tl = document.querySelector('#table-logs');
        var tlb = document.querySelector('#table-logs tbody');

        //Table Email Log
        var tml = document.querySelector('#table-mail-logs');
        var tmb = document.querySelector('#table-mail-logs tbody');

        var table = document.getElementById("table-wpm");
        var rows = table.getElementsByTagName("tr");

        var wp_id = document.getElementById("wp-install-id");

        var wp_current_version = document.getElementById("wp-current-version");
        var wp_last_version = document.getElementById("wp-last-version");

        // Create container for Wordpress tab. Show if it's updated or not
        const createWordpressContainer = (o, v, uv) => {
            if (o) {
                return `
                    <div class="form-check">
                        <p>Current version: <span id="wp-current-version">${v}</span><p>
                        <input class="form-check-input" type="checkbox" value="" id="update-wp-checkbox">
                        <label class="form-check-label" for="update-wp-checkbox">
                            Update to WordPress <span id="wp-last-version">${uv}</span>
                        </label>
                    </div>
                `;
            } else {
                return `
                    <div class="form-check">
                        <p>WordPress version is updated: <span id="wp-current-version">${v}</span><p>

                    </div>
                `;
            }
        };

        // Create actions menu select for each Plugin, update/disable options
        const createPluginSelect = (slug) => {
            return `
                <select class="form-select form-select-sm plugin-action-select" data-plugin-slug="${slug}">
                    <option value="Seleccionar...">Seleccionar...</option>
                    <option value="update">Actualizar</option>
                    <option value="disable">Desactivar</option>
                </select>
            `;
        };
        
        // Create actions menu select for Theme update/disable options
        const createThemeSelect = (slug) => {
            return `
                <select class="form-select form-select-sm theme-action-select" data-theme-slug="${slug}">
                    <option value="Seleccionar...">Seleccionar...</option>
                    <option value="update">Actualizar</option>
                    <option value="disable">Desactivar</option>
                </select>
            `;
        };

        for (i = 0; i < rows.length; i++) {

            var currentRow = table.rows[i];

            // Handler clicks in table rows
            var createClickHandler = function (row) {
                return function () {
                    // Clear logs table
                    clearLogsTable();
                    document.getElementById('wpm-modal').dataset.currentRow = row.rowIndex;
                    var cell = row.getElementsByTagName("td")[0];
                    var cell_title = row.getElementsByTagName("td")[0];
                    var id = row.getAttribute("data-code").trim();
                    const fetchOptions = {
                        method: "GET"
                    };
                    let fetchRes = fetch(
                            app_url + "?id=" + id, fetchOptions);
                    fetchRes.then(res =>
                        res.json()).then(d => {
                        //Empty plugins table
                        while (tpb.childNodes.length) {
                            tpb.removeChild(tpb.childNodes[0]);
                        }
                        //Empty themes table
                        while (ttb.childNodes.length) {
                            ttb.removeChild(ttb.childNodes[0]);
                        }

                        wpc.innerHTML = `${createWordpressContainer(d.outdatedWp, d.version, d.update_version)}`;

                        wp_id.value = d.id;

                        //Fill logs table
                        if (d.logs.length) {
                            for (const log in d.logs) {
                                let tr = document.createElement('tr');
                                //Name cell
                                let td_log = document.createElement('td');
                                td_log.innerHTML = d.logs[log];
                                td_log.className = 'table-cell';
                                tr.appendChild(td_log);
                                tlb.appendChild(tr);
                            }
                        } else {

                            displayLogMessage(d.logs_msg);
                        }
                        //Fill plugins table
                        for (const plugin in d.plugins) {
                            let tr = document.createElement('tr');
                            if (d.plugins[plugin]['update_version']) {
                                tr.className = 'table-danger';
                            }
                            //Name cell
                            let td_name = document.createElement('td');
                            td_name.innerHTML = d.plugins[plugin]['title'];
                            td_name.className = 'table-cell';
                            tr.appendChild(td_name);

                            //Slug cell
                            let td_slug = document.createElement('td');
                            td_slug.innerHTML = d.plugins[plugin]['name'];
                            td_slug.className = 'table-cell';
                            tr.appendChild(td_slug);

                            //Status cell
                            let td_status = document.createElement('td');
                            td_status.innerHTML = d.plugins[plugin]['status'];
                            td_status.className = 'table-cell';
                            tr.appendChild(td_status);

                            //Version cell
                            let td_version = document.createElement('td');
                            td_version.innerHTML = d.plugins[plugin]['version'];
                            td_version.className = 'table-cell';
                            tr.appendChild(td_version);

                            //Update cell
                            let td_upversion = document.createElement('td');
                            td_upversion.innerHTML = d.plugins[plugin]['update_version'];
                            td_upversion.className = 'table-cell';
                            tr.appendChild(td_upversion);

                            //Update cell
                            let td_autoup = document.createElement('td');
                            td_autoup.innerHTML = d.plugins[plugin]['autoUpdates'];
                            td_autoup.className = 'table-cell';
                            tr.appendChild(td_autoup);

                            let td_jobs = document.createElement('td');
                            td_jobs.innerHTML = "";
                            if (d.plugins[plugin]['update_version']) {
                                td_jobs.innerHTML = `${createPluginSelect(d.plugins[plugin]['name'])}`;
                            }
                            td_jobs.className = 'table-cell';
                            tr.appendChild(td_jobs);

                            tpb.appendChild(tr);
                        }

                        //Fill themes table
                        for (const theme in d.themes) {
                            let trt = document.createElement('tr');
                            if (d.themes[theme]['update_version']) {
                                trt.className = 'table-danger';
                            }
                            //Name cell
                            let td_name = document.createElement('td');
                            td_name.innerHTML = d.themes[theme]['title'];
                            td_name.className = 'table-cell';
                            trt.appendChild(td_name);

                            //Slug cell
                            let td_slug = document.createElement('td');
                            td_slug.innerHTML = d.themes[theme]['name'];
                            td_slug.className = 'table-cell';
                            trt.appendChild(td_slug);

                            //Status cell
                            let td_status = document.createElement('td');
                            td_status.innerHTML = d.themes[theme]['status'];
                            td_status.className = 'table-cell';
                            trt.appendChild(td_status);

                            //Version cell
                            let td_version = document.createElement('td');
                            td_version.innerHTML = d.themes[theme]['version'];
                            td_version.className = 'table-cell';
                            trt.appendChild(td_version);

                            //Update cell
                            let td_upversion = document.createElement('td');
                            td_upversion.innerHTML = d.themes[theme]['update_version'];
                            td_upversion.className = 'table-cell';
                            trt.appendChild(td_upversion);

                            //Update cell
                            let td_autoup = document.createElement('td');
                            td_autoup.innerHTML = d.themes[theme]['autoUpdates'];
                            td_autoup.className = 'table-cell';
                            trt.appendChild(td_autoup);

                            let theme_td_jobs = document.createElement('td');
                            theme_td_jobs.innerHTML = "";
                            if (d.themes[theme]['update_version'] && d.themes[theme]['status'] == 'active') {
                                theme_td_jobs.innerHTML = `${createThemeSelect(d.themes[theme]['name'])}`;
                            }
                            theme_td_jobs.className = 'table-cell';
                            trt.appendChild(theme_td_jobs);

                            ttb.appendChild(trt);
                        }

                        //Fill SSL table
                        let tst = document.createElement('tr');
                        //SSL active cell
                        let td_ssl = document.createElement('td');
                        td_ssl.innerHTML = d.sslStatus['isSslEnabled'];
                        td_ssl.className = 'table-cell';
                        tst.appendChild(td_ssl);
                        tsb.appendChild(tst);

                        //SSL redirected cell
                        let td_rssl = document.createElement('td');
                        td_rssl.innerHTML = d.sslStatus['isRedirectToHttpsEnabled'];
                        td_rssl.className = 'table-cell';
                        tst.appendChild(td_rssl);

                        //SSL URL cell
                        let td_ussl = document.createElement('td');
                        td_ussl.innerHTML = d.sslStatus['isUrlProtocolHttps'];
                        td_ussl.className = 'table-cell';
                        tst.appendChild(td_ussl);

                        //SSL certificate cell
                        let td_cssl = document.createElement('td');
                        td_cssl.innerHTML = d.sslStatus['isCertificateInstalled'];
                        td_cssl.className = 'table-cell';
                        tst.appendChild(td_cssl);

                        //SSL selfsigned cell
                        let td_sssl = document.createElement('td');
                        td_sssl.innerHTML = d.sslStatus['isSelfSigned'];
                        td_sssl.className = 'table-cell';
                        tst.appendChild(td_sssl);

                        //SSL udpated cell
                        let td_upssl = document.createElement('td');
                        td_upssl.innerHTML = d.sslStatus['isActual'];
                        td_upssl.className = 'table-cell';
                        tst.appendChild(td_upssl);

                        //SSL issuer cell
                        let td_issl = document.createElement('td');
                        td_issl.innerHTML = d.sslStatus['issuerName'];
                        td_issl.className = 'table-cell';
                        tst.appendChild(td_issl);

                        tsb.appendChild(tst);

                        //Add WP url to modal title
                        const modalTitle = wpModalElement.querySelector('.modal-title');
                        modalTitle.textContent = cell_title.innerHTML;

                        let input_hidden = document.getElementById("url_domain");
                        input_hidden.setAttribute('value', cell_title.innerHTML);
                        wpmModal.show();

                        //Fill email section
                        let emailAdmin = document.getElementById("email-admin");
                        
                        if (emailAdmin) {
                            emailAdmin.textContent = d.admin_email;

                            //Fill email logs table
                            if (d.email_log.length) {
                                for (const l in d.email_log) {
                                    let tr = document.createElement('tr');
                                    //Name cell
                                    let td_log = document.createElement('td');
                                    td_log.innerHTML = d.email_log[l];
                                    td_log.className = 'table-cell';
                                    tr.appendChild(td_log);
                                    tmb.appendChild(tr);
                                }
                            } else {

                                displayLogMessage(d.logs_msg);
                            }
                        }
                    });
                };
            };

            if (currentRow.classList.contains('pending-jobs')) {
                currentRow.onclick = function () {
                    // Mostrar segundo modal con el warning
                    const warningModal = new bootstrap.Modal(document.getElementById('warningModal'));
                    warningModal.show();
                };
            } else {
                currentRow.onclick = createClickHandler(currentRow);
            }
        }
    }

    /**
     * Helper function for POSTing data as JSON with fetch.
     *
     * @param {string} options.url - URL to POST data to
     * @param {FormData} options.formData - `FormData` instance
     */
    async function postFormDataAsJson( { url, formData }) {
        const plainFormData = Object.fromEntries(formData.entries());
        const formDataJsonString = JSON.stringify(plainFormData);

        const fetchOptions = {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: formDataJsonString
        };

        const response = await fetch(url, fetchOptions);

        if (!response.ok) {
            const errorMessage = await response.text();
            throw new Error(errorMessage);
        }

        return response.json();
    }

    /**
     * Event handler for a form submit event.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLFormElement/submit_event
     *
     * @param {SubmitEvent} event
     */
    async function handleFormSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const url = app_url;

        clearLogsTable();
        hideLogMessage();
        btnSubmitChange("searching");

        try {
            const formData = new FormData(form);
            const responseData = await postFormDataAsJson({url, formData});

            if (!responseData.logs.length) {
                displayLogMessage(responseData.logs_msg);
            } else {
                for (const log in responseData.logs) {
                    let tr = document.createElement('tr');
                    //Name cell
                    var tlb = document.querySelector('#table-logs tbody');
                    let td_log = document.createElement('td');
                    td_log.innerHTML = responseData.logs[log];
                    td_log.className = 'table-cell';
                    tr.appendChild(td_log);
                    tlb.appendChild(tr);
                }
            }
            btnSubmitChange("submit");
        } catch (error) {
            console.error(error);
        }
    }

    // Click event for the button that saves the jobs to be performed
    saveButton.addEventListener('click', function () {

        jobs = {
            wp_action: "do_jobs",
            wp_id: document.getElementById('wp-install-id').value,
            wp_update: updateWpCheckbox ? updateWpCheckbox.checked : false,
            plugins: [],
            wp_theme: false
        };

        // Plugins process
        const pluginRows = pluginTable.querySelectorAll('tbody tr');
        pluginRows.forEach(row => {
            const actionSelect = row.querySelector('.plugin-action-select');
            if (actionSelect) {
                const slug = row.querySelector('td:nth-child(2)').innerText.trim();
                if (actionSelect.value !== 'Seleccionar...') {
                    jobs.plugins.push({
                        name: slug,
                        action: actionSelect.value
                    });
                }
            }
        });

        // Theme process
        const themeRows = themeTable.querySelectorAll('tbody tr');
        themeRows.forEach(row => {
            const actionSelect = row.querySelector('.theme-action-select');
            if (actionSelect) {
                const slug = row.querySelector('td:nth-child(2)').innerText.trim();
                if (actionSelect.value === 'update') {
                    jobs.wp_theme = slug;
                }
            }
        });
        console.log(jobs);
        // Limpia la lista de jobs
        jobsList.innerHTML = '';

        // Añade cada job a la lista
        jobs.plugins.forEach(job => {
            const listItem = document.createElement('li');
            listItem.className = 'list-group-item';
            listItem.textContent = job['action'] + " " + job['name'];
            jobsList.appendChild(listItem);
        });
        confirmModalElement.querySelector('.confirm-domain').innerHTML = wpModalElement.querySelector('.modal-title').textContent;
        wpModalElement.querySelector('.modal-title');
        wpModalElement.classList.add('modal-disabled');
        //wpmModal.hide();
        confirmModal.show();
        
    });
    confirmModalElement.addEventListener('hidden.bs.modal', function () {
        // Elimina la clase deshabilitada de la modal principal si se cierra el modal de confirmación sin confirmar
        wpModalElement.classList.remove('modal-disabled');
    });
    confirmJobsButton.addEventListener('click', function() {
        
        
        // Send json to server
        fetch(app_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(jobs)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close the modal and reset the initial state
                const modalElement = document.getElementById('wpm-modal');
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                confirmModal.hide();
                modalInstance.hide();

                // Reset the first tab as active
                const firstTabButton = document.querySelector('#nav-tab button:first-child');
                const tabInstance = new bootstrap.Tab(firstTabButton);
                tabInstance.show();

                // Get the index of the row and update its onclick event
                const rowIndex = modalElement.dataset.currentRow;
                console.log(rowIndex);
                const table = document.getElementById("table-wpm");
                if (rowIndex) {
                    const row = table.rows[rowIndex];
                    row.onclick = function () {
                        // Show second modal with the warning
                        const warningModal = new bootstrap.Modal(document.getElementById('warningModal'));
                        warningModal.show();
                    };
                    // Add the text-bg-warning class to all tds in the row
                    Array.from(row.cells).forEach(cell => {
                        cell.classList.add('text-bg-warning');
                    });
                }
                if(data.success==='session_queue') {
                    showToastedMessage('path-to-image', 'New jobs', 'Now', 'There are jobs running, new ones will be added to the session queue.');
                }
            } else {
                // Erro message
                console.error('Error:', data.message);
            }
        })
        .catch((error) => {
            console.error('Error:', error);
        }); 
    });
    /**
     * Change event of the main modal that shows or does not show the save button,
     * depending on whether changes have been made to the content of the modal
     *
     */
    wpModalElement.addEventListener('change', function (event) {
        if (event.target.matches('#update-wp-checkbox, .plugin-action-select, #table-themes input[type="checkbox"]')) {
            showSaveButton();
        }
    });

    /**
     * Catch submit event for logs form
     * 
     */
    logForm.addEventListener("submit", handleFormSubmit);

    /**
     * Clear all modal data when it's closed
     * 
     */
    wpModalElement.addEventListener('hidden.bs.modal', function (event) {
        clearWordpressContainer();
        clearPluginsTable();
        clearThemesTable();
        clearEmailForm();
        hideSaveButton();
    });

    /**
     * Function called throw updateProgress() in setInterval function
     * 
     */
    function updateTable() {
        fetch(api_url + "?update=true")
                .then(response => response.json())

                .then(data => {
                    // Reemplazar el contenido del tbody con los <tr> recibidos
                    tableBody.innerHTML = data.html;
                    showToastedMessage('path-to-image', data.title, data.timestamp, data.message);
                    addRowHandlers();
                })
                .catch(error => {
                    console.error("Hubo un problema con la solicitud fetch:", error);
                });
    }

    /**
     * Function that updates the progress bar as the time interval passes until
     * the next data update in the main table
     * 
     */
    function updateProgress() {
        progress += increment;
        if (progress >= 100) {
            progress = 0;
            updateTable();
            updateDateTime();
        }
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
    }

    /**
     * Function that updates datetime at bottom page
     * 
     */
    function updateDateTime() {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0'); // Months start from 0
        const year = now.getFullYear();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        const formattedDate = `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        document.getElementById('last_updated').innerText = formattedDate;
    }

    /**
     * Get the urgency level of the email that has been selected in the radius of the Email tab
     * 
     */
    function gelEmailLevelValue() {

        var ele = document.getElementsByName('levelRadios');

        for (i = 0; i < ele.length; i++) {
            if (ele[i].checked)
                return ele[i].value;
        }
    }

    /**
     * Hide and show emails-copy input field when checkbox exists and change status
     * 
     */
    if (checkboxMailCopy) {
        checkboxMailCopy.addEventListener('change', function () {
            if (checkboxMailCopy.checked) {
                inputMailsCopy.classList.remove('d-none');
            } else {
                inputMailsCopy.classList.add('d-none');
            }
        });
    }
    
    /**
     * Show alert box in Email tab section showing message received from server side sendMail function
     *
     * @param {string} message - Message to show
     * @param {string} type - Message type: warning or success 
     */
    const appendAlert = (message, type) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible" role="alert">`,
            `   <div>${message}</div>`,
            '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>'
        ].join('');
        alertPlaceholder.innerHTML = "";
        alertPlaceholder.append(wrapper);
    };

    /**
     * Send email button
     * 
     */
    sendButton.addEventListener('click', function () {

        const emailTitle = document.getElementById('mail-message').value;
        const emailBody = document.getElementById('mail-body').value;
        const emailLevel = gelEmailLevelValue();
        const wp_code = document.getElementById('wp-install-id').value;
        const modalTitle = wpModalElement.querySelector('.modal-title');

        var copyMails = "";

        if (checkboxMailCopy.checked) {
            copyMails = inputMailsCopy.value
        }

        // Email data object
        const data = {
            wp_action: "send_mail",
            wp_id: wp_code,
            wp_site_url: modalTitle.textContent,
            email_subject: emailTitle,
            email_body: emailBody,
            email_level: emailLevel,
            email_copy: copyMails
        };

        // Config headers and body response
        const requestOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        };

        // Fetch call
        fetch(api_url, requestOptions)
                .then(response => response.json())
                .then(responseData => {

                    if (responseData.success) {
                        clearEmailForm();
                        appendAlert(responseData.message, 'success');
                    } else {
                        appendAlert(responseData.error, 'danger');
                    }
                })
                .catch(error => {
                    appendAlert('Errors have occurred in sending an email!', 'warning');
                });
    });

    const generalTabElements = document.querySelectorAll('#nav-tab button[data-bs-toggle="tab"]');

    generalTabElements.forEach(tab => {
        tab.addEventListener('shown.bs.tab', (event) => {
            if (event.target.textContent.trim() !== "Email") {
                sendButton.classList.add("d-none");
            } else {
                sendButton.classList.remove("d-none");
            }
        });
    });

    const emailTabElements = document.querySelectorAll('#pills-tab button[data-bs-toggle="pill"]');

    emailTabElements.forEach(tab => {
        tab.addEventListener('shown.bs.tab', (event) => {
            if (event.target.textContent.trim() === "Send mail") {
                sendButton.classList.remove("d-none");
            } else {
                sendButton.classList.add("d-none");
            }
        });
    });

    addRowHandlers();
    updateDateTime();
    setInterval(updateProgress, updateTime);
});
