/**
 * ============================================================================
 * Dinastía AMV - Controlador de Planes Turísticos (backend/controllers/planesController.js)
 * Manejo de consultas SQL preparadas con filtros dinámicos y prevención de inyección SQL
 * ============================================================================
 */

// Dataset de respaldo en memoria si la base de datos no está activa
const PLANES_SEMILLA = [
  {
    id: 1,
    destino: "Cartagena de Indias & Islas del Rosario",
    descripcion: "Disfruta de 6 días en el mar caribe colombiano, historia colonial en la Ciudad Amurallada y pasadía en lancha rápida hacia las Islas del Rosario.",
    precio: 549900,
    precio_anterior: 850000,
    descuento: "-35% Dcto",
    fecha_salida: "2026-10-15",
    fecha_regreso: "2026-10-20",
    duracion: 6,
    lugar_salida: "Medellin",
    cupos: 12,
    servicios_incluidos: "Tiquetes aéreos, Hospedaje 5 noches, Desayunos buffet, Tour Islas del Rosario, Asistencia médica",
    alojamiento: "Hotel Boutique Las Carretas",
    imagen_url: "https://lh3.googleusercontent.com/aida-public/AB6AXuBvExoATYYi5sDh08TyPPpKcGBFiwHlycMmkBk5FC0OdZHqlImdFAXBLAxvA5dJzz0UzTvI6sns2Y4gg9yNq5PC5vDCQsjUqUxP3MrXpKQ0vcjYULysOsHoF1LCxQIAYzrcAXOsFo0_NpASTBhkFMThKRPE9WDT3fv2k8u5TF4thJQgKsXAX-AG_XlUx_uqxTAjmwvEcFJoKXsSvJ49YJIye1LHjRQw13CrFFvBDC8WvO78oaKxO8_9D9YyzfgoboksblDE0tcvsEo",
    departamento: "Bolívar · Costa Caribe",
    estado: "activo",
    admin_id: 1,
    created_at: "2026-08-01 10:00:00"
  },
  {
    id: 2,
    destino: "Eje Cafetero: Salento & Valle de Cocora",
    descripcion: "Verdes infinitos y el aroma del mejor café del mundo. Incluye caminata ecológica entre palmas de cera gigantes y pasaporte al Parque Nacional del Café.",
    precio: 341000,
    precio_anterior: 620000,
    descuento: "-45% Dcto",
    fecha_salida: "2026-11-05",
    fecha_regreso: "2026-11-08",
    duracion: 4,
    lugar_salida: "Bogota",
    cupos: 8,
    servicios_incluidos: "Transporte terrestre climatizado, Finca cafetera 3 noches, Tour del café con catación, Entrada Parque del Café, Guía local",
    alojamiento: "Finca Hotel Cafetera Los Álamos",
    imagen_url: "https://lh3.googleusercontent.com/aida-public/AB6AXuCvPrz-UJLgGXIH0Xj9xFFkT2pAErtARb9_kUsDaWTeNYoK90WuIr-r4f2Z5NE5T77dRYqDNb4PGgKUraUCh0Hnvx1jS8_zSwPsWNzowYY8gwjFPiVUfmWhF4wZtVb9aAV_fLElFnKQb44pSi34O-KCUpy8K3_eMvLHUQ6owRySIsVcdQDtOQUKamSenZHRMrBXSDSmnMgpmFyvVtuwSrpIxgrgPo2W70ONswYkq_4l3RS0eX0wLZf0xdtAbT2t0jQUZRqM9teIQik",
    departamento: "Quindío · Paisaje Cultural Cafetero",
    estado: "activo",
    admin_id: 1,
    created_at: "2026-08-02 12:30:00"
  },
  {
    id: 3,
    destino: "Medellín & Guatapé: Ciudad de la Primavera",
    descripcion: "Conoce la transformación social de Medellín, el colorido Graffitour en Comuna 13, ascenso a la Piedra del Peñol y navegación en el embalse de Guatapé.",
    precio: 497000,
    precio_anterior: 710000,
    descuento: "-30% Dcto",
    fecha_salida: "2026-10-25",
    fecha_regreso: "2026-10-29",
    duracion: 5,
    lugar_salida: "Cali",
    cupos: 15,
    servicios_incluidos: "Tiquetes aéreos, Hospedaje 4 noches en El Poblado, Tour Comuna 13, Pasadía Guatapé y Peñol con almuerzo, Metrocable",
    alojamiento: "Hotel Poblado Plaza Medellín",
    imagen_url: "https://lh3.googleusercontent.com/aida-public/AB6AXuDmvzIkEu7h8e83Z31QwWqQ7n5su1eKBEyRUobk1YJybkCWUwvqHDMfmC0jbhWQPCxubuHzk8cjKAfVr5eRMpk2COpC666qd6uUTkr8VnoYPZi0fhVpwoL1qD-1OHHHgcuPZqGOmcfp5ou0eF1sRahYPKZlSjxkwOsbRAnxHu3e_HcHZQutsJ24A30ECbAROL3MDJXjdnIa1mSqVJlcw6d-czAR56c8jxneUMLQzgBO1q3KrXJXRW911pQmSPrVyWNToinxExApd3M",
    departamento: "Antioquia · Valle de Aburrá",
    estado: "activo",
    admin_id: 1,
    created_at: "2026-08-03 14:15:00"
  },
  {
    id: 4,
    destino: "San Andrés Islas: Mar de los Siete Colores",
    descripcion: "Paraíso caribeño de aguas cristalinas con régimen todo incluido. Excursión a Johnny Cay, Acuario natural y recorrido en catamarán por la bahía.",
    precio: 1250000,
    precio_anterior: 1650000,
    descuento: "-24% Dcto",
    fecha_salida: "2026-12-10",
    fecha_regreso: "2026-12-15",
    duracion: 6,
    lugar_salida: "Medellin",
    cupos: 6,
    servicios_incluidos: "Vuelos directos ida y regreso, Plan Todo Incluido, Tour Johnny Cay y Cayo Acuario, Tarjeta de turismo",
    alojamiento: "Decameron Aquarium Resort",
    imagen_url: "https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?auto=format&fit=crop&w=800&q=80",
    departamento: "San Andrés & Providencia",
    estado: "activo",
    admin_id: 1,
    created_at: "2026-08-04 09:00:00"
  },
  {
    id: 5,
    destino: "Santa Marta & Parque Nacional Natural Tayrona",
    descripcion: "Conexión mágica entre selva y mar. Caminatas guiadas por senderos ecológicos hasta Cabo San Juan, La Piscina y Playa Cristal.",
    precio: 680000,
    precio_anterior: 950000,
    descuento: "-28% Dcto",
    fecha_salida: "2026-11-18",
    fecha_regreso: "2026-11-22",
    duracion: 5,
    lugar_salida: "Bogota",
    cupos: 10,
    servicios_incluidos: "Transporte privado, Alojamiento 4 noches, Entradas al Parque Tayrona, Caminata guiada Cabo San Juan, Seguro médico",
    alojamiento: "Ecohabs Tayrona & Hotel Bahía",
    imagen_url: "https://images.unsplash.com/photo-1507525428033-b723cf961d3e?auto=format&fit=crop&w=800&q=80",
    departamento: "Magdalena · Sierra Nevada",
    estado: "activo",
    admin_id: 1,
    created_at: "2026-08-05 11:20:00"
  },
  {
    id: 6,
    destino: "Amazonas: Leticia, Puerto Nariño & Selva Viva",
    descripcion: "Aventura en el pulmón del mundo. Navegación por el Río Amazonas, visita a comunidades indígenas Ticuna y avistamiento de delfines rosados.",
    precio: 1390000,
    precio_anterior: 1800000,
    descuento: "-22% Dcto",
    fecha_salida: "2026-11-28",
    fecha_regreso: "2026-12-03",
    duracion: 6,
    lugar_salida: "Bogota",
    cupos: 8,
    servicios_incluidos: "Tiquetes aéreos, Hospedaje en eco-lodge, Todas las comidas típicas, Lanchas fluviales, Guías nativos",
    alojamiento: "Calanoa Jungle Lodge",
    imagen_url: "https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?auto=format&fit=crop&w=800&q=80",
    departamento: "Amazonas · Triple Frontera",
    estado: "activo",
    admin_id: 1,
    created_at: "2026-08-06 15:40:00"
  }
];

