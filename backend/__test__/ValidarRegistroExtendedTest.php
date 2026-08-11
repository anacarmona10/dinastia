<?php

use PHPUnit\Framework\TestCase;

final class ValidarRegistroExtendedTest extends TestCase
{
    private function datosValidos(array $overrides = []): array
    {
        return array_merge([
            'nombreCompleto' => 'Juan Perez',
            'tipoDocumento' => 'CC',
            'numeroDocumento' => '123456',
            'correo' => 'juan@test.com',
            'contrasena' => '123456',
            'confirmar_contrasena' => '123456'
        ], $overrides);
    }

    public function testFaltaNombreCompletoRetornaError()
    {
        $errores = validarRegistro($this->datosValidos(['nombreCompleto' => '']));

        $this->assertContains("El nombre es obligatorio", $errores);
    }

    public function testFaltaTipoDocumentoRetornaError()
    {
        $errores = validarRegistro($this->datosValidos(['tipoDocumento' => '']));

        $this->assertContains("Seleccione un tipo de documento", $errores);
    }

    public function testFaltaNumeroDocumentoRetornaError()
    {
        $errores = validarRegistro($this->datosValidos(['numeroDocumento' => '']));

        $this->assertContains("El número de documento es obligatorio", $errores);
    }

    public function testCorreoInvalidoRetornaError()
    {
        $errores = validarRegistro($this->datosValidos(['correo' => 'no-es-un-correo']));

        $this->assertContains("Correo electrónico inválido", $errores);
    }

    public function testContrasenaConMenosDeSeisCaracteresRetornaError()
    {
        $errores = validarRegistro($this->datosValidos([
            'contrasena' => '12345',
            'confirmar_contrasena' => '12345'
        ]));

        $this->assertContains("La contraseña debe tener al menos 6 caracteres", $errores);
    }

    public function testContrasenaConExactamenteSeisCaracteresEsValida()
    {
        $errores = validarRegistro($this->datosValidos([
            'contrasena' => '123456',
            'confirmar_contrasena' => '123456'
        ]));

        $this->assertNotContains("La contraseña debe tener al menos 6 caracteres", $errores);
    }

    public function testTodosLosCamposVaciosRetornaTodosLosErrores()
    {
        $errores = validarRegistro([
            'nombreCompleto' => '',
            'tipoDocumento' => '',
            'numeroDocumento' => '',
            'correo' => '',
            'contrasena' => '',
            'confirmar_contrasena' => ''
        ]);

        // nombre, tipoDocumento, numeroDocumento, correo, contraseña corta
        // (no coinciden no aplica porque ambas están vacías e iguales)
        $this->assertCount(5, $errores);
    }

    public function testValidarDatosRegistroConDatosCorrectosDevuelveSuccessTrue()
    {
        $resultado = validarDatosRegistro($this->datosValidos());

        $this->assertTrue($resultado['success']);
        $this->assertEmpty($resultado['errores']);
    }

    public function testValidarDatosRegistroConDatosIncompletosDevuelveSuccessFalse()
    {
        $resultado = validarDatosRegistro($this->datosValidos(['numeroDocumento' => '']));

        $this->assertFalse($resultado['success']);
        $this->assertContains('El número de documento es obligatorio', $resultado['errores']);
    }
}
