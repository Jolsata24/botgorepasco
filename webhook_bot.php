<?php
header('Content-Type: application/json');

// --- CONFIGURACIÓN CENTRAL ---
// Actualiza esta línea con tu nuevo link de Ngrok cada vez que lo abras
$ngrok_url = "https://unclinical-ungeometrically-elenor.ngrok-free.dev";
// -----------------------------

$base_files = $ngrok_url . "/pruebabot/documentos/";
// --- CONTACTO GORE PASCO ---
// Formato WhatsApp: https://wa.me/51NÚMERO (Sin espacios ni guiones)
// Formato Llamada: tel:+51NÚMERO
$wsp_soporte = "https://wa.me/51969704480"; // <--- PON AQUÍ EL NÚMERO REAL DE IMAGEN/SOPORTE
$fono_central = "tel:+51969704480"; // <--- PON EL NÚMERO FIJO DE LA SEDE
// 1. RECIBIR DATOS DE DIALOGFLOW
$json = file_get_contents('php://input');
$request = json_decode($json, true);

// 2. DETECTAR QUÉ INTENT SE ACTIVÓ
// Usamos el operador '??' para evitar errores si no viene el intent
$intent_name = $request['queryResult']['intent']['displayName'] ?? '';
$parametros = $request['queryResult']['parameters'] ?? [];

// Variable para guardar la respuesta
$response_array = [];