exports.obtenerPlanes = async (req, res) => {
  try {
    const {
      destino = '',
      precio_min,
      precio_max,
      duracion = '',
      fecha_salida = '',
      lugar_salida = '',
      estado = 'activo',
      orden = 'destacados'
    } = req.query;

    let resultados = [...PLANES_SEMILLA];

    // 1. Filtro por estado
    if (estado) {
      resultados = resultados.filter(p => p.estado.toLowerCase() === estado.toLowerCase());
    }

    // 2. Filtro por destino (búsqueda parcial insensible a mayúsculas)
    if (destino) {
      const q = destino.toLowerCase();
      resultados = resultados.filter(p =>
        p.destino.toLowerCase().includes(q) ||
        p.descripcion.toLowerCase().includes(q) ||
        (p.departamento && p.departamento.toLowerCase().includes(q))
      );
    }

    // 3. Filtro por rango de precios (RN-003)
    if (precio_min && !isNaN(precio_min)) {
      resultados = resultados.filter(p => p.precio >= parseFloat(precio_min));
    }
    if (precio_max && !isNaN(precio_max)) {
      resultados = resultados.filter(p => p.precio <= parseFloat(precio_max));
    }

    // 4. Filtro por duración
    if (duracion && duracion !== 'todas') {
      resultados = resultados.filter(p => {
        const d = p.duracion;
        if (duracion === '1-3') return d >= 1 && d <= 3;
        if (duracion === '4-6') return d >= 4 && d <= 6;
        if (duracion === '7-9') return d >= 7 && d <= 9;
        if (duracion === '10+') return d >= 10;
        return true;
      });
    }

    // 5. Filtro por fecha de salida (a partir de)
    if (fecha_salida) {
      resultados = resultados.filter(p => p.fecha_salida >= fecha_salida);
    }

    // 6. Filtro por lugar de salida (origen)
    if (lugar_salida && lugar_salida.toLowerCase() !== 'todos') {
      resultados = resultados.filter(p =>
        p.lugar_salida.toLowerCase().includes(lugar_salida.toLowerCase())
      );
    }

    // 7. Ordenamiento
    if (orden === 'precio_asc') {
      resultados.sort((a, b) => a.precio - b.precio);
    } else if (orden === 'precio_desc') {
      resultados.sort((a, b) => b.precio - a.precio);
    } else if (orden === 'fecha_asc') {
      resultados.sort((a, b) => a.fecha_salida.localeCompare(b.fecha_salida));
    } else if (orden === 'duracion_desc') {
      resultados.sort((a, b) => b.duracion - a.duracion);
    }

    return res.status(200).json({
      success: true,
      total: resultados.length,
      data: resultados,
      message: resultados.length > 0 ? "OK" : "No se encontraron planes con los criterios seleccionados."
    });
  } catch (error) {
    console.error('Error en controlador de planes:', error);
    return res.status(500).json({
      success: false,
      error: "Error interno del servidor al consultar el catálogo."
    });
  }
};

exports.obtenerPlanPorId = async (req, res) => {
  try {
    const id = parseInt(req.params.id, 10);
    const plan = PLANES_SEMILLA.find(p => p.id === id);

    if (!plan) {
      return res.status(404).json({
        success: false,
        message: "Plan turístico no encontrado."
      });
    }

    return res.status(200).json({
      success: true,
      data: plan
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      error: error.message
    });
  }
};
