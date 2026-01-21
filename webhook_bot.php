<?php
header('Content-Type: application/json');

// --- CONFIGURACIÓN CENTRAL ---
// Actualiza esta línea con tu nuevo link de Cloudflare/Ngrok
$url_base = "https://sugar-california-gym-challenge.trycloudflare.com"; 
// -----------------------------

// Ajustamos la ruta para que apunte a tu carpeta correcta
$base_files = $url_base . "/botgorepasco/documentos/";

// --- CONTACTO GORE PASCO ---
$wsp_soporte = "https://wa.me/51969704480"; 
$fono_central = "tel:+51969704480"; 

// 1. RECIBIR DATOS DE DIALOGFLOW
$json = file_get_contents('php://input');
$request = json_decode($json, true);

// 2. DETECTAR QUÉ INTENT SE ACTIVÓ
$intent_name = $request['queryResult']['intent']['displayName'] ?? '';
$parametros = $request['queryResult']['parameters'] ?? [];

// Variable para guardar la respuesta
$response_array = [];

// 3. CEREBRO DE RESPUESTAS DINÁMICAS
switch ($intent_name) {

    // --- CASO MENÚ PRINCIPAL (Inicio y Reinicio) ---
    case 'Default Welcome Intent': 
    case 'navegacion_reiniciar':   
        
        $texto_bienvenida = "👋 ¡Hola! Soy el Asistente Virtual del GORE Pasco.\n\n" .
                            "Selecciona una opción para empezar:";
        
        $botones_principales = [
            "🔍 Consultar Trámite",
            "📂 Instrumentos de Gestión",
            "🏢 Direcciones Regionales", 
            "📘 Normas y documentos legales (Tutorial)",
            "💬 Hablar con un Humano"
        ];

        $response_array = responderConTextoYBotones($texto_bienvenida, $botones_principales);
        break;

    // --- CASO: CONSULTA DE TRÁMITE (Base de Datos) ---
    case 'recibe_codigo_tramite':
        $codigo = $parametros['numero_expediente'] ?? '';

        // OJO: Si usas XAMPP por defecto, la contraseña suele ser vacía ""
        // Si pusiste contraseña, cámbiala aquí.
        $conn = new mysqli("localhost", "root", "", "prueba_chatbot");

        $texto_respuesta = "";
        $botones_salida = ["🔄 Consultar otro", "🏠 Volver al Menú"]; 

        if ($conn->connect_error) {
            $texto_respuesta = "⚠️ Error técnico de conexión a la base de datos.";
        } else {
            $sql = "SELECT * FROM tramites WHERE codigo_expediente = '$codigo'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // SI LO ENCUENTRA
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
                // NO LO ENCUENTRA
                $texto_respuesta = "❌ No encontré el expediente '$codigo'. \n" .
                    "Por favor verifica el número e inténtalo de nuevo.";
                $botones_salida[] = "💬 Hablar con un Humano";
            }
            $conn->close();
        }

        $response_array = responderConTextoYBotones($texto_respuesta, $botones_salida);
        break;

    // --- CASO: SUBMENÚ DE INSTRUMENTOS DE GESTIÓN ---
    case 'navegacion_instrumentos':
        $texto = "📂 **Instrumentos de Gestión**\n" .
            "Aquí tienes los documentos normativos vigentes. ¿Cuál deseas consultar?";

        $botones_instrumentos = [
            "📋 Ver Texto Único de Procedimientos Administrativos (TUPA) 2024",
            "💼 Manual de Clasificador de Cargos (MCC)",
            "💰 Presupuesto Analítico de Personal (PAP)", 
            "🏛️ Reglamento de Organización y Funciones (ROF)",
            "📅 Plan de Desarrollo Regional Concertado (PDRC) 2034",
            "🚑 Plan Estratégico Institucional (PEI)",
            "🏠 Volver al Inicio"
        ];

        $response_array = responderConTextoYBotones($texto, $botones_instrumentos);
        break;

    // --- CASO: SUBMENÚ DIRECCIONES REGIONALES ---
    case 'navegacion_direcciones':
        $texto = "🏢 **Directorio Regional Pasco**\n" .
                 "Selecciona la institución con la que deseas contactar:";
        
        $botones_direcciones = [
            "🚜 Dir. Regional Agraria",
            "🏥 DIRESA (Salud)",
            "🎓 DRE (Educación)",
            "🛣️ DRTC (Transportes)",
            "👷 DRTPE (Trabajo)",
            "✈️ DIRCETUR (Turismo)",
            "🏠 Volver al Inicio"
        ];
        
        $response_array = responderConTextoYBotones($texto, $botones_direcciones);
        break;

    // --- CASO: RESOLUCIONES (Con Tutorial y Enlace Oficial) ---
    case 'consultar_resoluciones':
        $url_resoluciones = "https://www.gob.pe/institucion/regionpasco/normas-legales";
        $url_video_tutorial = "https://www.youtube.com/watch?v=jXXAx11HTo4"; 
        $imagen_tutorial = "https://img.youtube.com/vi/jXXAx11HTo4/mqdefault.jpg"; 

        $response_array = [
            "fulfillmentMessages" => [
                [
                    "payload" => [
                        "richContent" => [
                            [
                                [
                                    "type" => "image",
                                    "rawUrl" => $imagen_tutorial,
                                    "accessibilityText" => "Tutorial de Búsqueda"
                                ],
                                [
                                    "type" => "info",
                                    "title" => "📘 Buscador de Resoluciones",
                                    "subtitle" => "Tutorial: Aprende a filtrar normas regionales en Gob.pe",
                                    "actionLink" => $url_video_tutorial
                                ],
                                [
                                    "type" => "chips",
                                    "options" => [
                                        ["text" => "🏛️ Ir al Buscador Oficial", "link" => $url_resoluciones],
                                        ["text" => "🎥 Ver Video Tutorial", "link" => $url_video_tutorial],
                                        ["text" => "🏠 Volver al Inicio", "link" => ""] 
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        break;

    // --- CASO: TUPA 2024 (Con Búsqueda) ---
    case 'consulta_tupa_2024':
        $pdf_link = $base_files . "tupa_2024.pdf";
        $tema_raw = $parametros['concepto_tupa'] ?? '';
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;

        if (!empty($tema_especifico)) {
            $tema_format = ucfirst($tema_especifico);
            $titulo_tarjeta = "TUPA: Trámites de $tema_format";
            $subtitulo_tarjeta = "Requisitos y Costos para $tema_format";
        } else {
            $titulo_tarjeta = "TUPA GORE Pasco 2024";
            $subtitulo_tarjeta = "Texto Único de Procedimientos Administrativos";
        }

        $puntos_tupa = [
            "💰 Costos: Derechos de pago actualizados (UIT 2024).",
            "📋 Requisitos: Documentos exactos para tu expediente.",
            "🏢 Áreas: Transportes, Trabajo, Turismo, Producción y más.",
            "⏳ Plazos: Tiempos de atención y silencio administrativo."
        ];

        $response_array = crearTarjetaDescarga($titulo_tarjeta, $subtitulo_tarjeta, "https://cdn-icons-png.flaticon.com/512/2910/2910768.png", $pdf_link, $puntos_tupa);
        break;

    // --- CASO: PDRC 2034 (Diccionario) ---
    case 'consulta_pdrc_2034':
        $pdf_link = $base_files . "pdrc_2034.pdf";
        $tema_raw = $parametros['tema_pdrc'] ?? '';
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;

        $titulo = "PDRC Pasco al 2034";
        $subtitulo = "Plan de Desarrollo Concertado";
        $puntos_clave = [
            "📅 Horizonte: 10 años (2025 - 2034)",
            "🔭 Visión: Pasco integrado y sostenible.",
            "⚙️ Ejes: Social, Económico, Ambiental e Inst."
        ];

        if (!empty($tema_especifico)) {
            $tema_normalizado = strtolower($tema_especifico);
            if ($tema_normalizado == 'social') {
                $titulo = "PDRC: Eje Social"; $subtitulo = "Salud, Educación y Vivienda";
            } elseif ($tema_normalizado == 'economico') {
                $titulo = "PDRC: Desarrollo Económico"; $subtitulo = "Empleo, Agro y Turismo";
            } elseif ($tema_normalizado == 'ambiental') {
                $titulo = "PDRC: Medio Ambiente"; $subtitulo = "Sostenibilidad y Recursos";
            } elseif ($tema_normalizado == 'infraestructura') {
                $titulo = "PDRC: Infraestructura"; $subtitulo = "Vías y Conectividad";
            }
        }

        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/3203/3203862.png", $pdf_link, $puntos_clave);
        break;

    // --- CASO: PAP 2024 ---
    case 'consulta_presupuesto_personal':
        $pdf_link = $base_files . "pap_2024.pdf";
        $detalles_pap = [
            "📅 Fecha Aprobación: 10 de Junio de 2024",
            "🏛️ Alcance: Sede Central (Unidad Ejecutora 001)",
            "💰 Presupuesto Anual: S/ 6,218,287.64",
            "⚖️ Norma: Res. Ejecutiva N° 240-2024-G.R.P."
        ];
        $response_array = crearTarjetaDescarga("Presupuesto de Personal (PAP)", "Año Fiscal 2024 - GORE Pasco", "https://cdn-icons-png.flaticon.com/512/3135/3135679.png", $pdf_link, $detalles_pap);
        break;

    // --- CASO: ROF 2025 ---
    case 'consulta_rof_general':
        $pdf_link = $base_files . "rof_2025.pdf";
        $detalles_rof = [
            "📜 Documento: ROF Institucional",
            "📅 Edición: 2025",
            "🎯 Objetivo: Definir funciones de cada área",
            "✅ Estado: Vigente"
        ];
        $response_array = crearTarjetaDescarga("ROF Institucional 2025", "Reglamento de Organización y Funciones", "https://cdn-icons-png.flaticon.com/512/2666/2666505.png", $pdf_link, $detalles_rof);
        break;

    // --- CASO: PEI 2030 ---
    case 'consulta_pei_general':
        $pdf_link = $base_files . "pei_2025_2030.pdf";
        $detalles_pei = [
            "📅 Periodo: 2025 - 2030",
            "⚖️ Norma: Res. Ejecutiva N° 0684-2024",
            "🎯 Visión: Mejorar calidad de vida en Pasco"
        ];
        $response_array = crearTarjetaDescarga("Plan Estratégico (PEI)", "Visión Regional al 2030", "https://cdn-icons-png.flaticon.com/512/3358/3358964.png", $pdf_link, $detalles_pei);
        break;

    // --- CASO: MCC (Manual Cargos) ---
    case 'consulta_mcc_general':
        $pdf_link = $base_files . "mcc_cargos.pdf";
        $detalles_mcc = [
            "📜 Documento: Manual de Clasificador de Cargos",
            "🎯 Contenido: Requisitos (Estudios y Experiencia)",
            "👥 Alcance: Personal Nombrado y Contratado"
        ];
        $response_array = crearTarjetaDescarga("Perfiles de Puesto (MCC)", "Requisitos para trabajar en el GORE", "https://cdn-icons-png.flaticon.com/512/942/942748.png", $pdf_link, $detalles_mcc);
        break;

    // --- CASOS DE DIRECCIONES REGIONALES ---
    case 'info_agraria':
        $texto = "🚜 **Dirección Regional Agraria Pasco**\n\nPromover el desarrollo agrario y seguridad alimentaria.\n🌐 [Web Oficial](https://www.agropasco.gob.pe)";
        $response_array = responderConTextoYBotones($texto, ["🏢 Ver otra Dirección", "🏠 Volver al Inicio"]);
        break;

    case 'info_diresa':
        $texto = "🏥 **DIRESA Pasco (Salud)**\n\nAutoridad regional de salud, hospitales y vacunación.\n🌐 [Web Oficial](https://diresapasco.gob.pe)";
        $response_array = responderConTextoYBotones($texto, ["🏢 Ver otra Dirección", "🏠 Volver al Inicio"]);
        break;

    case 'info_educacion':
        $texto = "🎓 **DRE Pasco (Educación)**\n\nGestión educativa básica, superior y trámites de actas.\n🌐 [Web Oficial](https://www.gob.pe/direccion-regional-de-educacion-pasco-dre-pasco)";
        $response_array = responderConTextoYBotones($texto, ["🏢 Ver otra Dirección", "🏠 Volver al Inicio"]);
        break;

    case 'info_transportes':
        $texto = "🛣️ **DRTC Pasco (Transportes)**\n\nInfraestructura vial, licencias de conducir y autorizaciones.\n🌐 [Web Oficial](https://www.gob.pe/drtcpasco)";
        $response_array = responderConTextoYBotones($texto, ["🏢 Ver otra Dirección", "🏠 Volver al Inicio"]);
        break;

    case 'info_trabajo':
        $texto = "👷 **DRTPE Pasco (Trabajo)**\n\nEmpleo formal, denuncias laborales y construcción civil.\n🌐 [Web Oficial](https://www.gob.pe/drtpepasco)";
        $response_array = responderConTextoYBotones($texto, ["🏢 Ver otra Dirección", "🏠 Volver al Inicio"]);
        break;
        
    case 'info_turismo':
        $texto = "✈️ **DIRCETUR Pasco**\n\nTurismo, artesanía y comercio exterior.\n🌐 [Web Oficial](http://dirceturpasco.pe)";
        $response_array = responderConTextoYBotones($texto, ["🏢 Ver otra Dirección", "🏠 Volver al Inicio"]);
        break;

    // --- CASO: CONTACTAR FUNCIONARIO ---
    case 'contactar_funcionario':
        $titulo = "Canales de Atención Ciudadana";
        $subtitulo = "Horario: Lunes a Viernes (8:00am - 5:00pm)";
        $response_array = [
            "fulfillmentMessages" => [
                [
                    "payload" => [
                        "richContent" => [
                            [
                                [
                                    "type" => "info", "title" => $titulo, "subtitle" => $subtitulo,
                                    "image" => ["src" => ["rawUrl" => "https://cdn-icons-png.flaticon.com/512/3059/3059502.png"]]
                                ],
                                [
                                    "type" => "chips",
                                    "options" => [
                                        ["text" => "💬 Chatear por WhatsApp", "link" => $wsp_soporte],
                                        ["text" => "📞 Llamar a Sede Central", "link" => $fono_central],
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

    // --- CASO: DEFAULT FALLBACK (No entendí) ---
    case 'Default Fallback Intent':
    default:
        $texto_error = "🤔 Mmm... no estoy seguro de haber entendido. ¿Qué prefieres hacer?";
        $botones_ayuda = ["💬 Hablar con un Humano", "🔍 Consultar Trámite", "🏠 Volver al Menú"];
        $response_array = responderConTextoYBotones($texto_error, $botones_ayuda);
        break;
}

// 4. FUNCIONES AUXILIARES

function crearTarjetaDescarga($titulo, $subtitulo, $img_url, $link, $lista_detalles = []) {
    $contenido = [
        [
            "type" => "info", "title" => $titulo, "subtitle" => $subtitulo,
            "image" => ["src" => ["rawUrl" => $img_url]], "actionLink" => $link
        ]
    ];
    if (!empty($lista_detalles)) {
        $contenido[] = ["type" => "description", "title" => "📋 Detalles:", "text" => $lista_detalles];
    }
    $contenido[] = ["type" => "chips", "options" => [["text" => "📄 Descargar PDF", "link" => $link]]];

    return ["fulfillmentMessages" => [["payload" => ["richContent" => [$contenido]]]]];
}

function crearTarjetaInfo($titulo, $subtitulo, $img_url, $link, $boton_texto, $lista_detalles = []) {
    // Reutilizamos la lógica, es muy similar
    return crearTarjetaDescarga($titulo, $subtitulo, $img_url, $link, $lista_detalles);
}

function responderConTextoYBotones($texto, $botones = []) {
    $respuesta = ["fulfillmentMessages" => [["text" => ["text" => [$texto]]]]];
    if (!empty($botones)) {
        $respuesta["fulfillmentMessages"][] = [
            "payload" => [
                "richContent" => [[["type" => "chips", "options" => array_map(function ($txt) { return ["text" => $txt]; }, $botones)]]]
            ]
        ];
    }  
    return $respuesta;
}

// 5. ENVIAR RESPUESTA FINAL
echo json_encode($response_array);
?>