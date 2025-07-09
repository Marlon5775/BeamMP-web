// Variables globales pour stocker toutes les données des véhicules et mods
let allVehicules = [];
let allMods = [];


// Initialisation au chargement de la page
document.addEventListener("DOMContentLoaded", () => {
    // La modale reste masquée au chargement de la page
    const mapModal = document.getElementById("mapModal");
    if (mapModal) {
        mapModal.style.display = "none";
    }
    // Charger les véhicules et les mods dès le chargement
    fetchItems('vehicule');
    fetchItems('mod');
    addDeleteEventListeners();
    filterMaps();
});

// Fonction pour redémarrer le serveur
function refreshServer() {
    const lang = document.documentElement.lang || 'en'; // ou récupérer depuis une variable globale

    fetch('/includes/BeamMP/restart_server.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ lang }) // ✅ On envoie la langue ici
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur lors du rafraîchissement du serveur.');
        return response.json();
    })
    .then(data => {
        if (data.success) alert(data.message);
        else alert(`Erreur : ${data.message}`);
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors du redémarrage du serveur.');
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

// Charger les cartes depuis le serveur
function loadMaps(mapType = 'all') {
    const mapList = document.getElementById('mapList');
    mapList.innerHTML = '<p>Chargement...</p>';

    fetch(`/includes/BeamMP/get_maps.php?type=${mapType}`)
        .then(response => {
            if (!response.ok) throw new Error('Erreur lors de la récupération des cartes.');
            return response.json();
        })
        .then(maps => {
            mapList.innerHTML = '';
            if (maps.length === 0) {
                mapList.innerHTML = '<p>Aucune carte disponible.</p>';
                return;
            }
            maps.forEach(map => {
                const mapItem = document.createElement('div');
                mapItem.classNom = 'map-item';
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
                                        return desc[currentLang] || 'Description non disponible';
                                    } catch {
                                        return 'Description non disponible';
                                    }
                                })()
                            }</p>
                            <button class="btn btn-select" onclick="selectMap('${map.id_map}')" ${isAdmin ? '' : 'disabled'}>Sélectionner</button>
                        
                            <div class="map-delete-container">
                                ${map.map_officielle === 0
                                ? `<button class="map-delete-button" data-nom="${map.nom}" data-type="${map.type}" ${isAdmin ? '' : 'disabled'}>Supprimer</button>`
                                : `<button class="map-official-badge" disabled>Officielle</button>`}
                            </div>
                        </div>
                    </div>`;
                mapList.appendChild(mapItem);
            });
            addDeleteEventListeners();
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des cartes :', error);
            mapList.innerHTML = '<p>Erreur lors du chargement des cartes.</p>';
        });
}

// Fonction pour sélectionner une carte
function selectMap(id_map) {
    fetch('/includes/BeamMP/executechangemap.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_map })
    })
        .then(response => {
            if (!response.ok) throw new Error('Erreur lors de la sélection de la carte.');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                closeMapModal();
                location.reload();
            } else {
                alert(`Erreur : ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Erreur :', error);
            alert('Une erreur est survenue lors de la sélection de la carte.');
        });
}

