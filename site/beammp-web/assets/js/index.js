document.addEventListener("DOMContentLoaded", () => {
    const dialogue = document.getElementById("dialogue");
    const backdrop = document.getElementById("dialogue-bg");
    const togglePassword = document.querySelector(".toggle-password");
    const closeButton = document.querySelector(".btn-close");
    const loginButton = document.querySelector(".btn-login");


   // Afficher la modale de connexion
   loginButton?.addEventListener("click", () => {
    dialogue.style.display = "block";
    backdrop.style.display = "block";
});

// Fermer la modale en cliquant sur la croix
closeButton?.addEventListener("click", () => {
    dialogue.style.display = "none";
    backdrop.style.display = "none";
});

// Fermer la modale en cliquant sur le backdrop
backdrop?.addEventListener("click", () => {
    dialogue.style.display = "none";
    backdrop.style.display = "none";
});

// Basculer la visibilité du mot de passe
togglePassword?.addEventListener("click", () => {
    const passwordInput = document.getElementById("password");
    const isPassword = passwordInput.type === "password";
    passwordInput.type = isPassword ? "text" : "password";
    togglePassword.textContent = isPassword ? "🙈" : "👁️";
});
});