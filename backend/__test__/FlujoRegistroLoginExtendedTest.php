<?php

use PHPUnit\Framework\TestCase;

final class FlujoRegistroLoginExtendedTest extends TestCase
{
    private function datosValidos(array $overrides = []): array
    {
        return array_merge([
            'nombreCompleto' => 'Ana Carmona',
            'tipoDocumento' => 'CC',
            'numeroDocumento' => '123456',
            'correo' => 'ana@test.com',
            'contrasena' => '123456',
            'confirmar_contrasena' => '123456'
        ], $overrides);
    }

    public function testRegistroPorMetodoGetEsRechazado()
    {
        $resultado = validarRegistroPost(
            ['REQUEST_METHOD' => 'GET'],
            $this->datosValidos()
        );

        $this->assertFalse($resultado['success']);
        $this->assertContains('La petición debe ser POST', $resultado['errores']);
    }

    public function testRegistroSinMetodoDefinidoEsRechazado()
    {
        $resultado = validarRegistroPost([], $this->datosValidos());

        $this->assertFalse($resultado['success']);
        $this->assertContains('La petición debe ser POST', $resultado['errores']);
    }

    public function testRegistroPorPostConDatosInvalidosDevuelveErroresDeValidacion()
    {
        $resultado = validarRegistroPost(
            ['REQUEST_METHOD' => 'POST'],
            $this->datosValidos(['correo' => 'correo-invalido'])
        );

        $this->assertFalse($resultado['success']);
        $this->assertContains('Correo electrónico inválido', $resultado['errores']);
    }

    public function testNoPermiteRegistrarCorreoDuplicado()
    {
        $usuarios = [
            [
                'nombreCompleto' => 'Ana Carmona',
                'correo' => 'ana@test.com',
                'contrasena' => password_hash('123456', PASSWORD_DEFAULT)
            ]
        ];

        $resultado = registrarUsuarioPrueba($usuarios, $this->datosValidos());

        $this->assertFalse($resultado['success']);
        $this->assertContains('El correo electrónico ya está registrado', $resultado['errores']);
        $this->assertCount(1, $usuarios);
    }

    public function testRegistroExitosoAlmacenaLaContrasenaHasheada()
    {
        $usuarios = [];

        registrarUsuarioPrueba($usuarios, $this->datosValidos());

        $this->assertNotEquals('123456', $usuarios[0]['contrasena']);
        $this->assertTrue(password_verify('123456', $usuarios[0]['contrasena']));
    }

    public function testLoginConListaDeUsuariosVaciaRetornaFalse()
    {
        $resultado = loginUsuarioPrueba([], 'ana@test.com', '123456');

        $this->assertFalse($resultado);
    }

    public function testLoginConContrasenaVaciaRetornaFalse()
    {
        $usuarios = [
            [
                'nombreCompleto' => 'Ana Carmona',
                'correo' => 'ana@test.com',
                'contrasena' => password_hash('123456', PASSWORD_DEFAULT)
            ]
        ];

        $resultado = loginUsuarioPrueba($usuarios, 'ana@test.com', '');

        $this->assertFalse($resultado);
    }
}
