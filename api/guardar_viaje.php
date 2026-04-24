<?php
require "conexion.php";

try {
    // 1. Recibir datos
    $destino = $_POST['destino'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $fecha_salida = $_POST['fecha_salida'];
    $fecha_regreso = $_POST['fecha_regreso'];
    $admin_id = 1; // luego lo hacemos dinámico

    // 2. Insertar viaje
    $sql = "INSERT INTO viajes (destino, descripcion, precio, fecha_salida, fecha_regreso, admin_id)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$destino, $descripcion, $precio, $fecha_salida, $fecha_regreso, $admin_id]);

    // 3. Obtener ID del viaje recién creado
    $viaje_id = $pdo->lastInsertId();

    // 4. Guardar imágenes
    if (!empty($_FILES['imagenes']['name'][0])) {

        $carpeta = "../imagenes/"; // crea esta carpeta

        foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {

            $nombre = time() . "_" . $_FILES['imagenes']['name'][$key];
            $ruta = $carpeta . $nombre;

            move_uploaded_file($tmp_name, $ruta);

            // guardar en BD
            $sql_img = "INSERT INTO imagenes_viajes (viaje_id, url) VALUES (?, ?)";
            $stmt_img = $pdo->prepare($sql_img);
            $stmt_img->execute([$viaje_id, $nombre]);
        }
    }

    echo "Viaje guardado correctamente";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}