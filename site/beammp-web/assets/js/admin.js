document.addEventListener("DOMContentLoaded", () => {
    const logContainer = document.getElementById("logContent");

    if (logContainer) {
        setInterval(() => {
            fetch('/includes/BeamMP/fetch_logs.php')
                .then(response => response.text())
                .then(data => {
                    logContainer.textContent = data;

                    const scrollbox = document.getElementById("logScrollbox");
                    const isAtBottom = scrollbox.scrollTop + scrollbox.clientHeight >= scrollbox.scrollHeight - 10;

                    if (isAtBottom) {
                        scrollbox.scrollTop = scrollbox.scrollHeight;
                    }
                })
                .catch(console.error);
        }, 2000);
    }
});

function saveField(fieldName) {
    const fieldValue = document.getElementById(fieldName).value;

    fetch('/includes/BeamMP/update_config_line.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key: fieldName, value: fieldValue })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {alert
            alert(t['save_ok'].replace('{field}', fieldName));
        } else {
           alert(t['save_error'].replace('{error}', result.error));

        }
    })
    .catch(error => {
        console.error('Erreur sauvegarde:', error);
        alert(t['save_network_error'].replace('{error}', error.message));
    });
}

function saveSwitch(switchName) {
    const isChecked = document.getElementById(switchName).checked;

    fetch('/includes/BeamMP/update_config_line.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key: switchName, value: isChecked })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(t['save_ok'].replace('{field}', switchName));
        } else {
            alert(t['save_error'].replace('{error}', result.error));
        }
    })
    .catch(error => {
        console.error('Erreur sauvegarde:', error);
        alert(t['save_network_error'].replace('{error}', error.message));
    });
}
