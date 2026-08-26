# DinastiaAMV
Proyecto final - Análisis y Desarrollo de Software

# Descripción del proyecto

Dinastía AMV es un proyecto de software que busca conectar a las familias nativas y personas extranjeras con los lugares más memorables de Colombia para permitir a nuestros clientes conocer y ser parte de nuestro patrimonio cultural. Lo que nos diferencia de otras agencias de viajes es nuestra propuesta de una nueva manera de conocer y explorar el país, ofreciendo planes menos reconocidos, colaborando al tiempo con la economía local de lugares no tan turísticos; a la vez que también ofrecemos planes en lugares más tradicionalmente turísticos.

A su vez, este proyecto se diferencia de otros comercios electrónicos populares debido a que ofrece planes con todo incluido, mientras que estos otros comercios suelen ofrecer cada servicio por separado, lo que implica que las personas se tomen más tiempo planeando que disfrutando de su viaje.

Dinastía AMV no busca “ser mejor” que los “e- commerce” que se encuentran en el mercado ni que las agencias de viaje existentes, busca ser auténtico en sus planes, apoyar la economía local de lugares poco turísticos y ahorrar costos y tiempo a los viajeros ofreciendo planes completos.

# Tecnologías usadas

PHP 8.2
HTML
CSS

# Estructura de carpetas

/backend = Contiene la API (conexión a base de datos, endpoints PHP), las pruebas, dependencias (vendor) y la documentación Swagger.
/frontend = Contiene todos los archivos como el registro, inicio de sesión, interfaz de usuario, imágenes y el CSS para la decoración.
/docs = Toda la documentación necesaria.

# Estructura de Backend

/backend
  /api        = Conexión a base de datos y endpoints
  /__test__   = Pruebas
  /swagger    = Documentación de la API
  composer.json / composer.lock / phpunit.xml

# Estructura de Frontend

/frontend
  index.html, login.html, login_admin.html, Registro.html, dashboard.html, interfazAdmin.php
  /js         = Scripts de validación
  /imagenes   = Imágenes del sitio
  style_*.css = Hojas de estilo

# Qué es github actions?

GitHub Actions es una plataforma de automatización integrada en GitHub que te permite compilar, probar y desplegar tu código automáticamente mediante flujos de trabajo (workflows) basados en archivos YAML, ejecutando tareas específicas (como verificar errores o subir a producción) cada vez que ocurre un evento en tu repositorio, como un push o un pull request.

# Qué es N-ginx?

Es un servidor web de código abierto y alto rendimiento diseñado principalmente para administrar de manera eficiente grandes volúmenes de tráfico en internet.

A diferencia de los servidores tradicionales, funciona mediante una arquitectura asíncrona orientada a eventos. Esto le permite procesar miles de conexiones simultáneas usando muy poca memoria del sistema.

# Qué es BPS?

BPS se refiere principalmente a un Business Process Server (Servidor de Procesos de Negocio), que es un componente encargado de orquestar, ejecutar y automatizar flujos de trabajo (workflows) lógicos complejos entre diferentes sistemas y APIs; aunque en fases de pruebas de rendimiento también se utiliza en minúsculas (bps) para medir los bits por segundo y calcular el ancho de banda que consume la aplicación.

# Qué es HOST?

Un HOST (o anfitrión) es cualquier dispositivo conectado a una red que posee una dirección IP única y que comparte o aloja recursos, servicios o datos para otros usuarios o equipos.

Puede ser un servidor físico en la nube que aloja una página web (como en los servicios de hosting), una máquina virtual donde ejecutas tus contenedores de Docker, o incluso tu propia computadora cuando programas y accedes a ella de forma local a través de la dirección de bucle invertido (localhost).

# Qué es un dominio?

En internet, un dominio es el nombre único y exclusivo que se le asigna a un sitio web para que los usuarios puedan acceder a él fácilmente, funcionando como una dirección amigable que traduce las complejas direcciones IP numéricas de los servidores en palabras memorizables (por ejemplo, traduciendo 142.251.40.174 a google.com).