// 3. CEREBRO DE RESPUESTAS DINÁMICAS
switch ($intent_name) {
    // --- CASO MENÚ PRINCIPAL (Inicio y Reinicio) ---
    // --- CASO MENÚ PRINCIPAL (Inicio y Reinicio) ---
    case 'Default Welcome Intent': // Cuando dicen "Hola"
    case 'navegacion_reiniciar':   // Cuando dicen "Volver al menú"
        
        $texto_bienvenida = "👋 ¡Hola! Soy el Asistente Virtual del GORE Pasco.\n\n" .
                            "Estoy conectado a los documentos oficiales de gestión (2024-2034) para brindarte información transparente y rápida.\n\n" .
                            "¿Qué información necesitas hoy?";
        
        // Menú Principal con opción de CONTACTO al final
        $botones_menu = [
            "🔍 Consultar Trámite",
            "📋 Ver TUPA 2024",
            "💼 Perfiles Puesto (MCC)",
            "💰 Sueldo Gobernador",
            "🏛️ Organigrama (ROF)",
            "📅 Plan PDRC 2034",
            "🚑 Objetivos PEI",
            "💬 Hablar con un Humano" // <--- ¡AQUÍ ESTÁ EL NUEVO BOTÓN!
        ];

        $response_array = responderConTextoYBotones($texto_bienvenida, $botones_menu);
        break;

    // --- CASO A: CONSULTA DE TRÁMITE ---
    case 'recibe_codigo_tramite':
        $codigo = $parametros['numero_expediente'] ?? '';
        
        // Conexión estándar (Asegúrate que la clave sea la correcta de tu servidor)
        $conn = new mysqli("localhost", "root", "123456", "prueba_chatbot");
        
        $texto_respuesta = "";
        $botones_salida = ["🔄 Consultar otro", "🏠 Volver al Menú"]; // Botones por defecto

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
                // NO LO ENCUENTRA (Aquí es útil ofrecer ayuda humana también)
                $texto_respuesta = "❌ No encontré el expediente '$codigo'. \n" .
                                   "Por favor verifica el número e inténtalo de nuevo.";
                
                // Si falla, le damos la opción de llamar para que no se frustre
                $botones_salida[] = "💬 Hablar con un Humano"; 
            }
            $conn->close();
        }

        // Enviamos la respuesta con los botones dinámicos
        $response_array = responderConTextoYBotones($texto_respuesta, $botones_salida);
        break;

    // CASO B: Requisitos Ambientales (Ahora con lista detallada)
    case 'consulta_requisitos_ambiental':
        $pdf_link = $base_files . "tupa_ambiental.pdf";

        // Creamos la lista de requisitos para que el usuario los lea rápido
        $requisitos = [
            "1. Solicitud Única de Trámite (FUT)",
            "2. Copia de DNI del solicitante",
            "3. Instrumento de Gestión Ambiental (Digital e Impreso)",
            "4. Pago por derecho de trámite (Banco de la Nación)"
        ];

        $response_array = crearTarjetaDescarga(
            "Certificación Ambiental (Cat. I)",   // Título más formal
            "Evaluación Preliminar - TUPA 2025",  // Subtítulo
            "https://cdn-icons-png.flaticon.com/512/337/337946.png",
            $pdf_link,
            $requisitos // <--- ¡Aquí pasamos la lista nueva!
        );
        break;

    // CASO C: Presupuesto de Personal (PAP 2024) - ¡Ahora con resumen!
    case 'consulta_presupuesto_personal':
        $pdf_link = $base_files . "pap_2024.pdf";

        // Datos extraídos directamente de la Resolución N° 240 [cite: 131, 206, 2016]
        $detalles_pap = [
            "📅 Fecha Aprobación: 10 de Junio de 2024",
            "🏛️ Alcance: Sede Central (Unidad Ejecutora 001)",
            "💰 Presupuesto Anual: S/ 6,218,287.64",
            "⚖️ Norma: Res. Ejecutiva N° 240-2024-G.R.P.",
            "✍️ Firma: Gob. Juan Luis Chombo Heredia"
        ];

        $response_array = crearTarjetaDescarga(
            "Presupuesto de Personal (PAP)",
            "Año Fiscal 2024 - GORE Pasco",
            "https://cdn-icons-png.flaticon.com/512/3135/3135679.png",
            $pdf_link,
            $detalles_pap // <--- Aquí pasamos la lista con los datos clave
        );
        break;

    // CASO D: Sueldo del Gobernador - (Detallado según PAP 2024)
    case 'consulta_sueldo_gobernador':
        $pdf_link = $base_files . "pap_2024.pdf";

        // Datos extraídos de la Pág. 05 del documento oficial
        $detalles_sueldo = [
            "💵 Mensual: S/ 14,398.28",          // [cite: 257]
            "🎁 Aguinaldos: S/ 600.00 (Jul/Dic)", // [cite: 263]
            "🎒 Escolaridad: S/ 400.00",         // [cite: 262]
            "📈 Costo Anual: S/ 173,779.36",      // [cite: 264]
            "🗳️ Régimen: Elección Popular"       // [cite: 252]
        ];

        $response_array = crearTarjetaInfo(
            "Gobernador Regional",
            "Juan Luis Chombo Heredia",
            "https://cdn-icons-png.flaticon.com/512/4825/4825038.png",
            $pdf_link,
            "Ver Documento Oficial",
            $detalles_sueldo // <--- ¡Ahora pasamos la lista aquí!
        );
        break;

    // CASO E: Plan de Desarrollo (PDRC) - (Detallado según Ordenanza 526)
    case 'consulta_plan_desarrollo':
        $pdf_link = $base_files . "normas_pasco.pdf";

        // Datos extraídos de la Ordenanza Regional N° 526-2025 [cite: 21, 19, 74, 56]
        $detalles_pdrc = [
            "📅 Periodo: 2025 - 2034",
            "📜 Norma: Ordenanza N° 526-2025-G.R.P/CR",
            "🎯 Objetivo: Orientar el desarrollo integral de la Región Pasco",
            "✅ Validación: Aprobado por CEPLAN (Fase 1, 2 y 3)",
            "🏛️ Estado: Vigente y de cumplimiento obligatorio"
        ];

        $response_array = crearTarjetaDescarga(
            "Plan de Desarrollo (PDRC)",
            "La hoja de ruta de Pasco al 2034",
            "https://cdn-icons-png.flaticon.com/512/3203/3203892.png",
            $pdf_link,
            $detalles_pdrc // <--- Lista de detalles clave
        );
        break;

    // CASO F: Presupuesto y Obras (Detalle de Inversiones 2025)
    case 'consulta_presupuesto_obras':
        $pdf_link = $base_files . "normas_pasco.pdf";
        $detalles_obras = [
            "💰 Monto Total: S/ 17'489,148.00", // Dato del PDF oficial
            "📜 Norma: Res. Ejecutiva N° 0258-2025",
            "💧 Agua: Chontabamba y Oxapampa",
            "☕ Café: Villa Rica, Pozuzo y Pto. Bermúdez",
            "🏦 Fuente: Recursos Determinados (Canon)"
        ];
        $response_array = crearTarjetaDescarga(
            "Modificación Presupuestal 2025",
            "Obras de Agua y Café en Selva Central",
            "https://cdn-icons-png.flaticon.com/512/2454/2454269.png",
            $pdf_link,
            $detalles_obras
        );
        break;

    // CASO K: ESTRUCTURA ORGÁNICA (Organigrama)
    case 'consulta_estructura_gore':
        // Usamos el PAP 2024 porque ahí aparecen listadas todas las gerencias reales
        $pdf_link = $base_files . "pap_2024.pdf";

        //[cite_start] Lista de las principales oficinas extraída del documento [cite: 240, 267, 280, 1201, 1321, 1340]
        $estructura = [
            "1. 🏛️ Consejo Regional (Fiscalizador)",
            "2. 👤 Gobernación y Vicegobernación",
            "3. 🏢 Gerencia General Regional",
            "4. 🚜 Ger. Desarrollo Económico",
            "5. 🤝 Ger. Desarrollo Social",
            "6. 🏗️ Ger. Infraestructura",
            "7. 🌿 Ger. Recursos Naturales y Medio Ambiente"
        ];

        $response_array = crearTarjetaInfo(
            "Estructura Orgánica",
            "Organización del GORE Pasco",
            "https://cdn-icons-png.flaticon.com/512/942/942799.png", // Icono de organigrama
            $pdf_link,
            "Ver Documento de Gestión",
            $estructura
        );
        break;
    // --- NUEVO: CASO G: CONSULTA CAP GENERAL ---
    case 'consulta_cap_general':
        $pdf_link = $base_files . "CAP_2014(Ord-344-2014).pdf";

        // Datos extraídos de la Ordenanza N° 344-2014
        $detalles_cap = [
            "📜 Documento: Cuadro para Asignación de Personal (CAP)",
            "⚖️ Aprobación: Ordenanza Regional N° 344-2014-G.R.PASCO/CR",
            "📅 Fecha: 10 de Abril de 2014",
            "🏢 Entidad: Gobierno Regional Pasco - Sede Central",
            "🎯 Fin: Organizar y conducir la gestión pública regional"
        ];

        $response_array = crearTarjetaDescarga(
            "Cuadro de Asignación de Personal",
            "Documento de Gestión (CAP)",
            "https://cdn-icons-png.flaticon.com/512/1570/1570102.png", // Icono de organigrama
            $pdf_link,
            $detalles_cap
        );
        break;

    // --- NUEVO: CASO H: PLAZAS EN INFRAESTRUCTURA ---
    case 'consulta_plazas_infraestructura':
        $pdf_link = $base_files . "CAP_2014(Ord-344-2014).pdf";

        // Datos extraídos de la Pág. 14 del PDF (Gerencia Infraestructura)
        $detalles_infra = [
            "🏗️ Gerencia Regional: 2 plazas",
            "📐 Sub Gerencia Estudios: 6 plazas",
            "🚜 Sub Gerencia Obras y Equipo Mecánico: 9 plazas",
            "👷 Supervisión de Obras: 7 plazas",
            "📉 Liquidaciones: 6 plazas"
        ];

        $response_array = crearTarjetaInfo(
            "Plazas: Infraestructura",
            "Detalle según CAP - Sede Central",
            "https://cdn-icons-png.flaticon.com/512/2942/2942544.png", // Icono de construcción
            $pdf_link,
            "Ver Cuadro Completo",
            $detalles_infra
        );
        break;

    // --- NUEVO: CASO I: CONSULTA MOP OXAPAMPA (General) ---
    case 'consulta_mop_oxapampa':
        $pdf_link = $base_files . "mop_oxapampa.pdf";

        // Datos extraídos del Decreto Regional N° 003-2023
        $detalles_mop = [
            "📜 Documento: Manual de Operaciones (MOP)",
            "⚖️ Norma: Decreto Regional N° 003-2023-G.R.P.",
            "📅 Aprobación: 07 de Agosto de 2023",
            "🏢 Entidad: Gerencia Sub Regional Oxapampa",
            "📍 Ámbito: Selva Central (Oxapampa, Villa Rica, etc.)"
        ];

        $response_array = crearTarjetaDescarga(
            "MOP - Selva Central",
            "Manual de Operaciones 2023",
            "https://cdn-icons-png.flaticon.com/512/2830/2830155.png", // Icono de mapa/selva
            $pdf_link,
            $detalles_mop
        );
        break;

    // --- NUEVO: CASO J: FUNCIONES Y COMPETENCIAS (Oxapampa) ---
    case 'consulta_funciones_oxapampa':
        $pdf_link = $base_files . "mop_oxapampa.pdf";

        // Datos extraídos de las funciones (Pág. Posterior del PDF)
        $funciones_oxa = [
            "🚜 Desarrollo Agropecuario y Turismo",
            "🏗️ Ejecución y Supervisión de Obras",
            "📈 Proyectos de Desarrollo Económico",
            "📝 Liquidación de Proyectos de Inversión",
            "🤝 Convenios con municipios locales"
        ];

        $response_array = crearTarjetaInfo(
            "Competencias: Oxapampa",
            "Funciones de la Sub Región",
            "https://cdn-icons-png.flaticon.com/512/3063/3063823.png", // Icono de gestión
            $pdf_link,
            "Ver Manual Completo",
            $funciones_oxa
        );
        break;
    // --- NUEVO: CASO K: ROF 2025 (Reglamento General) ---
    case 'consulta_rof_general':
        $pdf_link = $base_files . "rof_2025.pdf";

        // Datos del documento ROF 2025
        $detalles_rof = [
            "📜 Documento: Reglamento de Organización y Funciones",
            "📅 Edición: ROF - 2025",
            "🎯 Objetivo: Definir funciones y competencias de cada área",
            "🏢 Alcance: Todas las gerencias y unidades orgánicas",
            "✅ Estado: Vigente para el año fiscal 2025"
        ];

        $response_array = crearTarjetaDescarga(
            "ROF Institucional 2025",
            "Reglamento de Organización y Funciones",
            "https://cdn-icons-png.flaticon.com/512/2666/2666505.png", // Icono de reglamento/libro
            $pdf_link,
            $detalles_rof
        );
        break;

    // --- NUEVO: CASO L: SISTEMAS Y SIGLAS (Datos técnicos del PDF) ---
    case 'consulta_sistemas_gestion':
        $pdf_link = $base_files . "rof_2025.pdf";

        // Datos extraídos de la lista de siglas del PDF
        $sistemas_rof = [
            "🏗️ INFOBRAS: Sistema de Información de Obras Públicas",
            "🌿 SIAR: Sistema de Información Ambiental Regional",
            "🚨 COER: Centro de Operaciones de Emergencia Regional",
            "📉 SEIA: Sistema de Evaluación de Impacto Ambiental",
            "🛡️ SINPAD: Sistema Nacional de Respuesta (Desastres)"
        ];

        $response_array = crearTarjetaInfo(
            "Sistemas de Gestión",
            "Herramientas definidas en el ROF 2025",
            "https://cdn-icons-png.flaticon.com/512/8089/8089114.png", // Icono de sistema/red
            $pdf_link,
            "Ver Glosario Completo",
            $sistemas_rof
        );
        break;

    // --- NUEVO: CASO M: MOP EDUCACIÓN (DREP 2025) ---
    case 'consulta_mop_educacion':
        $pdf_link = $base_files . "mop_educacion.pdf";

        // Datos del Decreto Regional N° 005-2025
        $detalles_drep = [
            "📜 Documento: Manual de Operaciones (MOP) - DREP",
            "⚖️ Norma: Decreto Regional N° 005-2025-G.R.P.", // [cite: 588]
            "📅 Aprobación: 30 de Septiembre de 2025", // [cite: 590]
            "🏫 Entidad: Dirección Regional de Educación Pasco",
            "🎯 Objetivo: Modernizar la gestión educativa regional"
        ];

        $response_array = crearTarjetaDescarga(
            "Manual de Educación (DREP)",
            "Gestión Educativa 2025",
            "https://cdn-icons-png.flaticon.com/512/167/167707.png", // Icono de educación/libro
            $pdf_link,
            $detalles_drep
        );
        break;

    // --- NUEVO: CASO N: ESTADÍSTICA DE COLEGIOS (Datos del Anexo PDF) ---
    case 'consulta_estadistica_colegios':
        $pdf_link = $base_files . "mop_educacion.pdf";

        // Datos estadísticos extraídos de la tabla final del PDF
        $stats_colegios = [
            "📊 Total Región: 656 Instituciones Educativas", // [cite: 586]
            "📍 Paucartambo: 106 colegios (Mayor cantidad)", // [cite: 586]
            "📍 Huayllay: 63 colegios", // [cite: 586]
            "📍 Chaupimarca: 62 colegios", // [cite: 586]
            "📍 Huariaca: 35 colegios", // [cite: 586]
            "🎓 Modalidades: Básica, Especial y Tecnológica"
        ];

        $response_array = crearTarjetaInfo(
            "Estadística Educativa",
            "Cobertura de Colegios por Distrito",
            "https://cdn-icons-png.flaticon.com/512/3063/3063823.png", // Icono de gráfico
            $pdf_link,
            "Ver Tabla Completa",
            $stats_colegios
        );
        break;
    // --- NUEVO: CASO O: MCC (Manual de Clasificador de Cargos) ---
    // --- NUEVO: CASO O: MCC (Manual de Clasificador de Cargos - GENERAL) ---
    case 'consulta_mcc_general':
        $pdf_link = $base_files . "mcc_cargos.pdf";


        $detalles_mcc = [
            "📜 Documento: Manual de Clasificador de Cargos (MCC)",
            "⚖️ Norma: Res. Ejecutiva N° 646-2023-G.R.P.",
            "🎯 Contenido: Requisitos mínimos (Estudios y Experiencia) para TODOS los puestos.",
            "👥 Alcance: Personal Nombrado, Contratado y de Confianza.",
            "🔍 Tip: Descarga el PDF para buscar tu carrera o cargo específico."
        ];

        $response_array = crearTarjetaDescarga(
            "Perfiles de Puesto (MCC)",
            "Requisitos para trabajar en el GORE",
            "https://cdn-icons-png.flaticon.com/512/942/942748.png", // Icono de búsqueda de empleo
            $pdf_link,
            $detalles_mcc
        );
        break;

    // --- NUEVO: CASO P: EJEMPLO DE PERFIL (Técnico) ---
    case 'consulta_requisitos_ejemplo':
        $pdf_link = $base_files . "mcc_cargos.pdf";

        // Ejemplo real extraído del PDF (Pág. Final - Técnico Electricista)
        $ejemplo_perfil = [
            "🔧 Cargo: Técnico en Mantenimiento / Electricidad",
            "🎓 Estudios: Secundaria completa o Técnico",
            "⏳ Experiencia General: Dos (02) años",
            "🏢 Experiencia Específica: Un (01) año en sector público",
            "📝 Funciones: Instalación, reparación y mantenimiento"
        ];

        $response_array = crearTarjetaInfo(
            "Ejemplo de Perfil: Técnico",
            "Así se detallan los requisitos en el MCC:",
            "https://cdn-icons-png.flaticon.com/512/3063/3063823.png", // Icono de lista
            $pdf_link,
            "Ver Todos los Cargos",
            $ejemplo_perfil
        );
        break;
    // --- NUEVO: CASO Q: PEI GENERAL (Plan Estratégico) ---
    case 'consulta_pei_general':
        $pdf_link = $base_files . "pei_2025_2030.pdf";

        // Datos extraídos de la Resolución Ejecutiva N° 0684-2024
        $detalles_pei = [
            "📜 Documento: Plan Estratégico Institucional (PEI)",
            "📅 Periodo Ampliado: 2025 - 2030", //
            "⚖️ Norma: Res. Ejecutiva N° 0684-2024-G.R.P.GR", //
            "🎯 Visión: Mejorar la calidad de vida y servicios en Pasco",
            "🏛️ Estado: Instrumento de gestión vigente y aprobado"
        ];

        $response_array = crearTarjetaDescarga(
            "Plan Estratégico (PEI)",
            "Visión Regional al 2030",
            "https://cdn-icons-png.flaticon.com/512/3358/3358964.png", // Icono de estrategia/ajedrez
            $pdf_link,
            $detalles_pei
        );
        break;

    // --- NUEVO: CASO R: OBJETIVOS ESTRATÉGICOS (Prioridades) ---
    case 'consulta_objetivos_pei':
        $pdf_link = $base_files . "pei_2025_2030.pdf";

        // Los OEI principales extraídos de la Matriz del PDF
        $objetivos_pei = [
            "🚑 OEI.02: Mejorar servicios de SALUD integral", //
            "🎓 OEI.03: Mejorar logros de aprendizaje (EDUCACIÓN)", //
            "🚜 OEI.04: Competitividad económica (Agro y Turismo)", //
            "🛣️ OEI.06: Infraestructura Vial (Conectividad)", //
            "🤝 OEI.08: Inclusión Social (Población vulnerable)", //
            "⛈️ OEI.01: Gestión de Riesgo de Desastres" //
        ];

        $response_array = crearTarjetaInfo(
            "Prioridades de Gestión",
            "Objetivos Estratégicos (OEI)",
            "https://cdn-icons-png.flaticon.com/512/825/825590.png", // Icono de meta/objetivo
            $pdf_link,
            "Ver Plan Completo",
            $objetivos_pei
        );
        break;
    // --- NUEVO: CASO S: PDRC 2025-2034 (Plan Concertado) ---
    // --- CASO S: PDRC 2025-2034 (CON DICCIONARIO INTELIGENTE) ---
    case 'consulta_pdrc_2034':
        $pdf_link = $base_files . "pdrc_2034.pdf";
        
        // 1. CAPTURA SEGURA DEL TEMA (Igual que en el TUPA)
        $tema_raw = $parametros['tema_pdrc'] ?? '';
        $tema_especifico = "";
        
        if (is_array($tema_raw)) {
            $tema_especifico = !empty($tema_raw) ? $tema_raw[0] : '';
        } else {
            $tema_especifico = (string)$tema_raw;
        }

        // 2. PERSONALIZACIÓN DE LA RESPUESTA SEGÚN EL TEMA
        // Por defecto (si no dijo nada específico):
        $titulo = "PDRC Pasco al 2034";
        $subtitulo = "Plan de Desarrollo Concertado";
        $puntos_clave = [
            "📅 Horizonte: 10 años (2025 - 2034)",
            "🔭 Visión: Pasco integrado y sostenible.",
            "⚙️ Ejes: Social, Económico, Ambiental e Inst.",
            "✅ Estado: Aprobado con Acta del CCR."
        ];

        // Lógica de "Diccionario"
        if (!empty($tema_especifico)) {
            $tema_normalizado = strtolower($tema_especifico);
            
            if ($tema_normalizado == 'social') {
                $titulo = "PDRC: Eje Social y Humano";
                $subtitulo = "Salud, Educación y Vivienda";
                $puntos_clave = [
                    "🚑 Salud: Reducción de anemia y desnutrición.",
                    "🎓 Educación: Modernización de colegios y currícula.",
                    "🏠 Vivienda: Cierre de brechas en servicios básicos.",
                    "🤝 Inclusión: Atención a poblaciones vulnerables."
                ];
            } 
            elseif ($tema_normalizado == 'economico') {
                $titulo = "PDRC: Desarrollo Económico";
                $subtitulo = "Empleo, Agro y Turismo";
                $puntos_clave = [
                    "🚜 Agro: Tecnificación del campo y riego.",
                    "✈️ Turismo: Poner en valor la Selva Central.",
                    "🏭 Industria: Transformación de materias primas.",
                    "💼 Empleo: Fomento de la inversión privada."
                ];
            }
            elseif ($tema_normalizado == 'ambiental') {
                $titulo = "PDRC: Medio Ambiente";
                $subtitulo = "Sostenibilidad y Recursos";
                $puntos_clave = [
                    "💧 Agua: Gestión integral de recursos hídricos.",
                    "♻️ Residuos: Plantas de tratamiento provinciales.",
                    "🌳 Bosques: Reforestación y control de tala.",
                    "⚠️ Riesgos: Prevención ante desastres naturales."
                ];
            }
            elseif ($tema_normalizado == 'infraestructura') {
                $titulo = "PDRC: Infraestructura Vial";
                $subtitulo = "Conectividad y Obras";
                $puntos_clave = [
                    "🛣️ Vías: Asfaltado de carreteras departamentales.",
                    "bridge Puentes: Interconexión entre distritos.",
                    "⚡ Energía: Electrificación rural al 100%.",
                    "📡 Digital: Banda ancha para toda la región."
                ];
            }
            elseif ($tema_normalizado == 'institucional') {
                $titulo = "PDRC: Gestión Institucional";
                $subtitulo = "Gobernanza y Seguridad";
                $puntos_clave = [
                    "⚖️ Transparencia: Gobierno digital y abierto.",
                    "👮 Seguridad: Fortalecimiento de seguridad ciudadana.",
                    "📉 Conflictos: Gestión y diálogo social preventivo."
                ];
            }
        }

        $response_array = crearTarjetaDescarga(
            $titulo, 
            $subtitulo, 
            "https://cdn-icons-png.flaticon.com/512/3203/3203862.png", 
            $pdf_link,
            $puntos_clave
        );
        break;
    // --- CASO T: TUPA 2024 (Versión Blindada) ---
    // --- CASO T: TUPA 2024 (CORREGIDO Y PROBADO) ---
    case 'consulta_tupa_2024':
        $pdf_link = $base_files . "tupa_2024.pdf"; 
        
        // 1. CAPTURA INTELIGENTE DEL PARÁMETRO
        // Obtenemos lo que manda Dialogflow (puede ser texto o lista)
        $tema_raw = $parametros['concepto_tupa'] ?? '';
        
        $tema_especifico = ""; // Valor inicial vacío
        
        // Verificamos: ¿Es una lista (Array)?
        if (is_array($tema_raw)) {
            // Si es lista, sacamos el primer valor: ["Transporte"] -> "Transporte"
            $tema_especifico = !empty($tema_raw) ? $tema_raw[0] : '';
        } else {
            // Si ya es texto, lo usamos tal cual
            $tema_especifico = (string)$tema_raw;
        }

        // 2. TÍTULOS DINÁMICOS (Ahora sí funcionará)
        if (!empty($tema_especifico)) {
            // Convertimos primera letra a mayúscula
            $tema_format = ucfirst($tema_especifico); 
            $titulo_tarjeta = "TUPA: Trámites de $tema_format";
            $subtitulo_tarjeta = "Requisitos y Costos para $tema_format";
        } else {
            // Título Genérico (Si no detectó palabra clave)
            $titulo_tarjeta = "TUPA GORE Pasco 2024";
            $subtitulo_tarjeta = "Texto Único de Procedimientos Administrativos";
        }
        
        $puntos_tupa = [
            "💰 Costos: Derechos de pago actualizados (UIT 2024).",
            "📋 Requisitos: Documentos exactos para tu expediente.",
            "🏢 Áreas: Transportes, Trabajo, Turismo, Producción y más.",
            "⏳ Plazos: Tiempos de atención y silencio administrativo.",
            "✅ Nota: Revisa el índice del PDF para ubicar tu trámite."
        ];

        $response_array = crearTarjetaDescarga(
            $titulo_tarjeta,
            $subtitulo_tarjeta,
            "https://cdn-icons-png.flaticon.com/512/2910/2910768.png", 
            $pdf_link,
            $puntos_tupa
        );
        break;
    

    // --- NUEVO: CASO REINICIO (Volver al Menú) ---
    // --- CASO MENÚ PRINCIPAL (Inicio y Reinicio) ---
    case 'Default Welcome Intent': // Cuando dicen "Hola"
    case 'navegacion_reiniciar':   // Cuando dicen "Volver al menú"
        
        $texto_bienvenida = "👋 ¡Hola! Soy el Asistente Virtual del GORE Pasco.\n\n" .
                            "Estoy aquí para ayudarte con información oficial 24/7. " .
                            "¿Qué deseas hacer hoy?";
        
        // Menú con las opciones principales + CONTACTO
        $botones_menu = [
            "🔍 Consultar Expediente",
            "📋 Ver TUPA 2024",
            "💰 Sueldo Gobernador",
            "📅 Plan Desarrollo 2034",
            "🚑 Objetivos Salud (PEI)",
            "💬 Hablar con un Humano" // <--- NUEVO BOTÓN
        ];

        $response_array = responderConTextoYBotones($texto_bienvenida, $botones_menu);
        break;
    // CASO DEFAULT: Si no reconocemos el intent
    // --- NUEVO: CASO CONTACTO DIRECTO ---
    case 'contactar_funcionario':
        
        $titulo = "Canales de Atención Ciudadana";
        $subtitulo = "Horario: Lunes a Viernes (8:00am - 5:00pm)";
        
        // Usamos una tarjeta con botones de acción directa
        $response_array = [
            "fulfillmentMessages" => [
                [
                    "payload" => [
                        "richContent" => [
                            [
                                [
                                    "type" => "info",
                                    "title" => $titulo,
                                    "subtitle" => $subtitulo,
                                    "image" => [
                                        "src" => ["rawUrl" => "https://cdn-icons-png.flaticon.com/512/3059/3059502.png"] // Icono de Call Center
                                    ]
                                ],
                                [
                                    "type" => "chips",
                                    "options" => [
                                        [
                                            "text" => "💬 Chatear por WhatsApp",
                                            "link" => $wsp_soporte
                                        ],
                                        [
                                            "text" => "📞 Llamar a Sede Central",
                                            "link" => $fono_central
                                        ],
                                        [
                                            "text" => "🏠 Volver al Menú",
                                            "link" => "" // El link vacío en chips a veces da error en web, mejor manejarlo como texto si es web
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        break;

    // --- MODIFICADO: CASO DEFAULT (FALLBACK INTELIGENTE) ---
    // Esto se activa cuando el bot NO entiende la pregunta
    case 'Default Fallback Intent':
    default:
        
        $texto_error = "🤔 Mmm... no estoy seguro de haber entendido tu consulta, o tal vez esa información no está en mis archivos actuales.\n\n" .
                       "¿Qué prefieres hacer?";

        // Ofrecemos soluciones en lugar de solo decir "Error"
        $botones_ayuda = [
            "💬 Hablar con un Humano", // Esto activará el intent 'contactar_funcionario' si lo entrenas o lo manejas por texto
            "🔍 Consultar Trámite",
            "🏠 Volver al Menú"
        ];

        // NOTA: Para que el botón "Hablar con un Humano" funcione al hacer clic, 
        // debes agregar esa frase en el Training Phrases del intent 'contactar_funcionario'.
        
        $response_array = responderConTextoYBotones($texto_error, $botones_ayuda);
        break;
}

// 4. FUNCIONES AUXILIARES (Generadores de JSON)
// Función MEJORADA: Ahora acepta una lista de detalles (Array)
function crearTarjetaDescarga($titulo, $subtitulo, $img_url, $link, $lista_detalles = [])
{

    // 1. Tarjeta Principal (Título e Imagen)
    $contenido = [
        [
            "type" => "info",
            "title" => $titulo,
            "subtitle" => $subtitulo,
            "image" => ["src" => ["rawUrl" => $img_url]],
            "actionLink" => $link
        ]
    ];

    // 2. Si hay detalles, agregamos la sección de texto (Lista)
    if (!empty($lista_detalles)) {
        $contenido[] = [
            "type" => "description",
            "title" => "📋 Detalles Importantes:", // Título de la lista
            "text" => $lista_detalles
        ];
    }

    // 3. Botón de Descarga (Siempre va al final)
    $contenido[] = [
        "type" => "chips",
        "options" => [
            ["text" => "📄 Descargar PDF Oficial", "link" => $link]
        ]
    ];

    return [
        "fulfillmentMessages" => [
            [
                "payload" => [
                    "richContent" => [$contenido]
                ]
            ]
        ]
    ];
}

// Función CORREGIDA: Ahora sí acepta la lista de detalles
function crearTarjetaInfo($titulo, $subtitulo, $img_url, $link, $boton_texto, $lista_detalles = [])
{
    // 1. Cabecera
    $contenido = [
        [
            "type" => "info",
            "title" => $titulo,
            "subtitle" => $subtitulo,
            "image" => ["src" => ["rawUrl" => $img_url]],
            "actionLink" => $link
        ]
    ];

    // 2. Lista de detalles (Si existe)
    if (!empty($lista_detalles)) {
        $contenido[] = [
            "type" => "description",
            "title" => "📌 Detalles / Datos:", // <--- CAMBIO AQUÍ: Título genérico para que sirva para todo
            "text" => $lista_detalles
        ];
    }

    // 3. Botón Personalizado
    $contenido[] = [
        "type" => "chips",
        "options" => [
            ["text" => "📄 " . $boton_texto, "link" => $link]
        ]
    ];

    return [
        "fulfillmentMessages" => [
            [
                "payload" => [
                    "richContent" => [$contenido]
                ]
            ]
        ]
    ];
}
// Función para enviar Texto Simple + Botones (Chips)
function responderConTextoYBotones($texto, $botones = []) {
    // Estructura básica de respuesta
    $respuesta = [
        "fulfillmentMessages" => [
            [
                "text" => [
                    "text" => [$texto]
                ]
            ]
        ]
    ];

    // Si hay botones, los agregamos como "Suggestions"
    if (!empty($botones)) {
        $suggestions = [];
        foreach ($botones as $btn) {
            $suggestions[] = ["title" => $btn];
        }

        $respuesta["fulfillmentMessages"][] = [
            "payload" => [
                "richContent" => [
                    [
                        [
                            "type" => "chips",
                            "options" => array_map(function($txt) { return ["text" => $txt]; }, $botones)
                        ]
                    ]
                ]
            ]
        ];
    }
    
    return $respuesta;
}
// 5. ENVIAR RESPUESTA FINAL
echo json_encode($response_array);
