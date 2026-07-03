<?php

use PHPUnit\Framework\TestCase;

final class CorreoTest extends TestCase
{
    public function testCorreoValidoSePreparaCorrectamente()
    {
        $correo = prepararCorreo(
            'cliente@test.com',
            'Confirmación de registro',
            'Tu registro fue exitoso'
        );

        $this->assertIsArray($correo);
        $this->assertEquals('cliente@test.com', $correo['to']);
    }

    public function testCorreoInvalidoRetornaFalse()
    {
        $correo = prepararCorreo(
            'correo-malo',
            'Confirmación',
            'Mensaje'
        );

        $this->assertFalse($correo);
    }
}