function toggleMenu() {
    let menu = document.getElementById("menu");
    menu.style.display = menu.style.display === "flex" ? "none" : "flex";
}

function mostrarInfo(tipo) {
    let info = document.getElementById("info");

    if (tipo === "planes") {
        info.innerHTML = "Aquí podrás ver todos los viajes que tienes actualmente reservados, con detalles como fechas, destinos y estado.";
    }

    if (tipo === "historial") {
        info.innerHTML = "Consulta todos los viajes que ya realizaste anteriormente, incluyendo destinos visitados y experiencias.";
    }

    if (tipo === "promo") {
        info.innerHTML = "Descubre ofertas exclusivas, descuentos y promociones personalizadas según tus preferencias de viaje.";
    }
}