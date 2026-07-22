<?php
session_start();
require "conexion.php";

// Solo un admin con sesión iniciada puede actualizar viajes
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
    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception("Falta el id del viaje a actualizar");
    }

    $destino = $_POST['destino'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $fecha_salida = $_POST['fecha_salida'];
    $fecha_regreso = $_POST['fecha_regreso'];

    // 2. Actualizar viaje
    $sql = "UPDATE viajes
            SET destino = ?, descripcion = ?, precio = ?, fecha_salida = ?, fecha_regreso = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$destino, $descripcion, $precio, $fecha_salida, $fecha_regreso, $id]);

    // 3. Si se agregaron imágenes nuevas, se guardan (se suman a las que ya tenía)
    if (!empty($_FILES['imagenes']['name'][0])) {

        $carpeta = "../imagenes/";

        foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {

            $nombre = time() . "_" . $_FILES['imagenes']['name'][$key];
            $ruta = $carpeta . $nombre;

            move_uploaded_file($tmp_name, $ruta);

            $sql_img = "INSERT INTO imagenes_viajes (viaje_id, url) VALUES (?, ?)";
            $stmt_img = $pdo->prepare($sql_img);
            $stmt_img->execute([$id, $nombre]);
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "mensaje" => "Viaje actualizado correctamente"
    ]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}