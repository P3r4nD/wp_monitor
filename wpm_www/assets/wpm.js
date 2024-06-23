
document.addEventListener("DOMContentLoaded", function() {

    function hasClass(element, cls) {
        return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
    }

    function displayLogMessage(message) {

        var alertBox = document.getElementById("msg-logs");

        alertBox.innerHTML = message;

        if (false == hasClass(alertBox, 'd-none')) {

        } else {
            alertBox.classList.toggle("d-none");
        }
    }

    function btnSubmitChange(status){

        var btn_submit = document.getElementById("btn-submit");
        var btn_searching = document.getElementById("btn-searching");

        if(status=="searching"){
            btn_submit.classList.add("d-none");
            btn_searching.classList.remove("d-none");
        }else{
            btn_submit.classList.remove("d-none");
            btn_searching.classList.add("d-none");
        }
    }
    function clearWordpressContainer() {

        var tlb = document.querySelector('#wp-container');
        tlb.innerHTML = "";
    }
    function clearPluginsTable() {

        var tlb = document.querySelector('#table-plugins tbody');
        tlb.innerHTML = "";
    }
    function clearThemesTable() {

        var tlb = document.querySelector('#table-themes tbody');
        tlb.innerHTML = "";
    }
    function clearLogsTable() {

        var tlb = document.querySelector('#table-logs tbody');
        tlb.innerHTML = "";
    }
    function hideLogMessage() {

        var alertBox = document.getElementById("msg-logs");
        alertBox.innerHTML = "";
        alertBox.classList.add("d-none");

    }

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

        var table = document.getElementById("table-wp");
        var rows = table.getElementsByTagName("tr");

        var wp_id = document.getElementById("wp-install-id");

        var wp_current_version = document.getElementById("wp-current-version");
        var wp_last_version = document.getElementById("wp-last-version");

        const createWordpressContainer = (o, v, uv) => {
            if (o) {
                return `
                    <div class="form-check">
                        <p>Current version: <span id="wp-current-version">${v}</span><p>
                        <input class="form-check-input" type="checkbox" value="" id="updateWpCheckbox">
                        <label class="form-check-label" for="updateWpCheckbox">
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
        }

        const createPluginSelect = (slug) => {
            return `
                <select class="form-select form-select-sm plugin-action-select" data-plugin-slug="${slug}">
                    <option value="Seleccionar...">Seleccionar...</option>
                    <option value="update">Actualizar</option>
                    <option value="disable">Desactivar</option>
                </select>
            `;
        };

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

            var createClickHandler = function (row) {
                return function () {
                    clearLogsTable();
                    document.getElementById('wpExtModal').dataset.currentRow = row.rowIndex;
                    var cell = row.getElementsByTagName("td")[0];
                    var cell_title = row.getElementsByTagName("td")[1];
                    var id = cell.innerHTML;
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
                        console.log(d.id, d.outdatedWp, d.version, d.update_version);

                        wpc.innerHTML = `${createWordpressContainer(d.outdatedWp, d.version, d.update_version)}`;

                        wp_id.value = d.id;
                        console.log("ID: ", wp_id.value);
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

                        //Fill Logs table
                        const modalTitle = wpModalElement.querySelector('.modal-title');
                        modalTitle.textContent = cell_title.innerHTML;

                        let input_hidden = document.getElementById("url_domain");
                        input_hidden.setAttribute('value', cell_title.innerHTML);
                        wpExtModal.show();
                    });
                };
            };

            if ( currentRow.classList.contains('pending-jobs') ) {
                currentRow.onclick = function() {
                    // Mostrar segundo modal con el warning
                    const warningModal = new bootstrap.Modal(document.getElementById('warningModal'));
                    warningModal.show();
                };
            }else{
                currentRow.onclick = createClickHandler(currentRow);
            }
        }
    }

    /**
    * Helper function for POSTing data as JSON with fetch.
    *
    * @param {Object} options
    * @param {string} options.url - URL to POST data to
    * @param {FormData} options.formData - `FormData` instance
    * @return {Object} - Response body from URL that was POSTed to
    */
    async function postFormDataAsJson( { url, formData }) {
        const plainFormData = Object.fromEntries(formData.entries());
        const formDataJsonString = JSON.stringify(plainFormData);

        const fetchOptions = {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: formDataJsonString,
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

    const showSaveButton = () => {
        saveJobsBtn = document.getElementById("save-jobs");
        saveJobsBtn.classList.remove('d-none');
    };

    const wpModalElement = document.getElementById('wpExtModal');
    const wpExtModal = new bootstrap.Modal(wpModalElement);
    const saveButton = document.getElementById("save-jobs");
    const pluginTable = document.getElementById('table-plugins');
    const themeTable = document.getElementById('table-themes');
    const updateWpCheckbox = document.getElementById('updateWpCheckbox');

    saveButton.addEventListener('click', function() {
        const jobs = {
            wp_action: "do_jobs",
            wp_id: document.getElementById('wp-install-id').value,
            wp_update: updateWpCheckbox ? updateWpCheckbox.checked : false,
            plugins: [],
            wp_theme: false
        };

        // Procesar plugins
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

        // Procesar theme
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

        console.log(app_url);
        console.log(jobs);
        // Enviar el objeto JSON al servidor
        fetch(app_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(jobs)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Success:', data);
            if (data.success) {
                // Cerrar el modal y restablecer el estado inicial
                const modalElement = document.getElementById('wpExtModal');
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                modalInstance.hide();

                // Restablecer el primer tab como activo
                const firstTabButton = document.querySelector('#nav-tab button:first-child');
                const tabInstance = new bootstrap.Tab(firstTabButton);
                tabInstance.show();

                // Obtener el índice del row y actualizar su evento onclick
                const rowIndex = modalElement.dataset.currentRow;
                const table = document.getElementById("table-wp");
                if (rowIndex) {
                    const row = table.rows[rowIndex];
                    row.onclick = function() {
                        // Mostrar segundo modal con el warning
                        const warningModal = new bootstrap.Modal(document.getElementById('warningModal'));
                        warningModal.show();
                    };
                }
            } else {
                // Manejar el error
                console.error('Error:', data.message);
            }
        })
        .catch((error) => {
            console.error('Error:', error);
        });
    });


    wpModalElement.addEventListener('change', function(event) {
        if (event.target.matches('#updateWpCheckbox, .plugin-action-select, #table-themes input[type="checkbox"]')) {
            showSaveButton();
        }
    });

    logForm = document.getElementById("log-form");
    logForm.addEventListener("submit", handleFormSubmit);



    // Capturar el evento de cierre del modal
    wpModalElement.addEventListener('hidden.bs.modal', function(event) {
        console.log('El modal se ha cerrado');
        clearWordpressContainer();
        clearPluginsTable();
        clearThemesTable();
    });

    const tableBody = document.querySelector("#table-wp tbody");
    const api_url = app_url;
    function updateTable() {
        fetch(api_url+"?update=true")
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.text();
            })
            .then(html => {
                // Reemplazar el contenido del tbody con los <tr> recibidos
                tableBody.innerHTML = html;
                addRowHandlers();
            })
            .catch(error => {
                console.error("Hubo un problema con la solicitud fetch:", error);
            });
    }

    addRowHandlers();
    // Llamar a la función inmediatamente y luego establecer el intervalo

    const progressBar = document.getElementById('progressBar');
    const interval = app_reload_time; // Intervalo total en milisegundos
    const updateTime = 100; // Tiempo de actualización del progreso en milisegundos
    let progress = 0;
    let increment = 100 / (interval / updateTime);

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

    function updateDateTime() {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0'); // Los meses empiezan desde 0
        const year = now.getFullYear();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        const formattedDate = `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        document.getElementById('last_updated').innerText = formattedDate;
    }
    updateDateTime();
    setInterval(updateProgress, updateTime);
});
