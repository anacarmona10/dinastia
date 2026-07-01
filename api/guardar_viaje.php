<?php
session_start();
require "conexion.php";

// Solo un admin con sesión iniciada puede guardar viajes
if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "Debes iniciar sesión como administrador"
    ]);
    exit;
}

try {
    // 1. Recibir datos
    $destino = $_POST['destino'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $fecha_salida = $_POST['fecha_salida'];
    $fecha_regreso = $_POST['fecha_regreso'];
    $admin_id = $_SESSION['admin_id']; // ahora viene de la sesión, no fijo

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

    header('Content-Type: application/json');

echo json_encode([
    "success" => true,
    "mensaje" => "Viaje guardado correctamente"
]);

} catch(PDOException $e) {
    header('Content-Type: application/json');

echo json_encode([
    "success" => false,
    "error" => $e->getMessage()
]);
}