<?php

use PHPUnit\Framework\TestCase;

final class LoginCasosPruebaTest extends TestCase
{
    private array $usuarios;

    protected function setUp(): void
    {
        $this->usuarios = [
            [
                'nombreCompleto' => 'Ana Carmona',
                'correo' => 'ana@test.com',
                'contrasena' => password_hash('123456', PASSWORD_DEFAULT)
            ]
        ];
    }

    public function testCp003PermiteIngresarUsuarioConDatosRegistrados()
    {
        $resultado = loginUsuarioPrueba(
            $this->usuarios,
            'ana@test.com',
            '123456'
        );

        $this->assertTrue($resultado);
    }

    public function testCp004NoPermiteIngresarConContrasenaIncorrecta()
    {
        $resultado = loginUsuarioPrueba(
            $this->usuarios,
            'ana@test.com',
            'claveIncorrecta'
        );

        $this->assertFalse($resultado);
    }

    public function testCp005NoPermiteIngresarUsuarioNoRegistrado()
    {
        $resultado = loginUsuarioPrueba(
            $this->usuarios,
            'noexiste@test.com',
            '123456'
        );

        $this->assertFalse($resultado);
    }
}