// Charger les véhicules ou mods depuis le serveur
function fetchItems(type, options = {}) {
    const { status = 'all', search = '', sort = 'az' } = options;

    // Sélectionner le conteneur pour afficher les éléments
    const container = document.querySelector(`.${type}-scroll-box`);
    if (!container) {
        console.error(`Conteneur pour ${type} introuvable.`);
        return;
    }

    // Afficher un message de chargement
    container.innerHTML = '<p>Chargement...</p>';

    // Appeler l'API pour récupérer les données
    fetch(`/includes/BeamMP/get_items.php?type=${type}`)
        .then(response => {
            if (!response.ok) throw new Error('Erreur réseau.');
            return response.json();
        })
        .then(data => {
            // Stocker toutes les données dans les variables globales
            if (type === 'vehicule') allVehicules = data;
            if (type === 'mod') allMods = data;

            // Mettre à jour les compteurs fixes
            updateFixedCounts();

            // Appliquer les filtres pour l'affichage des données
            const filteredData = applyFilters(data, status, search, sort);

            // Nettoyer le conteneur
            container.innerHTML = '';

            // Afficher un message si aucune donnée n'est disponible
            if (filteredData.length === 0) {
                container.innerHTML = `<p>Aucun ${type} disponible.</p>`;
                return;
            }

            // Générer les éléments pour chaque entrée filtrée
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
                            Supprimer
                        </button>
                    </div>`;
                
                container.appendChild(itemElement);
            });

            // Ajouter les écouteurs pour la suppression
            addDeleteEventListeners();
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données :', error);
            container.innerHTML = `<p>Erreur lors du chargement des ${type}s.</p>`;
        });
}

// Filtres et tri dynamiques
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
    console.log(`Recherche pour ${type}:`, searchValue); // Debugging
    fetchItems(type, { search: searchValue });
}


function applySort(type, criteria) {
    fetchItems(type, { sort: criteria });
}

function filterMaps() {
    const selectedType = document.getElementById("mapTypeSelector").value; // Récupère la valeur sélectionnée

    // Détermine le type de carte à charger (all, official, mod)
    let mapType = "all";
    if (selectedType === "official") {
        mapType = "official";
    } else if (selectedType === "mod") {
        mapType = "mod";
    }

    // Appelle la fonction loadMaps avec le type de carte sélectionné
    loadMaps(mapType);
}

function toggleMod(nom, type, element) {
    // Trouver l'élément principal
    const item = document.querySelector(`.${type}-item[data-nom="${nom}"]`);
    if (!item) {
        console.error('Élément non trouvé pour', nom);
        return;
    }

    // Chercher image + texte à modifier
    const img = item.querySelector('img');
    const h3 = item.querySelector('h3');

    if (!img || !h3) {
        console.error('Image ou titre introuvable.');
        return;
    }

    // Détecter l'état actuel via data-active
    const currentStatus = item.getAttribute('data-active') === "1"; // true si actif

    // Appel serveur pour inverser
    fetch('/includes/BeamMP/toggle_mod.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nom, type })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Statut de ${nom} mis à jour avec succès.`);

                // Inverser l'état local
                const newStatus = !currentStatus;
                item.setAttribute('data-active', newStatus ? "1" : "0");

                // Mise à jour visuelle
                if (newStatus) {
                    img.classList.remove('inactive-mod');
                    img.classList.add('active-mod');
                    h3.classList.remove('disabled-text');
                } else {
                    img.classList.remove('active-mod');
                    img.classList.add('inactive-mod');
                    h3.classList.add('disabled-text');
                }

                // Filtrage dynamique
                const currentFilter = document.getElementById(`${type}StatusFilter`).value;
                if (currentFilter === "active" && !newStatus) {
                    item.classList.add('hidden');
                } else if (currentFilter === "inactive" && newStatus) {
                    item.classList.add('hidden');
                } else {
                    item.classList.remove('hidden');
                }

                // Mise à jour des compteurs
                updateCountsFromDOM(type);
            } else {
                alert(`Erreur : ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut :', error);
            alert('Une erreur est survenue.');
        });
}




function deleteItem(nom, type) {
    fetch('/includes/BeamMP/delete_mod.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ mod_nom: nom, mod_type: type }), // Utilise 'mod_nom'
    })
        .then(response => {
            if (!response.ok) throw new Error('Erreur réseau.');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Élément supprimé avec succès.');
                const item = document.querySelector(`[data-nom="${nom}"][data-type="${type}"]`);
                if (item) item.remove();
            } else {
                console.error('Erreur :', data.message);
                alert(`Erreur : ${data.message}`);
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
            const nom = this.dataset.nom; // Récupération du nom
            const type = this.dataset.type; // Récupération du type

            showDeleteConfirmation(nom, type, () => {
                // Exécuter la suppression
                fetch('/includes/BeamMP/delete_mod.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mod_nom: nom, mod_type: type })
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Erreur réseau.');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            const item = this.closest('.map-item, .mod-item');
                            if (item) item.remove();
                            console.log('Élément supprimé avec succès.');
                            fetchItems(type);
                        } else {
                            console.error('Erreur :', data.message);
                            alert(`Erreur : ${data.message}`);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors de la suppression :', error);
                    });
            });
        });
    });
}

// Fonction pour afficher la boîte de confirmation de suppression
function showDeleteConfirmation(nom, type, onConfirm) {
    const confirmationBox = document.getElementById('deleteConfirmationBox');
    const confirmationMessage = document.getElementById('deleteConfirmationMessage');
    const confirmButton = document.getElementById('confirmDeleteButton');
    const cancelButton = document.getElementById('cancelDeleteButton');

    confirmationBox.style.display = 'flex'; // Affiche la boîte
    confirmationMessage.textContent = `Voulez-vous vraiment supprimer ${nom} (${type}) ?`;

    // Gestion du bouton de confirmation
    confirmButton.onclick = () => {
        onConfirm(); // Exécute l'action de suppression
        confirmationBox.style.display = 'none';
    };

    // Gestion du bouton d'annulation
    cancelButton.onclick = () => {
        confirmationBox.style.display = 'none'; // Ferme la boîte
    };
}

function updateCounts(type, data) {
    // Filtrer les données pour obtenir les différents totaux
    const totalCount = data.length;
    const activeCount = data.filter(item => item.mod_actif == 1).length;
    const inactiveCount = totalCount - activeCount;

    // Mettre à jour les compteurs dans le DOM
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

    // Mettre à jour les compteurs dans le DOM
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
    // Compteurs pour les véhicules
    const vehiculeTotal = allVehicules.length;
    const vehiculeActive = allVehicules.filter(item => item.mod_actif == 1).length;
    const vehiculeInactive = vehiculeTotal - vehiculeActive;

    document.getElementById('vehiculeTotalCount').textContent = vehiculeTotal;
    document.getElementById('vehiculeActiveCount').textContent = vehiculeActive;
    document.getElementById('vehiculeInactiveCount').textContent = vehiculeInactive;

    // Compteurs pour les mods
    const modTotal = allMods.length;
    const modActive = allMods.filter(item => item.mod_actif == 1).length;
    const modInactive = modTotal - modActive;

    document.getElementById('modTotalCount').textContent = modTotal;
    document.getElementById('modActiveCount').textContent = modActive;
    document.getElementById('modInactiveCount').textContent = modInactive;
}


function applyFilters(data, status, search, sort) {
    let filteredData = [...data];

    // Filtre par statut actif/inactif
    if (status === 'active') {
        filteredData = filteredData.filter(item => item.mod_actif == 1);
    } else if (status === 'inactive') {
        filteredData = filteredData.filter(item => item.mod_actif == 0);
    }

    // Filtre par recherche
    if (search) {
        filteredData = filteredData.filter(item =>
            item.nom.toLowerCase().includes(search.toLowerCase())
        );
    }

    // Tri des résultats
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
