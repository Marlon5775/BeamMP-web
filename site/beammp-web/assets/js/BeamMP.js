// Variables globales pour stocker toutes les données des véhicules et mods
let allVehicules = [];
let allMods = [];

document.addEventListener("DOMContentLoaded", () => {
    const mapModal = document.getElementById("mapModal");
    if (mapModal) mapModal.style.display = "none";
    fetchItems('vehicule');
    fetchItems('mod');
    addDeleteEventListeners();
    filterMaps();
});

function refreshServer() {
    const lang = document.documentElement.lang || 'en';
    fetch('/includes/BeamMP/restart_server.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ lang })
    })
    .then(response => {
        if (!response.ok) throw new Error(tr('server_restart_network_error'));
        return response.json();
    })
    .then(data => {
        if (data.success) alert(tr('server_restart_ok'));
        else alert(tr('server_restart_error', { error: data.message }));
    })
    .catch(error => {
        console.error('Error:', error);
        alert(tr('server_restart_network_error'));
    });
}

// Gestion de la modale des cartes
function openMapModal() {
    const mapModal = document.getElementById('mapModal');
    if (mapModal) {
        mapModal.style.display = 'flex';
        loadMaps('all');
    }
}
function closeMapModal() {
    const mapModal = document.getElementById('mapModal');
    if (mapModal) mapModal.style.display = 'none';
}

function loadMaps(mapType = 'all') {
    const mapList = document.getElementById('mapList');
    mapList.innerHTML = `<p>${tr('loading_maps')}</p>`;

    fetch(`/includes/BeamMP/get_maps.php?type=${mapType}`)
        .then(response => {
            if (!response.ok) throw new Error(tr('error_loading_maps'));
            return response.json();
        })
        .then(maps => {
            mapList.innerHTML = '';
            if (maps.length === 0) {
                mapList.innerHTML = `<p>${tr('no_map_available')}</p>`;
                return;
            }
            maps.forEach(map => {
                const mapItem = document.createElement('div');
                mapItem.className = 'map-item';
                mapItem.innerHTML = `
                    <div class="mapactive-box">
                        <div class="mapactive-image-container">
                            <img src="${map.image || '/assets/images/no_image.png'}" alt="${map.nom}">
                        </div>
                        <div class="mapactive-info-container">
                            <h3>${map.nom}</h3>
                            <p>${
                                (() => {
                                    try {
                                        const desc = JSON.parse(map.description);
                                        return desc[currentLang] || tr('desc_not_available');
                                    } catch {
                                        return tr('desc_not_available');
                                    }
                                })()
                            }</p>
                            <button class="btn btn-select" onclick="selectMap('${map.id_map}')" ${isAdmin ? '' : 'disabled'}>${tr('select')}</button>
                            <div class="map-delete-container">
                                ${map.map_officielle == 0
                                    ? `<button class="map-delete-button" data-nom="${map.nom}" data-type="${map.type}" ${isAdmin ? '' : 'disabled'}>${tr('delete')}</button>`
                                    : `<button class="map-official-badge" disabled>${tr('official')}</button>`
                                }
                            </div>
                        </div>
                    </div>`;
                mapList.appendChild(mapItem);
            });
            addDeleteEventListeners();
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des cartes :', error);
            mapList.innerHTML = `<p>${tr('error_loading_maps')}</p>`;
        });
}

function selectMap(id_map) {
    fetch('/includes/BeamMP/executechangemap.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_map })
    })
    .then(response => {
        if (!response.ok) throw new Error(tr('map_select_network_error'));
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeMapModal();
            location.reload();
        } else {
            alert(tr('map_select_error', { error: data.message }));
        }
    })
    .catch(error => {
        console.error('Erreur :', error);
        alert(tr('map_select_network_error'));
    });
}

