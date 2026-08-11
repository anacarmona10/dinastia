<?php

use PHPUnit\Framework\TestCase;

final class ValidarLoginExtendedTest extends TestCase
{
    public function testCorreoVacioRetornaErrorObligatorio()
    {
        $errores = validarLogin('', '123456');

        $this->assertContains("El correo electrónico es obligatorio", $errores);
    }

    public function testContrasenaVaciaRetornaErrorObligatoria()
    {
        $errores = validarLogin('usuario@test.com', '');

        $this->assertContains("La contraseña es obligatoria", $errores);
    }

    public function testCorreoConSoloEspaciosSeConsideraVacio()
    {
        $errores = validarLogin('   ', '123456');

        $this->assertContains("El correo electrónico es obligatorio", $errores);
    }

    public function testCorreoYContrasenaVaciosRetornaDosErrores()
    {
        $errores = validarLogin('', '');

        $this->assertCount(2, $errores);
        $this->assertContains("El correo electrónico es obligatorio", $errores);
        $this->assertContains("La contraseña es obligatoria", $errores);
    }

    public function testCorreoInvalidoNoDuplicaErrorDeObligatoriedad()
    {
        $errores = validarLogin('correo-sin-arroba', '123456');

        $this->assertCount(1, $errores);
        $this->assertContains("El correo no tiene un formato válido", $errores);
    }
}
