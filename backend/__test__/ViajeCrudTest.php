<?php

use PHPUnit\Framework\TestCase;

final class ViajeCrudTest extends TestCase
{
    public function testAdminPuedeGestionarViajes()
    {
        $session = [
            'logged_in' => true,
            'tipo_usuario' => 'admin'
        ];

        $this->assertTrue(puedeGestionarViajes($session));
    }

    public function testUsuarioNormalNoPuedeGestionarViajes()
    {
        $session = [
            'logged_in' => true,
            'tipo_usuario' => 'usuario'
        ];

        $this->assertFalse(puedeGestionarViajes($session));
    }

    public function testCrearViajeValidoNoRetornaErrores()
    {
        $errores = validarViaje([
            'destino' => 'Cartagena',
            'descripcion' => 'Plan turístico familiar',
            'precio' => 500000,
            'fecha_salida' => '2026-08-01',
            'fecha_regreso' => '2026-08-05'
        ]);

        $this->assertEmpty($errores);
    }

    public function testEditarViajeConPrecioInvalidoRetornaError()
    {
        $errores = validarViaje([
            'destino' => 'Cartagena',
            'descripcion' => 'Plan actualizado',
            'precio' => 0,
            'fecha_salida' => '2026-08-01',
            'fecha_regreso' => '2026-08-05'
        ]);

        $this->assertContains("El precio debe ser mayor a cero", $errores);
    }

    public function testEliminarViajeConIdValidoNoRetornaErrores()
    {
        $errores = validarIdViaje(5);

        $this->assertEmpty($errores);
    }

    public function testEliminarViajeConIdInvalidoRetornaError()
    {
        $errores = validarIdViaje(0);

        $this->assertContains("El ID del viaje no es válido", $errores);
    }
}