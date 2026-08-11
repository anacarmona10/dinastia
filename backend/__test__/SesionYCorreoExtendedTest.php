<?php

use PHPUnit\Framework\TestCase;

final class SesionYCorreoExtendedTest extends TestCase
{
    public function testSesionVaciaNoPuedeGestionarViajes()
    {
        $this->assertFalse(puedeGestionarViajes([]));
    }

    public function testSesionNoLogueadaNoPuedeGestionarViajesAunqueSeaAdmin()
    {
        $session = [
            'logged_in' => false,
            'tipo_usuario' => 'admin'
        ];

        $this->assertFalse(puedeGestionarViajes($session));
    }

    public function testSesionSinTipoUsuarioNoPuedeGestionarViajes()
    {
        $session = [
            'logged_in' => true
        ];

        $this->assertFalse(puedeGestionarViajes($session));
    }

    public function testCorreoConAsuntoVacioRetornaFalse()
    {
        $correo = prepararCorreo('cliente@test.com', '', 'Mensaje de prueba');

        $this->assertFalse($correo);
    }

    public function testCorreoConMensajeVacioRetornaFalse()
    {
        $correo = prepararCorreo('cliente@test.com', 'Asunto', '');

        $this->assertFalse($correo);
    }

    public function testCorreoConAsuntoSoloEspaciosRetornaFalse()
    {
        $correo = prepararCorreo('cliente@test.com', '   ', 'Mensaje de prueba');

        $this->assertFalse($correo);
    }

    public function testCorreoValidoContieneAsuntoYMensajeCorrectos()
    {
        $correo = prepararCorreo('cliente@test.com', 'Asunto de prueba', 'Cuerpo del mensaje');

        $this->assertEquals('Asunto de prueba', $correo['subject']);
        $this->assertEquals('Cuerpo del mensaje', $correo['message']);
    }
}
