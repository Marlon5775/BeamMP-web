function tr(key, params = {}) {
    let str = t[key] || key;
    Object.keys(params).forEach(param => {
        str = str.replace(`{${param}}`, params[param]);
    });
    return str;
}
window.onload = function () {
    console.log("✅ DOM prêt");
    const uploadForm = document.getElementById("uploadForm");
    const typeSelect = document.getElementById("type");
    const mapGroup = document.getElementById("map-id-group");
    const vehicleGroup = document.getElementById("vehicle-type-group");
    const modStatusGroup = document.getElementById("mod-status-group");
    const idMapInput = document.getElementById("id_map");

    const uploadModal = document.getElementById("uploadModal");
    const modalFileName = document.getElementById("fileName");
    const uploadProgressBar = document.getElementById("uploadProgressBar");
    const rsyncProgressBar = document.getElementById("rsyncProgressBar");
    const modalSpeed = document.getElementById("uploadSpeedText");
    const modalRemainingTime = document.getElementById("uploadTimeRemaining");
    const modalTitle = document.getElementById("modalTitle");
    const loaderAnimation = document.querySelector(".loader");
    const progressPercent = document.getElementById("progressPercent");

    const nameInput = document.getElementById("name");
    function toggleFieldGroups() {
        const type = typeSelect.value;

        mapGroup.style.display = type === "map" ? "block" : "none";
        vehicleGroup.style.display = type === "vehicule" ? "block" : "none";
        modStatusGroup.style.display = type === "mod" ? "block" : "none";

        if (type === "map") {
            idMapInput.setAttribute("required", "required");
        } else {
            idMapInput.removeAttribute("required");
        }
    }

    console.log(t['log_init']);

    typeSelect.addEventListener("change", toggleFieldGroups);

    toggleFieldGroups();

    nameInput.addEventListener("input", () => {
        const nameRegex = /^[a-zA-Z0-9-_ ]+$/;
        if (nameInput.value && !nameRegex.test(nameInput.value)) {
            nameInput.setCustomValidity(t['form_invalid_chars']);
        } else {
            nameInput.setCustomValidity("");
        }
        nameInput.reportValidity();
    });

    uploadForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const errors = validateForm();
        if (errors.length > 0) {
            alert(t['form_error'] + "\n" + errors.join("\n"));
            return;
        }

        const formData = new FormData(uploadForm);
        const fileName = formData.get("zip").name;

        showModal(fileName);
        uploadFile(formData);
    });

    function uploadFile(formData) {
        const xhr = new XMLHttpRequest();
        const startTime = Date.now();

        xhr.open("POST", "/includes/BeamMP/addmod.php", true);

        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                const percentComplete = Math.round((event.loaded / event.total) * 100);
                updateUploadProgress(percentComplete);

                const elapsedTime = (Date.now() - startTime) / 1000;
                const speedMBps = (event.loaded / 1024 / 1024) / elapsedTime;
                const remainingTime = ((event.total - event.loaded) / 1024 / 1024) / speedMBps;

                modalSpeed.textContent = `${speedMBps.toFixed(2)} MB/s`;
                modalRemainingTime.textContent = `${remainingTime.toFixed(2)} s`;
            }
        };

        xhr.onload = function () {
            console.log(t['log_server_response'], xhr.responseText);
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    console.log(t['log_upload_success']);
                    const name = document.getElementById("name").value;
                    const type = document.getElementById("type").value;
                    const vehicleType = document.getElementById("vehicle_type")?.value || null;
                    const status = document.getElementById("status")?.checked ? 1 : 0;
                    startRsyncTracking(name, type, vehicleType, status);
                } else {
                    showError(response.message || t['error_unknown']);
                }
            } else {
                showError(`${t['error_http']} ${xhr.status}`);
            }
        };

        xhr.onerror = () => showError(t['error_network']);
        xhr.send(formData);
    }

    function startRsyncTracking(name, type, vehicleType, status) {
        const params = new URLSearchParams({
            name: name,
            type: type,
            vehicle_type: vehicleType || '',
            status: status || 0
        });

        const url = `/includes/BeamMP/rsync_handler.php?${params.toString()}`;
        console.log(t['log_sse_connection'], url);

        const eventSource = new EventSource(url);

        eventSource.onmessage = function (event) {
            try {
                const data = JSON.parse(event.data);

                if (data.progress !== undefined) {
                    rsyncProgressBar.style.width = `${data.progress}%`;
                    progressPercent.textContent = `${data.progress}%`;
                }

                if (data.speed) modalSpeed.textContent = data.speed;
                if (data.time_remaining) modalRemainingTime.textContent = data.time_remaining;

                if (data.success) {
                    console.log(t['log_rsync_complete']);
                    eventSource.close();
                    loaderAnimation.style.display = "none";
                    showCompletionOptions();
                }
            } catch (error) {
                console.error(t['error_sse_parse'], error, event.data);
            }
        };

        eventSource.onerror = function () {
            console.error(t['error_sse']);
            eventSource.close();
            showError(t['error_rsync']);
            loaderAnimation.style.display = "none";
        };
    }

    function validateForm() {
        const errors = [];
        const zipInput = document.getElementById("zip");
        if (!nameInput.value.trim()) errors.push(t['error_required_name']);
        if (!zipInput.files.length) errors.push(t['error_required_zip']);
        return errors;
    }

    function updateUploadProgress(percent) {
        uploadProgressBar.style.width = `${percent}%`;
        progressPercent.textContent = `${percent}%`;
    }

    function showModal(fileName) {
        modalTitle.textContent = t['modal_title'];
        modalFileName.textContent = fileName;
        uploadProgressBar.style.width = "0%";
        rsyncProgressBar.style.width = "0%";
        modalSpeed.textContent = "0 MB/s";
        modalRemainingTime.textContent = "--";
        loaderAnimation.style.display = "block";
        uploadModal.style.display = "block";
    }

    function showCompletionOptions() {
        uploadModal.innerHTML += `
            <div style="text-align: center; margin-top: 20px;">
                <p>${t['modal_rsync_success']}</p>
                <button onclick="window.location.reload();" class="btn-completion">${t['modal_add_another']}</button>
                <button onclick="window.location.href='/pages/BeamMP/BeamMP.php';" class="btn-completion">${t['modal_exit']}</button>
            </div>
        `;
    }

    function showError(message) {
        console.error(message);
        modalTitle.textContent = t['error_title'];
        modalFileName.textContent = message;
        loaderAnimation.style.display = "none";
    }
};
