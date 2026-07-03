<?php

use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testLoginValidoNoRetornaErrores()
    {
        $errores = validarLogin('usuario@test.com', '123456');

        $this->assertEmpty($errores);
    }

    public function testLoginConCorreoInvalidoRetornaError()
    {
        $errores = validarLogin('correo-malo', '123456');

        $this->assertContains("El correo no tiene un formato válido", $errores);
    }
}