function fetchItems(type, options = {}) {
    const { status = 'all', search = '', sort = 'az' } = options;
    const container = document.querySelector(`.${type}-scroll-box`);
    if (!container) {
        console.error(`Conteneur pour ${type} introuvable.`);
        return;
    }
    container.innerHTML = `<p>${tr('loading_items')}</p>`;

    fetch(`/includes/BeamMP/get_items.php?type=${type}`)
        .then(response => {
            if (!response.ok) throw new Error(tr('network_error'));
            return response.json();
        })
        .then(data => {
            if (type === 'vehicule') allVehicules = data;
            if (type === 'mod') allMods = data;
            updateFixedCounts();
            const filteredData = applyFilters(data, status, search, sort);
            container.innerHTML = '';
            if (filteredData.length === 0) {
                container.innerHTML = `<p>${tr('no_item_available', { type: tr(type) })}</p>`;
                return;
            }
            filteredData.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = `${type}-item individual-box`;
                itemElement.setAttribute('data-active', item.mod_actif);
                itemElement.setAttribute('data-date', item.date);
                itemElement.setAttribute('data-nom', item.nom);

                itemElement.innerHTML = `
                    <div class="box-image" onclick="toggleMod('${item.nom}', '${type}', this)">
                        <img src="${item.image}" alt="${item.nom}" class="${item.mod_actif == 1 ? 'active-mod' : 'inactive-mod'}">
                    </div>
                    <div class="box-content">
                        <h3>${item.nom}</h3>
                    </div>
                    <div class="box-actions">
                        <button class="map-delete-button" 
                            data-nom="${item.nom}" 
                            data-type="${type}"
                            ${isAdmin ? '' : 'disabled'}>
                            ${tr('delete')}
                        </button>
                    </div>`;
                
                container.appendChild(itemElement);
            });
            addDeleteEventListeners();
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données :', error);
            container.innerHTML = `<p>${tr('error_loading_items', { type: tr(type) })}</p>`;
        });
}

function applyStatusFilter(type) {
    const status = document.getElementById(`${type}StatusFilter`).value;
    fetchItems(type, { status });
}

function applySearch(type) {
    const searchBox = document.getElementById(`${type}SearchBox`);
    if (!searchBox) {
        console.error(`Search box for ${type} not found.`);
        return;
    }
    const searchValue = searchBox.value.toLowerCase();
    fetchItems(type, { search: searchValue });
}

function applySort(type, criteria) {
    fetchItems(type, { sort: criteria });
}

function filterMaps() {
    const selectedType = document.getElementById("mapTypeSelector").value;
    let mapType = "all";
    if (selectedType === "official") mapType = "official";
    else if (selectedType === "mod") mapType = "mod";
    loadMaps(mapType);
}

function toggleMod(nom, type, element) {
    const item = document.querySelector(`.${type}-item[data-nom="${nom}"]`);
    if (!item) {
        console.error('Élément non trouvé pour', nom);
        return;
    }
    const img = item.querySelector('img');
    const h3 = item.querySelector('h3');
    if (!img || !h3) {
        console.error('Image ou titre introuvable.');
        return;
    }
    const currentStatus = item.getAttribute('data-active') === "1";
    fetch('/includes/BeamMP/toggle_mod.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nom, type })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const newStatus = !currentStatus;
            item.setAttribute('data-active', newStatus ? "1" : "0");
            if (newStatus) {
                img.classList.remove('inactive-mod');
                img.classList.add('active-mod');
                h3.classList.remove('disabled-text');
            } else {
                img.classList.remove('active-mod');
                img.classList.add('inactive-mod');
                h3.classList.add('disabled-text');
            }
            const currentFilter = document.getElementById(`${type}StatusFilter`).value;
            if (currentFilter === "active" && !newStatus) {
                item.classList.add('hidden');
            } else if (currentFilter === "inactive" && newStatus) {
                item.classList.add('hidden');
            } else {
                item.classList.remove('hidden');
            }
            updateCountsFromDOM(type);
        } else {
            alert(tr('toggle_mod_error', { error: data.message }));
        }
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour du statut :', error);
        alert(tr('toggle_mod_network_error'));
    });
}

function deleteItem(nom, type) {
    fetch('/includes/BeamMP/delete_mod.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mod_nom: nom, mod_type: type }),
    })
    .then(response => {
        if (!response.ok) throw new Error(tr('network_error'));
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('Élément supprimé avec succès.');
            const item = document.querySelector(`[data-nom="${nom}"][data-type="${type}"]`);
            if (item) item.remove();
        } else {
            console.error('Erreur :', data.message);
            alert(tr('delete_error', { error: data.message }));
        }
    })
    .catch(error => {
        console.error('Erreur lors de la suppression :', error);
    });
}

