window.onload = function() {
  window.ui = SwaggerUIBundle({
    url: "swagger.yaml",
    dom_id: '#swagger-ui',
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    layout: "StandaloneLayout",
    deepLinking: true,
    displayRequestDuration: true,
    tryItOutEnabled: true
  });
};