<?php

use PHPUnit\Framework\TestCase;

final class RegistroPostTest extends TestCase
{
    public function testCp001NoRegistraUsuarioSiFaltaDatoObligatorio()
    {
        $usuarios = [];

        $resultado = registrarUsuarioPrueba($usuarios, [
            'nombreCompleto' => '',
            'tipoDocumento' => 'CC',
            'numeroDocumento' => '123456',
            'correo' => 'usuario@test.com',
            'contrasena' => '123456',
            'confirmar_contrasena' => '123456'
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertContains('El nombre es obligatorio', $resultado['errores']);
        $this->assertCount(0, $usuarios);
    }

    public function testCp002RegistraUsuarioConTodosLosDatosCorrectos()
    {
        $usuarios = [];

        $resultado = registrarUsuarioPrueba($usuarios, [
            'nombreCompleto' => 'Ana Carmona',
            'tipoDocumento' => 'CC',
            'numeroDocumento' => '123456',
            'correo' => 'ana@test.com',
            'contrasena' => '123456',
            'confirmar_contrasena' => '123456'
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertCount(1, $usuarios);
        $this->assertEquals('ana@test.com', $usuarios[0]['correo']);
    }

    public function testRegistroSeProcesaPorMetodoPost()
    {
        $resultado = validarRegistroPost(
            ['REQUEST_METHOD' => 'POST'],
            [
                'nombreCompleto' => 'Ana Carmona',
                'tipoDocumento' => 'CC',
                'numeroDocumento' => '123456',
                'correo' => 'ana@test.com',
                'contrasena' => '123456',
                'confirmar_contrasena' => '123456'
            ]
        );

        $this->assertTrue($resultado['success']);
    }
}