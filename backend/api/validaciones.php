<?php

function validarLogin($correo, $contrasena) {
    $errores = [];

    if (empty(trim($correo))) {
        $errores[] = "El correo electrónico es obligatorio";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no tiene un formato válido";
    }

    if (empty($contrasena)) {
        $errores[] = "La contraseña es obligatoria";
    }

    return $errores;
}

function validarRegistro($datos) {
    $errores = [];

    if (empty(trim($datos['nombreCompleto'] ?? ''))) $errores[] = "El nombre es obligatorio";
    if (empty(trim($datos['tipoDocumento'] ?? ''))) $errores[] = "Seleccione un tipo de documento";
    if (empty(trim($datos['numeroDocumento'] ?? ''))) $errores[] = "El número de documento es obligatorio";

    if (!filter_var($datos['correo'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico inválido";
    }

    if (strlen($datos['contrasena'] ?? '') < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }

    if (($datos['contrasena'] ?? '') !== ($datos['confirmar_contrasena'] ?? '')) {
        $errores[] = "Las contraseñas no coinciden";
    }

    return $errores;
}

function validarViaje($datos) {
    $errores = [];

    if (empty(trim($datos['destino'] ?? ''))) $errores[] = "El destino es obligatorio";
    if (empty(trim($datos['descripcion'] ?? ''))) $errores[] = "La descripción es obligatoria";

    if (empty($datos['precio'] ?? '') || !is_numeric($datos['precio']) || $datos['precio'] <= 0) {
        $errores[] = "El precio debe ser mayor a cero";
    }

    if (empty($datos['fecha_salida'] ?? '')) $errores[] = "La fecha de salida es obligatoria";
    if (empty($datos['fecha_regreso'] ?? '')) $errores[] = "La fecha de regreso es obligatoria";

    if (!empty($datos['fecha_salida']) && !empty($datos['fecha_regreso'])) {
        if ($datos['fecha_regreso'] < $datos['fecha_salida']) {
            $errores[] = "La fecha de regreso no puede ser anterior a la fecha de salida";
        }
    }

    return $errores;
}

function validarIdViaje($id) {
    if (empty($id) || !is_numeric($id) || $id <= 0) {
        return ["El ID del viaje no es válido"];
    }

    return [];
}

function puedeGestionarViajes($session) {
    return !empty($session['logged_in']) && ($session['tipo_usuario'] ?? '') === 'admin';
}

function prepararCorreo($destinatario, $asunto, $mensaje) {
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) return false;
    if (empty(trim($asunto)) || empty(trim($mensaje))) return false;

    return [
        'to' => $destinatario,
        'subject' => $asunto,
        'message' => $mensaje
    ];
}


function validarRegistroPost($server, $post) {
    if (($server['REQUEST_METHOD'] ?? '') !== 'POST') {
        return [
            'success' => false,
            'errores' => ['La petición debe ser POST']
        ];
    }

    return validarDatosRegistro($post);
}

function validarDatosRegistro($datos) {
    $errores = [];

    if (empty(trim($datos['nombreCompleto'] ?? ''))) {
        $errores[] = 'El nombre es obligatorio';
    }

    if (empty(trim($datos['tipoDocumento'] ?? ''))) {
        $errores[] = 'Seleccione un tipo de documento';
    }

    if (empty(trim($datos['numeroDocumento'] ?? ''))) {
        $errores[] = 'El número de documento es obligatorio';
    }

    if (!filter_var($datos['correo'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Correo electrónico inválido';
    }

    if (strlen($datos['contrasena'] ?? '') < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres';
    }

    if (($datos['contrasena'] ?? '') !== ($datos['confirmar_contrasena'] ?? '')) {
        $errores[] = 'Las contraseñas no coinciden';
    }

    return [
        'success' => empty($errores),
        'errores' => $errores
    ];
}

function registrarUsuarioPrueba(&$usuarios, $datos) {
    $validacion = validarDatosRegistro($datos);

    if (!$validacion['success']) {
        return $validacion;
    }

    foreach ($usuarios as $usuario) {
        if ($usuario['correo'] === $datos['correo']) {
            return [
                'success' => false,
                'errores' => ['El correo electrónico ya está registrado']
            ];
        }
    }

    $usuarios[] = [
        'nombreCompleto' => $datos['nombreCompleto'],
        'correo' => $datos['correo'],
        'contrasena' => password_hash($datos['contrasena'], PASSWORD_DEFAULT)
    ];

    return [
        'success' => true,
        'errores' => []
    ];
}

function loginUsuarioPrueba($usuarios, $correo, $contrasena) {
    foreach ($usuarios as $usuario) {
        if ($usuario['correo'] === $correo && password_verify($contrasena, $usuario['contrasena'])) {
            return true;
        }
    }

    return false;
}