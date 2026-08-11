<?php

use PHPUnit\Framework\TestCase;

final class ValidarViajeExtendedTest extends TestCase
{
    private function viajeValido(array $overrides = []): array
    {
        return array_merge([
            'destino' => 'Cartagena',
            'descripcion' => 'Plan turístico familiar',
            'precio' => 500000,
            'fecha_salida' => '2026-08-01',
            'fecha_regreso' => '2026-08-05'
        ], $overrides);
    }

    public function testFaltaDestinoRetornaError()
    {
        $errores = validarViaje($this->viajeValido(['destino' => '']));

        $this->assertContains("El destino es obligatorio", $errores);
    }

    public function testFaltaDescripcionRetornaError()
    {
        $errores = validarViaje($this->viajeValido(['descripcion' => '']));

        $this->assertContains("La descripción es obligatoria", $errores);
    }

    public function testPrecioNoNumericoRetornaError()
    {
        $errores = validarViaje($this->viajeValido(['precio' => 'gratis']));

        $this->assertContains("El precio debe ser mayor a cero", $errores);
    }

    public function testPrecioNegativoRetornaError()
    {
        $errores = validarViaje($this->viajeValido(['precio' => -100]));

        $this->assertContains("El precio debe ser mayor a cero", $errores);
    }

    public function testFaltaFechaSalidaRetornaError()
    {
        $errores = validarViaje($this->viajeValido(['fecha_salida' => '']));

        $this->assertContains("La fecha de salida es obligatoria", $errores);
    }

    public function testFaltaFechaRegresoRetornaError()
    {
        $errores = validarViaje($this->viajeValido(['fecha_regreso' => '']));

        $this->assertContains("La fecha de regreso es obligatoria", $errores);
    }

    public function testFechaRegresoIgualAFechaSalidaNoRetornaErrorDeRango()
    {
        $errores = validarViaje($this->viajeValido([
            'fecha_salida' => '2026-08-01',
            'fecha_regreso' => '2026-08-01'
        ]));

        $this->assertNotContains(
            "La fecha de regreso no puede ser anterior a la fecha de salida",
            $errores
        );
    }

    public function testTodosLosCamposVaciosRetornaMultiplesErrores()
    {
        $errores = validarViaje([
            'destino' => '',
            'descripcion' => '',
            'precio' => '',
            'fecha_salida' => '',
            'fecha_regreso' => ''
        ]);

        $this->assertCount(5, $errores);
    }

    public function testIdViajeNegativoRetornaError()
    {
        $errores = validarIdViaje(-3);

        $this->assertContains("El ID del viaje no es válido", $errores);
    }

    public function testIdViajeNoNumericoRetornaError()
    {
        $errores = validarIdViaje('abc');

        $this->assertContains("El ID del viaje no es válido", $errores);
    }

    public function testIdViajeVacioRetornaError()
    {
        $errores = validarIdViaje('');

        $this->assertContains("El ID del viaje no es válido", $errores);
    }

    public function testIdViajeComoTextoNumericoValidoNoRetornaError()
    {
        $errores = validarIdViaje('10');

        $this->assertEmpty($errores);
    }
}
