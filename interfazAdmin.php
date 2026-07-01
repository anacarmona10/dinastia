<?php
// Solo los administradores con sesión iniciada pueden ver esta página
session_start();
if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header('Location: login_admin.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel Admin</title>
    <link rel="stylesheet" href="style_InterAdmin.css">
</head>

<body>
    <div class="container" style="display:flex; justify-content:space-between; align-items:center;">
        <p style="margin:0;">Bienvenido/a, <strong><?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></strong></p>
        <a href="api/logout.php" style="color:#c800ff; font-weight:bold; text-decoration:none;">Cerrar sesión</a>
    </div>

    <div class="container">
        <h1>Panel de Administración</h1>

        <button class="btn-crear" onclick="abrirModal()">
            + Crear viaje
        </button>

        <div id="listaViajes" class="viajes-grid">
            <p id="mensajeCarga">Cargando viajes...</p>
        </div>
    </div>

    <!-- MODAL -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h2>Crear viaje</h2>

            <label>Imágenes</label>

            <div id="drop-zone">
                <span class="plus">+</span>
                <input type="file" id="imagenes" multiple accept="image/*">
            </div>

            <div id="preview"></div>

            <label>Destino</label>
            <input type="text" id="destino" placeholder="Ingresa el destino">

            <label>Precio</label>
            <input type="number" id="precio" placeholder="Ingresa el precio">

            <label>Fecha salida</label>
            <input type="date" id="fecha_salida">

            <label>Fecha regreso</label>
            <input type="date" id="fecha_regreso">

            <label>Descripción</label>
            <textarea id="descripcion" placeholder="Descripción del plan turístico"></textarea>

            <button class="btn-guardar" onclick="guardarPlan()">Guardar</button>
            <button class="btn-cerrar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div class="modal" id="modalEditar">
        <div class="modal-content">
            <h2>Editar viaje</h2>

            <input type="hidden" id="editar_id">

            <label>Imágenes actuales</label>
            <div id="preview_actuales"></div>

            <label>Agregar nuevas imágenes</label>

            <div id="drop-zone-editar">
                <span class="plus">+</span>
                <input type="file" id="imagenes_editar" multiple accept="image/*">
            </div>

            <div id="preview_editar"></div>

            <label>Destino</label>
            <input type="text" id="editar_destino" placeholder="Ingresa el destino">

            <label>Precio</label>
            <input type="number" id="editar_precio" placeholder="Ingresa el precio">

            <label>Fecha salida</label>
            <input type="date" id="editar_fecha_salida">

            <label>Fecha regreso</label>
            <input type="date" id="editar_fecha_regreso">

            <label>Descripción</label>
            <textarea id="editar_descripcion" placeholder="Descripción del plan turístico"></textarea>

            <button class="btn-guardar" onclick="guardarEdicion()">Guardar cambios</button>
            <button class="btn-cerrar" onclick="cerrarModalEditar()">Cancelar</button>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById("modal").style.display = "flex";
        }

        function cerrarModal() {
            document.getElementById("modal").style.display = "none";
            limpiarFormulario();
        }

        let imagenesGuardadas = [];

        const inputImagenes = document.getElementById("imagenes");
        const preview = document.getElementById("preview");
        const dropZone = document.getElementById("drop-zone");

        // DRAG EVENTS
        dropZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            dropZone.classList.add("dragover");
        });

        dropZone.addEventListener("dragleave", () => {
            dropZone.classList.remove("dragover");
        });

        dropZone.addEventListener("drop", (e) => {
            e.preventDefault();
            dropZone.classList.remove("dragover");

            const archivos = Array.from(e.dataTransfer.files);
            agregarImagenes(archivos);
        });

        // INPUT NORMAL
        inputImagenes.addEventListener("change", function () {
            const archivos = Array.from(this.files);
            agregarImagenes(archivos);
        });

        function agregarImagenes(archivos) {
            archivos.forEach(file => {
                if (!file.type.startsWith("image/")) return;
                imagenesGuardadas.push(file);
            });

            mostrarPreview();
        }

        function mostrarPreview() {
            preview.innerHTML = "";

            imagenesGuardadas.forEach((file, index) => {
                const reader = new FileReader();

                reader.onload = function (e) {
                    const contenedor = document.createElement("div");
                    contenedor.classList.add("img-container");

                    const img = document.createElement("img");
                    img.src = e.target.result;
                    img.classList.add("preview-img");

                    // IMAGEN POR DEFECTO SI NO CARGA
                    img.onerror = function () {
                        this.src = "https://via.placeholder.com/100x100?text=Sin+imagen";
                    };

                    const btnEliminar = document.createElement("button");
                    btnEliminar.innerText = "✖";
                    btnEliminar.classList.add("btn-eliminar");

                    btnEliminar.onclick = () => {
                        imagenesGuardadas.splice(index, 1);
                        mostrarPreview();
                    };

                    contenedor.appendChild(img);
                    contenedor.appendChild(btnEliminar);
                    preview.appendChild(contenedor);
                };

                reader.readAsDataURL(file);
            });
        }

        function limpiarFormulario() {
            document.getElementById("destino").value = "";
            document.getElementById("precio").value = "";
            document.getElementById("fecha_salida").value = "";
            document.getElementById("fecha_regreso").value = "";
            document.getElementById("descripcion").value = "";
            imagenesGuardadas = [];
            mostrarPreview();
            document.getElementById("imagenes").value = "";
        }

        async function guardarPlan() {
            const destino = document.getElementById("destino").value;
            const precio = document.getElementById("precio").value;
            const fecha_salida = document.getElementById("fecha_salida").value;
            const fecha_regreso = document.getElementById("fecha_regreso").value;
            const descripcion = document.getElementById("descripcion").value;

            if (!destino || !precio || !fecha_salida || !fecha_regreso) {
                alert("Completa todos los campos obligatorios");
                return;
            }

            if (imagenesGuardadas.length === 0) {
                alert("Debes seleccionar al menos una imagen");
                return;
            }

            const formData = new FormData();
            formData.append("destino", destino);
            formData.append("precio", precio);
            formData.append("fecha_salida", fecha_salida);
            formData.append("fecha_regreso", fecha_regreso);
            formData.append("descripcion", descripcion);

            imagenesGuardadas.forEach((imagen) => {
                formData.append("imagenes[]", imagen);
            });

            try {
                const response = await fetch("api/guardar_viaje.php", {
                    method: "POST",
                    body: formData
                });

                const resultado = await response.json();

                if (resultado.success) {
                    alert(resultado.mensaje);
                    cerrarModal();
                    limpiarFormulario();
                    cargarViajes(); // refresca las tarjetas con el nuevo viaje
                } else {
                    alert(resultado.error);
                }
            } catch (error) {
                alert("Error al conectar con el servidor: " + error.message);
            }
        }
    </script>

    <script>
        // Ajusta la altura de todos los textarea (crear y editar)
        document.querySelectorAll("textarea").forEach((textarea) => {
            textarea.addEventListener("input", () => {
                textarea.style.height = "auto";
                textarea.style.height = textarea.scrollHeight + "px";
            });
        });
    </script>

    <script>
        // ======= LISTAR VIAJES (TARJETAS) =======

        let viajesData = []; // guarda la última respuesta del servidor
        const rutaImagenes = "imagenes/"; // carpeta de imágenes en la raíz del proyecto

        document.addEventListener("DOMContentLoaded", cargarViajes);

        async function cargarViajes() {
            const contenedor = document.getElementById("listaViajes");

            try {
                const response = await fetch("api/listar_viajes.php");
                const resultado = await response.json();

                if (!resultado.success) {
                    contenedor.innerHTML = `<p>${resultado.error}</p>`;
                    return;
                }

                viajesData = resultado.viajes;
                renderizarViajes();

            } catch (error) {
                contenedor.innerHTML = `<p>Error al cargar los viajes: ${error.message}</p>`;
            }
        }

        function renderizarViajes() {
            const contenedor = document.getElementById("listaViajes");
            contenedor.innerHTML = "";

            if (viajesData.length === 0) {
                contenedor.innerHTML = "<p>No hay viajes creados todavía.</p>";
                return;
            }

            viajesData.forEach((viaje) => {
                const card = document.createElement("div");
                card.classList.add("viaje-card");

                const imgUrl = viaje.imagenes.length > 0
                    ? rutaImagenes + viaje.imagenes[0].url
                    : "https://via.placeholder.com/300x180?text=Sin+imagen";

                const precioFormateado = Number(viaje.precio).toLocaleString("es-CO");

                card.innerHTML = `
                    <img src="${imgUrl}" alt="${viaje.destino}" class="viaje-card-img"
                         onerror="this.src='https://via.placeholder.com/300x180?text=Sin+imagen'">
                    <div class="viaje-card-body">
                        <h3>${viaje.destino}</h3>
                        <p class="viaje-precio">$${precioFormateado}</p>
                        <p class="viaje-fechas">${formatearFecha(viaje.fecha_salida)} → ${formatearFecha(viaje.fecha_regreso)}</p>
                        <p class="viaje-descripcion">${viaje.descripcion ?? ""}</p>
                        <button class="btn-editar" onclick="abrirModalEditar(${viaje.id})">Editar</button>
                    </div>
                `;

                contenedor.appendChild(card);
            });
        }

        function formatearFecha(fechaStr) {
            if (!fechaStr) return "";
            const [anio, mes, dia] = fechaStr.split("-");
            return `${dia}/${mes}/${anio}`;
        }

        // ======= EDITAR VIAJE =======

        let imagenesNuevasEditar = [];

        function abrirModalEditar(id) {
            const viaje = viajesData.find(v => v.id == id);
            if (!viaje) return;

            document.getElementById("editar_id").value = viaje.id;
            document.getElementById("editar_destino").value = viaje.destino;
            document.getElementById("editar_precio").value = viaje.precio;
            document.getElementById("editar_fecha_salida").value = viaje.fecha_salida;
            document.getElementById("editar_fecha_regreso").value = viaje.fecha_regreso;
            document.getElementById("editar_descripcion").value = viaje.descripcion ?? "";

            // Mostrar imágenes actuales (solo lectura por ahora)
            const previewActuales = document.getElementById("preview_actuales");
            previewActuales.innerHTML = "";
            viaje.imagenes.forEach((img) => {
                const imagen = document.createElement("img");
                imagen.src = rutaImagenes + img.url;
                imagen.classList.add("preview-img");
                imagen.onerror = function () {
                    this.src = "https://via.placeholder.com/100x100?text=Sin+imagen";
                };
                previewActuales.appendChild(imagen);
            });

            // Limpiar imágenes nuevas pendientes
            imagenesNuevasEditar = [];
            document.getElementById("preview_editar").innerHTML = "";
            document.getElementById("imagenes_editar").value = "";

            // Ajustar altura del textarea con el contenido cargado
            const textareaEditar = document.getElementById("editar_descripcion");
            textareaEditar.style.height = "auto";
            textareaEditar.style.height = textareaEditar.scrollHeight + "px";

            document.getElementById("modalEditar").style.display = "flex";
        }

        function cerrarModalEditar() {
            document.getElementById("modalEditar").style.display = "none";
        }

        // Drag & drop e input para imágenes nuevas en el modal de edición
        const inputImagenesEditar = document.getElementById("imagenes_editar");
        const previewEditar = document.getElementById("preview_editar");
        const dropZoneEditar = document.getElementById("drop-zone-editar");

        dropZoneEditar.addEventListener("dragover", (e) => {
            e.preventDefault();
            dropZoneEditar.classList.add("dragover");
        });

        dropZoneEditar.addEventListener("dragleave", () => {
            dropZoneEditar.classList.remove("dragover");
        });

        dropZoneEditar.addEventListener("drop", (e) => {
            e.preventDefault();
            dropZoneEditar.classList.remove("dragover");
            agregarImagenesEditar(Array.from(e.dataTransfer.files));
        });

        inputImagenesEditar.addEventListener("change", function () {
            agregarImagenesEditar(Array.from(this.files));
        });

        function agregarImagenesEditar(archivos) {
            archivos.forEach(file => {
                if (!file.type.startsWith("image/")) return;
                imagenesNuevasEditar.push(file);
            });
            mostrarPreviewEditar();
        }

        function mostrarPreviewEditar() {
            previewEditar.innerHTML = "";

            imagenesNuevasEditar.forEach((file, index) => {
                const reader = new FileReader();

                reader.onload = function (e) {
                    const contenedor = document.createElement("div");
                    contenedor.classList.add("img-container");

                    const img = document.createElement("img");
                    img.src = e.target.result;
                    img.classList.add("preview-img");

                    const btnEliminar = document.createElement("button");
                    btnEliminar.innerText = "✖";
                    btnEliminar.classList.add("btn-eliminar");
                    btnEliminar.onclick = () => {
                        imagenesNuevasEditar.splice(index, 1);
                        mostrarPreviewEditar();
                    };

                    contenedor.appendChild(img);
                    contenedor.appendChild(btnEliminar);
                    previewEditar.appendChild(contenedor);
                };

                reader.readAsDataURL(file);
            });
        }

        async function guardarEdicion() {
            const id = document.getElementById("editar_id").value;
            const destino = document.getElementById("editar_destino").value;
            const precio = document.getElementById("editar_precio").value;
            const fecha_salida = document.getElementById("editar_fecha_salida").value;
            const fecha_regreso = document.getElementById("editar_fecha_regreso").value;
            const descripcion = document.getElementById("editar_descripcion").value;

            if (!destino || !precio || !fecha_salida || !fecha_regreso) {
                alert("Completa todos los campos obligatorios");
                return;
            }

            const formData = new FormData();
            formData.append("id", id);
            formData.append("destino", destino);
            formData.append("precio", precio);
            formData.append("fecha_salida", fecha_salida);
            formData.append("fecha_regreso", fecha_regreso);
            formData.append("descripcion", descripcion);

            imagenesNuevasEditar.forEach((imagen) => {
                formData.append("imagenes[]", imagen);
            });

            try {
                const response = await fetch("api/actualizar_viaje.php", {
                    method: "POST",
                    body: formData
                });

                const resultado = await response.json();

                if (resultado.success) {
                    alert(resultado.mensaje);
                    cerrarModalEditar();
                    cargarViajes(); // refresca las tarjetas
                } else {
                    alert(resultado.error);
                }
            } catch (error) {
                alert("Error al conectar con el servidor: " + error.message);
            }
        }
    </script>

</body>

</html>