<?php

use PHPUnit\Framework\TestCase;

final class RegistroTest extends TestCase
{
    public function testRegistroValidoNoRetornaErrores()
    {
        $errores = validarRegistro([
            'nombreCompleto' => 'Juan Perez',
            'tipoDocumento' => 'CC',
            'numeroDocumento' => '123456',
            'correo' => 'juan@test.com',
            'contrasena' => '123456',
            'confirmar_contrasena' => '123456'
        ]);

        $this->assertEmpty($errores);
    }

    public function testRegistroConContrasenasDiferentesRetornaError()
    {
        $errores = validarRegistro([
            'nombreCompleto' => 'Juan Perez',
            'tipoDocumento' => 'CC',
            'numeroDocumento' => '123456',
            'correo' => 'juan@test.com',
            'contrasena' => '123456',
            'confirmar_contrasena' => 'abcdef'
        ]);

        $this->assertContains("Las contraseñas no coinciden", $errores);
    }
}