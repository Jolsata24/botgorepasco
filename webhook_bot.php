<?php
header('Content-Type: application/json');

// --- CONFIGURACIÓN CENTRAL ---
$url_base = "https://sugar-california-gym-challenge.trycloudflare.com"; 
// -----------------------------

$base_files = $url_base . "/botgorepasco/documentos/";

// --- CONTACTO GORE PASCO (Sede Central) ---
$wsp_soporte = "https://wa.me/51969704480"; 
$fono_central = "tel:+51969704480"; 

// 1. RECIBIR DATOS
$json = file_get_contents('php://input');
$request = json_decode($json, true);

// 2. DETECTAR INTENT
$intent_name = $request['queryResult']['intent']['displayName'] ?? '';
$parametros = $request['queryResult']['parameters'] ?? [];
$response_array = [];

// 3. CEREBRO DE RESPUESTAS
switch ($intent_name) {

    // --- MENÚ PRINCIPAL (LIMPIO) ---
    case 'Default Welcome Intent': 
    case 'navegacion_reiniciar':   
        $texto_bienvenida = "👋 ¡Hola! Soy el Asistente Virtual del GORE Pasco.\n\nSelecciona una opción para empezar:";
        $botones_principales = [
            "🔍 Consultar Trámite",
            "📂 Instrumentos de Gestión",
            // "🏢 Direcciones Regionales", <--- ELIMINADO DEL INICIO
            "📘 Normas y documentos legales (Tutorial)",
            "💬 Hablar con un Humano"
        ];
        $response_array = responderConTextoYBotones($texto_bienvenida, $botones_principales);
        break;

    // --- CONSULTA DE TRÁMITE ---
    case 'recibe_codigo_tramite':
        $codigo = $parametros['numero_expediente'] ?? '';
        $conn = new mysqli("localhost", "root", "", "prueba_chatbot"); 

        $texto_respuesta = "";
        $botones_salida = ["🔄 Consultar otro", "🏠 Volver al Menú"]; 

        if ($conn->connect_error) {
            $texto_respuesta = "⚠️ Error técnico de conexión a la base de datos.";
        } else {
            $sql = "SELECT * FROM tramites WHERE codigo_expediente = '$codigo'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $fila = $result->fetch_assoc();
                $texto_respuesta = "✅ **¡Encontrado!** \n" .
                    "📂 Expediente: " . $fila['codigo_expediente'] . "\n" .
                    "📊 Estado: " . $fila['estado'] . "\n" .
                    "📍 Ubicación: " . $fila['ubicacion'];

                if (!empty($fila['pdf_adjunto'])) {
                    $link_descarga = $base_files . $fila['pdf_adjunto'];
                    $texto_respuesta .= "\n\n📄 [Descargar Documento]($link_descarga)";
                }
            } else {
                $texto_respuesta = "❌ No encontré el expediente '$codigo'. \nPor favor verifica el número.";
                $botones_salida[] = "💬 Hablar con un Humano";
            }
            $conn->close();
        }
        $response_array = responderConTextoYBotones($texto_respuesta, $botones_salida);
        break;

    // --- SUBMENÚ INSTRUMENTOS ---
    case 'navegacion_instrumentos':
        $texto = "📂 **Instrumentos de Gestión**\nAquí tienes los documentos normativos vigentes de la región.";
        $botones_instrumentos = [
            "📋 Ver Texto Único de Procedimientos Administrativos (TUPA) 2024",
            "💼 Manual de Clasificador de Cargos (MCC)",
            "💰 Presupuesto Analítico de Personal (PAP)", 
            "🏛️ Reglamento de Organización y Funciones (ROF)",
            "📅 Plan de Desarrollo Regional Concertado (PDRC) 2034",
            "📈 Plan Estratégico Institucional (PEI)", // Cambiado ícono a gráfico
            "⚙️ MOP - Oxapampa", // Corregido typo y cambiado a MOP
            "🎓 MOP - Educación", // Icono de educación para diferenciar
            "🏠 Volver al Inicio"
        ];
        $response_array = responderConTextoYBotones($texto, $botones_instrumentos);
        break;

    // --- TUPA 2024 ---
    case 'consulta_tupa_2024':
        $pdf_link = $base_files . "tupa_2024.pdf";
        $tema_raw = $parametros['concepto_tupa'] ?? '';
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;

        if (!empty($tema_especifico)) {
            $tema_format = ucfirst($tema_especifico);
            $titulo = "TUPA: Trámites de $tema_format"; $subtitulo = "Requisitos para $tema_format";
        } else {
            $titulo = "TUPA GORE Pasco 2024"; $subtitulo = "Texto Único de Procedimientos Administrativos";
        }
        $puntos = ["💰 Costos: Derechos de pago actualizados (UIT 2024).", "📋 Requisitos: Documentos exactos.", "⏳ Plazos: Tiempos de atención."];
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/2910/2910768.png", $pdf_link, $puntos);
        break;

    // --- PDRC 2034 ---
    case 'consulta_pdrc_2034':
        $pdf_link = $base_files . "pdrc_2034.pdf";
        $tema_raw = $parametros['tema_pdrc'] ?? '';
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $titulo = "PDRC Pasco al 2034"; $subtitulo = "Plan de Desarrollo Concertado";
        $puntos = ["📅 Horizonte: 10 años (2025 - 2034)", "🔭 Visión: Pasco integrado.", "⚙️ Ejes: Social, Económico, Ambiental."];

        if (!empty($tema_especifico)) {
            $tema_norm = strtolower($tema_especifico);
            if ($tema_norm == 'social') { $titulo = "PDRC: Eje Social"; } 
            elseif ($tema_norm == 'economico') { $titulo = "PDRC: Desarrollo Económico"; } 
            elseif ($tema_norm == 'ambiental') { $titulo = "PDRC: Medio Ambiente"; }
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/3203/3203862.png", $pdf_link, $puntos);
        break;
	
    // --- PAP 2024 ---
    case 'consulta_presupuesto_personal':
        $pdf_link = $base_files . "pap_2024.pdf";
        $detalles = ["📅 Fecha: 10/06/2024", "🏛️ Alcance: Sede Central", "💰 Presupuesto: S/ 6,218,287.64"];
        $response_array = crearTarjetaDescarga("Presupuesto de Personal (PAP)", "Año Fiscal 2024", "https://cdn-icons-png.flaticon.com/512/3135/3135679.png", $pdf_link, $detalles);
        break;

    // --- ROF 2025 ---
    case 'consulta_rof_general':
        $pdf_link = $base_files . "rof_2025.pdf";
        $detalles = ["📜 Documento: ROF Institucional", "📅 Edición: 2025", "✅ Estado: Vigente"];
        $response_array = crearTarjetaDescarga("ROF Institucional 2025", "Reglamento de Organización", "https://cdn-icons-png.flaticon.com/512/2666/2666505.png", $pdf_link, $detalles);
        break;
	case 'consulta_mop_oxapampa':
        $pdf_link = $base_files . "mop_oxapampa.pdf";
        $detalles = ["📅 Fecha: 07/08/2023", "🏛️ Entidad: Gerencia Sub Regional Oxapampa", "📜 Aprobación: Decreto Regional N° 003-2023"];
        $response_array = crearTarjetaDescarga("Manual de Operaciones (MOP)", "Gestión 2022 - 2023", "https://cdn-icons-png.flaticon.com/512/3135/3135679.png", $pdf_link, $detalles);
        break;
    case 'consulta_mop_educacion':
        $pdf_link = $base_files . "mop_educacion.pdf";
        $detalles = ["📅 Fecha: 30/09/2025", "🏛️ Entidad: DRE Pasco y UGELs", "📜 Aprobación: Decreto Regional N° 005-2025"];
        $response_array = crearTarjetaDescarga("Manual de Operaciones (MOP)", "Sector Educación 2025", "https://cdn-icons-png.flaticon.com/512/3135/3135679.png", $pdf_link, $detalles);
        break;
    // --- PEI 2030 ---
    case 'consulta_pei_general':
        $pdf_link = $base_files . "pei_2025_2030.pdf";
        $detalles = ["📅 Periodo: 2025 - 2030", "🎯 Visión: Mejorar calidad de vida"];
        $response_array = crearTarjetaDescarga("Plan Estratégico (PEI)", "Visión Regional al 2030", "https://cdn-icons-png.flaticon.com/512/3358/3358964.png", $pdf_link, $detalles);
        break;

    // --- MCC (Cargos) ---
    case 'consulta_mcc_general':
        $pdf_link = $base_files . "mcc_cargos.pdf";
        $detalles = ["📜 Manual de Clasificador de Cargos", "🎯 Requisitos (Estudios y Experiencia)"];
        $response_array = crearTarjetaDescarga("Perfiles de Puesto (MCC)", "Requisitos GORE", "https://cdn-icons-png.flaticon.com/512/942/942748.png", $pdf_link, $detalles);
        break;

    // --- RESOLUCIONES (TUTORIAL CON IMAGEN GRANDE) ---
    case 'consultar_resoluciones':
        $url_resoluciones = "https://www.gob.pe/institucion/regionpasco/normas-legales";
        $url_video_tutorial = "https://www.youtube.com/watch?v=jXXAx11HTo4"; 
        
        // CAMBIO: Usamos 'sddefault' para mejor calidad de imagen
        $imagen_tutorial = "https://img.youtube.com/vi/jXXAx11HTo4/sddefault.jpg"; 

        $response_array = [
            "fulfillmentMessages" => [
                [
                    "payload" => [
                        "richContent" => [
                            [
                                // 1. IMAGEN GRANDE (Tipo 'image' ocupa todo el ancho)
                                [
                                    "type" => "image",
                                    "rawUrl" => $imagen_tutorial,
                                    "accessibilityText" => "Portada del Tutorial"
                                ],

                                // 2. Título y Subtítulo (Sin imagen pequeña)
                                [
                                    "type" => "info",
                                    "title" => "📘 Buscador de Normas Legales",
                                    "subtitle" => "Tutorial: Aprende a buscar resoluciones y decretos en el portal oficial.",
                                    "actionLink" => $url_video_tutorial
                                ],

                                // 3. BOTÓN FIJO 1: Video
                                [
                                    "type" => "button", 
                                    "icon" => ["type" => "play_circle", "color" => "#FF0000"],
                                    "text" => "🎥 Ver Video Tutorial", 
                                    "link" => $url_video_tutorial
                                ],

                                // 4. BOTÓN FIJO 2: Web
                                [
                                    "type" => "button", 
                                    "icon" => ["type" => "public", "color" => "#0057b7"],
                                    "text" => "🏛️ Ir al Buscador Oficial", 
                                    "link" => $url_resoluciones
                                ],

                                // 5. Navegación
                                [
                                    "type" => "chips", 
                                    "options" => [["text" => "🏠 Volver al Inicio", "link" => ""]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        break;

    // --- CONTACTAR FUNCIONARIO (SEDE CENTRAL) ---
    case 'contactar_funcionario':
        $response_array = [
            "fulfillmentMessages" => [
                [
                    "payload" => [
                        "richContent" => [
                            [
                                // 1. Cabecera con Título e Imagen
                                [
                                    "type" => "info", 
                                    "title" => "Atención al Ciudadano", 
                                    "subtitle" => "Sede Central GORE Pasco", 
                                    "image" => ["src" => ["rawUrl" => "https://cdn-icons-png.flaticon.com/512/3059/3059502.png"]]
                                ],
                                
                                // 2. LISTA DE DATOS (Aquí va lo que pediste ordenado)
                                [
                                    "type" => "description",
                                    "title" => "📌 Datos de Contacto:",
                                    "text" => [
                                        "📍 Dir: Sede Central: Edificio Estatal Nº 01 San Juan Pampa - Pasco",
                                        "📞 Tel: (063) 281262 / 969 704 480",
                                        "📧 Email: sistemas@regionpasco.gob.pe",
                                        "⏰ Horario: Lun-Vie (8:00am - 5:30pm)"
                                    ]
                                ],

                                // 3. Botones de Acción
                                [
                                    "type" => "chips", 
                                    "options" => [
                                        ["text" => "📞 Llamar a Sede Central", "link" => $fono_central],
                                        ["text" => "🏢 Contactar otras direcciones", "link" => ""], 
                                        ["text" => "🏠 Volver al Menú", "link" => ""]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        break;

    // --- SUBMENÚ DIRECCIONES ---
    // (Asegúrate de tener un Intent en Dialogflow con la frase "CONTACTAR OTRAS DIRECCIONES")
    case 'navegacion_direcciones':
        $texto = "🏢 **Directorio Regional Pasco**\nSelecciona la institución para ver sus datos:";
        $botones_direcciones = [
            "🚜 Dir. Regional Agraria", "🏥 Dir. Regional Salud", "🎓 Dir. Regional Educación",
            "🛣️ Dir. Regional Transportes", "👷 Dir. Regional Trabajo", "✈️ Dir. Regional Turismo",
            "🏠 Volver al Inicio"
        ];
        $response_array = responderConTextoYBotones($texto, $botones_direcciones);
        break;

    // --- FICHAS DE DIRECCIONES (ACTUALIZADAS CON DATOS) ---
    
    // 1. AGRARIA
    case 'info_agraria':
        $response_array = crearTarjetaDirectorio(
            "Dir. Regional Agraria", "Sector: Agricultura", 
            "https://cdn-icons-png.flaticon.com/512/2829/2829759.png", 
            "https://www.agropasco.gob.pe",
            [
                "📞 Tel:  063-793986 / 063-421899",
                "📍 Dir: Edificio Estatal N° 2 - San Juan, Cerro de Pasco - Perú",
                "📧 Email: direccionregional@agropasco.gob.pe",
                "🚜 Promueve cadenas productivas."
            ]
        );
        break;

    // 2. SALUD (DIRESA)
    case 'info_diresa':
        $response_array = crearTarjetaDirectorio(
            "DIRESA Pasco", "Sector: Salud", 
            "https://cdn-icons-png.flaticon.com/512/2382/2382461.png", 
            "https://diresapasco.gob.pe",
            [
                "📞 Tel: (063) 422284",
                "📍 Dir: Jr. José Carlos Marátegui N° 101 Yanacancha",
                "📧 Email: mesadepartes@diresapasco.gob.pe",
                "🏥 Gestión de Hospitales y Vacunación."
            ]
        );
        break;

    // 3. EDUCACIÓN (DRE)
    case 'info_educacion':
        $response_array = crearTarjetaDirectorio(
            "DRE Pasco", "Sector: Educación", 
            "https://cdn-icons-png.flaticon.com/512/2232/2232688.png", 
            "https://www.gob.pe/direccion-regional-de-educacion-pasco-dre-pasco",
            [
                "📞 Tel: (063) 421019",
                "📍 Dir: Av. Los Incas S/N, San Juan  Pampa - Yanacancha",
                "📧 Email: -",
                "🎓 Trámites de actas y certificados."
            ]
        );
        break;

    // 4. TRANSPORTES (DRTC)
    case 'info_transportes':
        $response_array = crearTarjetaDirectorio(
            "DRTC Pasco", "Sector: Transportes", 
            "https://cdn-icons-png.flaticon.com/512/2554/2554922.png", 
            "https://drtcpasco.gob.pe/",
            [
                "📞 Tel: (063) 422177",
                "📍 Dir: Av. El Minero N° 506, Cerro de Pasco, Peru",
                "📧 Email: mesapartes@drtcpasco.gob.pe",
                "🚗 Licencias y autorizaciones."
            ]
        );
        break;

    // 5. TRABAJO (DRTPE)
    case 'info_trabajo':
        $response_array = crearTarjetaDirectorio(
            "DRTPE Pasco", "Sector: Trabajo", 
            "https://cdn-icons-png.flaticon.com/512/1570/1570887.png", 
            "https://www.gob.pe/drtpepasco",
            [
                "📞 Tel: (063) 281659",
                "📍 Dir: AV. LOS PROCERES Nº 707 - YANACANCHA",
                "📧 Email: direcciontrabajo@regionpasco.gob.pe",
                "👷 Carnet construcción civil."
            ]
        );
        break;

    // 6. TURISMO (DIRCETUR)
    case 'info_turismo':
        $response_array = crearTarjetaDirectorio(
            "DIRCETUR Pasco", "Sector: Turismo", 
            "https://cdn-icons-png.flaticon.com/512/3125/3125848.png", 
            "http://dirceturpasco.pe",
            [
                "📞 Tel: (063) 421019",
                "📍 Dir: Av. Los Próceres, edificio Estatal N°1, San Juan Pampa",
                "📧 Email: turismo@regionpasco.gob.pe",
                "✈️ Promoción turística y artesanía."
            ]
        );
        break;

    // --- DEFAULT FALLBACK ---
    default:
        $response_array = responderConTextoYBotones("🤔 No estoy seguro de haber entendido. ¿Qué prefieres hacer?", ["💬 Hablar con un Humano", "🔍 Consultar Trámite", "🏠 Volver al Menú"]);
        break;
}

// 4. FUNCIONES AUXILIARES

function crearTarjetaDescarga($titulo, $subtitulo, $img_url, $link, $lista_detalles = []) {
    $contenido = [
        ["type" => "info", "title" => $titulo, "subtitle" => $subtitulo, "image" => ["src" => ["rawUrl" => $img_url]], "actionLink" => $link]
    ];
    if (!empty($lista_detalles)) {
        $contenido[] = ["type" => "description", "title" => "📋 Detalles:", "text" => $lista_detalles];
    }
    // BOTÓN FIJO (type: button)
    $contenido[] = [
        "type" => "button", 
        "icon" => ["type" => "description", "color" => "#FF0000"], 
        "text" => "📄 Descargar PDF Oficial", 
        "link" => $link
    ];
    $contenido[] = ["type" => "chips", "options" => [["text" => "🏠 Volver al Inicio", "link" => ""]]];
    return ["fulfillmentMessages" => [["payload" => ["richContent" => [$contenido]]]]];
}

function crearTarjetaDirectorio($titulo, $subtitulo, $img_url, $web_link, $detalles = []) {
    $contenido = [
        ["type" => "info", "title" => $titulo, "subtitle" => $subtitulo, "image" => ["src" => ["rawUrl" => $img_url]], "actionLink" => $web_link]
    ];
    if (!empty($detalles)) {
        $contenido[] = ["type" => "description", "title" => "📌 Datos de Contacto:", "text" => $detalles];
    }
    // BOTÓN FIJO (type: button)
    $contenido[] = [
        "type" => "button", 
        "icon" => ["type" => "chevron_right", "color" => "#0057b7"], 
        "text" => "🌐 Ir al Sitio Web Oficial", 
        "link" => $web_link
    ];
    $contenido[] = ["type" => "chips", "options" => [["text" => "🏠 Volver al Inicio", "link" => ""]]];
    return ["fulfillmentMessages" => [["payload" => ["richContent" => [$contenido]]]]];
}

function responderConTextoYBotones($texto, $botones = []) {
    $respuesta = ["fulfillmentMessages" => [["text" => ["text" => [$texto]]]]];
    if (!empty($botones)) {
        $respuesta["fulfillmentMessages"][] = [
            "payload" => ["richContent" => [[["type" => "chips", "options" => array_map(function ($txt) { return ["text" => $txt]; }, $botones)]]]]
        ];
    }
    return $respuesta;
}

// 5. ENVIAR RESPUESTA FINAL
echo json_encode($response_array);
?>