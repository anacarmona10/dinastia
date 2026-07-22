# DinastiaAMV
Proyecto final - Análisis y Desarrollo de Software

# Descripción del proyecto

Dinastía AMV es un proyecto de software que busca conectar a las familias nativas y personas extranjeras con los lugares más memorables de Colombia para permitir a nuestros clientes conocer y ser parte de nuestro patrimonio cultural. Lo que nos diferencia de otras agencias de viajes es nuestra propuesta de una nueva manera de conocer y explorar el país, ofreciendo planes menos reconocidos, colaborando al tiempo con la economía local de lugares no tan turísticos; a la vez que también ofrecemos planes en lugares más tradicionalmente turísticos.

A su vez, este proyecto se diferencia de otros comercios electrónicos populares debido a que ofrece planes con todo incluido, mientras que estos otros comercios suelen ofrecer cada servicio por separado, lo que implica que las personas se tomen más tiempo planeando que disfrutando de su viaje.

Dinastía AMV no busca “ser mejor” que los “e- commerce” que se encuentran en el mercado ni que las agencias de viaje existentes, busca ser auténtico en sus planes, apoyar la economía local de lugares poco turísticos y ahorrar costos y tiempo a los viajeros ofreciendo planes completos.

# Tecnologías usadas

PHP
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