function addDeleteEventListeners() {
    const deleteButtons = document.querySelectorAll('.map-delete-button, .item-delete-button');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const nom = this.dataset.nom;
            const type = this.dataset.type;
            showDeleteConfirmation(nom, type, () => {
                fetch('/includes/BeamMP/delete_mod.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mod_nom: nom, mod_type: type })
                })
                .then(response => {
                    if (!response.ok) throw new Error(tr('network_error'));
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const item = this.closest('.map-item, .mod-item');
                        if (item) item.remove();
                        fetchItems(type);
                    } else {
                        alert(tr('delete_error', { error: data.message }));
                    }
                })
                .catch(error => {
                    alert(tr('delete_error', { error: error.message }));
                });
            });
        });
    });
}

function showDeleteConfirmation(nom, type, onConfirm) {
    const confirmationBox = document.getElementById('deleteConfirmationBox');
    const confirmationMessage = document.getElementById('deleteConfirmationMessage');
    const confirmButton = document.getElementById('confirmDeleteButton');
    const cancelButton = document.getElementById('cancelDeleteButton');

    confirmationBox.style.display = 'flex';
    confirmationMessage.textContent = tr('confirm_delete_item', { nom, type: tr(type) });

    confirmButton.onclick = () => {
        onConfirm();
        confirmationBox.style.display = 'none';
    };
    cancelButton.onclick = () => {
        confirmationBox.style.display = 'none';
    };
}

function updateCounts(type, data) {
    const totalCount = data.length;
    const activeCount = data.filter(item => item.mod_actif == 1).length;
    const inactiveCount = totalCount - activeCount;

    if (type === 'vehicule') {
        document.getElementById('vehiculeTotalCount').textContent = totalCount;
        document.getElementById('vehiculeActiveCount').textContent = activeCount;
        document.getElementById('vehiculeInactiveCount').textContent = inactiveCount;
    } else if (type === 'mod') {
        document.getElementById('modTotalCount').textContent = totalCount;
        document.getElementById('modActiveCount').textContent = activeCount;
        document.getElementById('modInactiveCount').textContent = inactiveCount;
    }
}

function updateCountsFromDOM(type) {
    const items = document.querySelectorAll(`.${type}-item`);
    const totalCount = items.length;
    const activeCount = Array.from(items).filter(item => item.getAttribute('data-active') === "1").length;
    const inactiveCount = totalCount - activeCount;

    if (type === 'vehicule') {
        document.getElementById('vehiculeTotalCount').textContent = totalCount;
        document.getElementById('vehiculeActiveCount').textContent = activeCount;
        document.getElementById('vehiculeInactiveCount').textContent = inactiveCount;
    } else if (type === 'mod') {
        document.getElementById('modTotalCount').textContent = totalCount;
        document.getElementById('modActiveCount').textContent = activeCount;
        document.getElementById('modInactiveCount').textContent = inactiveCount;
    }
}

function updateFixedCounts() {
    const vehiculeTotal = allVehicules.length;
    const vehiculeActive = allVehicules.filter(item => item.mod_actif == 1).length;
    const vehiculeInactive = vehiculeTotal - vehiculeActive;
    document.getElementById('vehiculeTotalCount').textContent = vehiculeTotal;
    document.getElementById('vehiculeActiveCount').textContent = vehiculeActive;
    document.getElementById('vehiculeInactiveCount').textContent = vehiculeInactive;

    const modTotal = allMods.length;
    const modActive = allMods.filter(item => item.mod_actif == 1).length;
    const modInactive = modTotal - modActive;
    document.getElementById('modTotalCount').textContent = modTotal;
    document.getElementById('modActiveCount').textContent = modActive;
    document.getElementById('modInactiveCount').textContent = modInactive;
}

function applyFilters(data, status, search, sort) {
    let filteredData = [...data];
    if (status === 'active') filteredData = filteredData.filter(item => item.mod_actif == 1);
    else if (status === 'inactive') filteredData = filteredData.filter(item => item.mod_actif == 0);
    if (search) {
        filteredData = filteredData.filter(item =>
            item.nom.toLowerCase().includes(search.toLowerCase())
        );
    }
    if (sort === 'az') {
        filteredData.sort((a, b) => a.nom.localeCompare(b.nom));
    } else if (sort === 'za') {
        filteredData.sort((a, b) => b.nom.localeCompare(a.nom));
    } else if (sort === 'recent') {
        filteredData.sort((a, b) => new Date(b.date) - new Date(a.date));
    } else if (sort === 'oldest') {
        filteredData.sort((a, b) => new Date(a.date) - new Date(b.date));
    }
    return filteredData;
}
