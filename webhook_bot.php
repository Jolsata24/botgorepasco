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
            "📈 Plan Estratégico Institucional (PEI)", 
            "⚙️ MOP - Oxapampa", 
            "🎓 MOP - Educación", 	
            "🎓 CAP - 2014",
            "🏠 Volver al Inicio"
        ];
        $response_array = responderConTextoYBotones($texto, $botones_instrumentos);
        break;

    // --- TUPA 2024 ---
    case 'consulta_tupa_2024':
        $pdf_link = $base_files . "tupa_2024.pdf";
        $tema_raw = $parametros['concepto_tupa'] ?? '';
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        
        $tema_low = mb_strtolower($tema_especifico, 'UTF-8');

        if (!empty($tema_especifico)) {
            $tema_format = ucfirst($tema_especifico);
            $titulo = "TUPA: $tema_format (2024)";
            $subtitulo = "Requisitos y Costos Oficiales";

            if (strpos($tema_low, 'transport') !== false || strpos($tema_low, 'licencia') !== false || strpos($tema_low, 'vehicu') !== false) {
                $puntos = [
                    "🚗 Trámites: Licencias (A-I a A-III), Canjes y Revalidación.",
                    "💰 Costos: Licencias Clase A desde S/ 9.50 (UIT 2024).",
                    "🏦 Pago: Banco de la Nación Cta. 00-501-040592.",
                    "📍 Sede: Dirección Regional de Transportes (Área Circulación)."
                ];
            } elseif (strpos($tema_low, 'turismo') !== false || strpos($tema_low, 'dircetur') !== false || strpos($tema_low, 'hotel') !== false) {
                $puntos = [
                    "✈️ Sector: DIRCETUR (15 procedimientos).",
                    "🏨 Trámites: Clasificación Hoteles, Agencias y Canotaje.",
                    "🏦 Cuenta Exclusiva Turismo: Bco. Nación 00-501-085383.",
                    "📝 Nota: Costos variables según UIT 2024 (S/ 5,150)."
                ];
            } elseif (strpos($tema_low, 'agri') !== false || strpos($tema_low, 'tierras') !== false || strpos($tema_low, 'forest') !== false) {
                $puntos = [
                    "🌾 Agricultura: 161 trámites (Sector más grande).",
                    "🌲 Gestión: Titulación de tierras y permisos forestales.",
                    "💰 Base Legal: Derechos calculados sobre UIT 2024.",
                    "📂 Atención: Dirección Regional de Agricultura."
                ];
            } elseif (strpos($tema_low, 'minas') !== false || strpos($tema_low, 'energ') !== false || strpos($tema_low, 'elect') !== false) {
                $puntos = [
                    "⛏️ Energía y Minas: 75 procedimientos activos.",
                    "⚡ Temas: Concesiones eléctricas, mineras e IGAFOM.",
                    "💰 Pagos: Banco de la Nación Cta. 00-501-040592.",
                    "🏢 Sede: Edificio Estatal N° 03 (San Juan Pampa)."
                ];
            } elseif (strpos($tema_low, 'trabajo') !== false || strpos($tema_low, 'labor') !== false || strpos($tema_low, 'empleo') !== false) {
                $puntos = [
                    "👷 Trabajo: 40 trámites (DRTPE Pasco).",
                    "📋 Gestión: Reg. Construcción Civil (RETCC) y Sindicatos.",
                    "⚖️ Servicios: Conciliaciones y liquidaciones laborales.",
                    "🆓 Nota: Muchos servicios de patrocinio son gratuitos."
                ];
            } else {
                $puntos = [
                    "📄 Información: Procedimientos vigentes para $tema_format.",
                    "💰 Base Cálculo: Unidad Impositiva Tributaria (UIT) 2024.",
                    "🏦 Banco General: Banco de la Nación Cta. 00-501-040592.",
                    "🏢 Entidad: Gobierno Regional de Pasco (Pliego 456)."
                ];
            }

        } else {
            $titulo = "TUPA GORE Pasco 2024";
            $subtitulo = "Texto Único de Procedimientos Administrativos";
            $puntos = [
                "📊 Contenido: 434 trámites de 14 sectores regionales.",
                "💰 Valor UIT 2024: S/ 5,150.00 (Base de cálculo).",
                "🏦 Cta. General: Banco de la Nación 00-501-040592.",
                "🏆 Top Sectores: Agricultura (161), Minas (75), Trabajo (40)."
            ];
        }

        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/2910/2910768.png", $pdf_link, $puntos);
        break;

    // --- PDRC 2034 ---
    case 'consulta_pdrc_2034':
        $pdf_link = $base_files . "pdrc_2034.pdf";
        $tema_raw = $parametros['tema_pdrc'] ?? '';
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $tema_norm = mb_strtolower($tema_especifico, 'UTF-8');

        if (!empty($tema_especifico)) {
            if (strpos($tema_norm, 'social') !== false || strpos($tema_norm, 'salud') !== false || strpos($tema_norm, 'educa') !== false) {
                $titulo = "PDRC: Eje Social (OER 1, 2 y 3)";
                $subtitulo = "Salud, Educación y Habitabilidad";
                $puntos = [
                    "🏥 Salud (OER 3): Reducir anemia (<28%) y desnutrición (<6.9%) al 2034.",
                    "🎓 Educación (OER 2): Lograr 53% comprensión lectora en secundaria.",
                    "🏠 Vivienda (OER 1): Cobertura de agua (>82%) y saneamiento (>70%).",
                    "🎯 Meta: Cerrar brechas de servicios básicos al 100%."
                ];
            } elseif (strpos($tema_norm, 'econom') !== false || strpos($tema_norm, 'agro') !== false || strpos($tema_norm, 'turi') !== false || strpos($tema_norm, 'produc') !== false) {
                $titulo = "PDRC: Desarrollo Económico (OER 5 y 6)";
                $subtitulo = "Competitividad y Producción";
                $puntos = [
                    "🚜 Agro (OER 5): Elevar PBI agrícola al 18% (Riego y tecnificación).",
                    "💼 Empleo (OER 6): Formalización laboral al 55% y empresarial al 61%.",
                    "✈️ Turismo: Meta de recibir >625,000 visitantes anuales al 2034.",
                    "📈 Crecimiento: Tasa de variación del Valor Agregado Bruto del 9.9%."
                ];
            } elseif (strpos($tema_norm, 'ambient') !== false || strpos($tema_norm, 'agua') !== false || strpos($tema_norm, 'eco') !== false) {
                $titulo = "PDRC: Eje Ambiental (OER 4)";
                $subtitulo = "Sostenibilidad y Recursos Naturales";
                $puntos = [
                    "💧 Calidad Agua: 75% de plantas de tratamiento cumpliendo normas.",
                    "🌲 Bosques: Reducir deforestación y restaurar ecosistemas degradados.",
                    "♻️ Residuos: Gestión integral de residuos sólidos y pasivos mineros.",
                    "🌍 Enfoque: Conservación de ecosistemas frágiles y cabeceras de cuenca."
                ];
            } elseif (strpos($tema_norm, 'infra') !== false || strpos($tema_norm, 'vial') !== false || strpos($tema_norm, 'conect') !== false) {
                $titulo = "PDRC: Conectividad e Infraestructura (OER 7)";
                $subtitulo = "Integración Territorial";
                $puntos = [
                    "🛣️ Vías (OER 7): Pavimentación del 73% de la red vial departamental.",
                    "📡 Internet: 75% de hogares con acceso a internet fijo/móvil.",
                    "🚚 Corredores: Consolidación de 5 corredores económicos regionales.",
                    "⚠️ Riesgos (OER 8): Reducción de vulnerabilidad ante desastres (5.3%)."
                ];
            } else {
                $titulo = "PDRC Pasco 2034: $tema_especifico";
                $subtitulo = "Objetivos Estratégicos";
                $puntos = [
                    "📅 Vigencia: Plan actualizado para el horizonte 2025 - 2034.",
                    "🎯 Estructura: 8 Objetivos Estratégicos Regionales (OER).",
                    "🤝 Enfoque: Desarrollo concertado, inclusivo y sostenible.",
                    "📥 Descarga: Revisa el PDF para ver indicadores específicos."
                ];
            }

        } else {
            $titulo = "PDRC Pasco al 2034"; 
            $subtitulo = "Plan de Desarrollo Regional Concertado";
            $puntos = [
                "📅 Horizonte: 2025 - 2034 (10 años de planificación).",
                "🔭 Visión: Pasco integrado, competitivo y sostenible.",
                "⚙️ 4 Ejes: Social, Económico, Ambiental e Institucional.",
                "📊 Metas: 8 Objetivos Estratégicos y múltiples Acciones (AER)."
            ];
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/3203/3203862.png", $pdf_link, $puntos);
        break;
    
    // --- PAP 2024 ---
    case 'consulta_presupuesto_personal':
        $pdf_link = $base_files . "pap_2024.pdf";
        $tema_raw = $parametros['area_pap'] ?? ''; 
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $tema_norm = mb_strtolower($tema_especifico, 'UTF-8');

        $resolucion = "R.E.R. N° 240-2024-G.R.P./GOB";
        $fecha_aprob = "10 de Junio 2024";
        
        if (!empty($tema_especifico)) {
            if (strpos($tema_norm, 'vivienda') !== false || strpos($tema_norm, 'saneamiento') !== false) {
                $titulo = "PAP 2024: Dirección de Vivienda";
                $subtitulo = "Plazas y Presupuesto - Const. y Saneamiento";
                $puntos = [
                    "👔 Plazas: Incluye Director (F5), Ingenieros (SPA) y Técnicos.",
                    "💰 Costo Anual Ref: S/ 35,041.04 (Ej. Director Sistema Admin).",
                    "📋 Estado: Plazas previstas para el año fiscal 2024.",
                    "⚖️ Base Legal: $resolucion."
                ];
            } elseif (strpos($tema_norm, 'trabajo') !== false || strpos($tema_norm, 'empleo') !== false) {
                $titulo = "PAP 2024: Dirección de Trabajo";
                $subtitulo = "Plazas DRTPE Pasco";
                $puntos = [
                    "👔 Plazas: Técnicos Administrativos (STD/STE) y Profesionales.",
                    "💵 Remuneraciones: Escala según niveles (SPA, STB, SAC).",
                    "📊 Cobertura: Personal nombrado y contratado permanente.",
                    "📅 Aprobación: $fecha_aprob."
                ];
            } elseif (strpos($tema_norm, 'sede') !== false || strpos($tema_norm, 'central') !== false) {
                $titulo = "PAP 2024: Sede Central GORE";
                $subtitulo = "Pliego 456 - Administración Central";
                $puntos = [
                    "🏢 Alcance: Gerencias Regionales y oficinas de apoyo.",
                    "👥 Personal: Funcionarios de Confianza, Directivos y Servidores.",
                    "💰 Presupuesto Global: S/ 6,218,287.64 (Anualizado).",
                    "📜 Documento: $resolucion."
                ];
            } else {
                $titulo = "PAP 2024: $tema_especifico";
                $subtitulo = "Presupuesto de Personal";
                $puntos = [
                    "📄 Detalle: Consulta el PDF para ver las plazas de esta área.",
                    "📅 Fecha de Corte: $fecha_aprob.",
                    "⚖️ Resolución: $resolucion.",
                    "📂 Clasificación: Funcionarios, Profesionales, Técnicos y Auxiliares."
                ];
            }
        } else {
            $titulo = "PAP GORE Pasco 2024";
            $subtitulo = "Presupuesto Analítico de Personal";
            $puntos = [
                "⚖️ Aprobación: $resolucion ($fecha_aprob).",
                "💰 Presupuesto Total: S/ 6,218,287.64 (Costo Anualizado).",
                "🏢 Alcance: Sede Central, Agricultura, Transportes, Vivienda, etc.",
                "👥 Contenido: Relación de plazas (Nombrados y Contratados)."
            ];
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/3135/3135679.png", $pdf_link, $puntos);
        break;

    // --- ROF 2025 ---
    case 'consulta_rof_general':
        $pdf_link = $base_files . "rof_2025.pdf";
        $tema_raw = $parametros['area_rof'] ?? ''; 
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $tema_norm = mb_strtolower($tema_especifico, 'UTF-8');

        $ordenanza = "Ordenanza Regional Nº 535-2025-G.R.P/CR";
        $fecha_pub = "07 de Noviembre 2025";

        if (!empty($tema_especifico)) {
            if (strpos($tema_norm, 'consejo') !== false || strpos($tema_norm, 'fiscal') !== false) {
                $titulo = "ROF: Consejo Regional";
                $subtitulo = "Órgano Normativo y Fiscalizador";
                $puntos = [
                    "⚖️ Función: Aprobar normas, PDRC y Presupuesto Participativo.",
                    "🔍 Atribución: Fiscalizar la gestión y dictar Ordenanzas.",
                    "👥 Composición: Consejeros representantes de las provincias.",
                    "🏛️ Nivel: Primer nivel organizacional (Alta Dirección)."
                ];
            } 
            elseif (strpos($tema_norm, 'gobernacion') !== false || strpos($tema_norm, 'gobernador') !== false) {
                $titulo = "ROF: Gobernación Regional";
                $subtitulo = "Órgano Ejecutivo";
                $puntos = [
                    "👔 Rol: Dirigir la marcha del Gobierno Regional y sus gerencias.",
                    "🖊️ Facultad: Dictar Decretos y Resoluciones Regionales.",
                    "🤝 Gestión: Suscribir convenios y contratos de obras/servicios.",
                    "💼 Designación: Nombra a Gerentes y funcionarios de confianza."
                ];
            }
            elseif (strpos($tema_norm, 'econom') !== false || strpos($tema_norm, 'agro') !== false || strpos($tema_norm, 'min') !== false || strpos($tema_norm, 'turi') !== false) {
                $titulo = "ROF: G.R. Desarrollo Económico";
                $subtitulo = "Gerencia de Línea (Art. 07.1)";
                $puntos = [
                    "🚜 Sectores: Agricultura, Energía y Minas, Turismo (DIRCETUR), Producción.",
                    "📈 Misión: Promover inversión privada, competitividad y exportación.",
                    "⚙️ Subgerencias: Inversión Privada y Competitividad Productiva.",
                    "🎯 Objetivo: Crecimiento de sectores productivos regionales."
                ];
            }
            elseif (strpos($tema_norm, 'social') !== false || strpos($tema_norm, 'educa') !== false || strpos($tema_norm, 'salud') !== false || strpos($tema_norm, 'vivien') !== false) {
                $titulo = "ROF: G.R. Desarrollo Social";
                $subtitulo = "Gerencia de Línea (Art. 07.2)";
                $puntos = [
                    "🏥 Sectores: Educación (DRE), Salud (DIRESA), Vivienda, Trabajo.",
                    "🤝 Enfoque: Inclusión social, poblaciones vulnerables e identidad.",
                    "🏘️ Meta: Cerrar brechas de servicios básicos y saneamiento.",
                    "🎓 Gestión: Supervisar servicios educativos y sanitarios."
                ];
            }
            elseif (strpos($tema_norm, 'infra') !== false || strpos($tema_norm, 'obra') !== false || strpos($tema_norm, 'vial') !== false || strpos($tema_norm, 'transp') !== false) {
                $titulo = "ROF: G.R. Infraestructura";
                $subtitulo = "Gerencia de Línea (Art. 07.3)";
                $puntos = [
                    "🏗️ Áreas: Estudios, Obras, Supervisión y Liquidación.",
                    "🛣️ Sector: Incluye a la Dirección Regional de Transportes (DRTC).",
                    "🚜 Función: Ejecución de proyectos de inversión pública y vialidad.",
                    "📋 Control: Supervisión técnica de obras y maquinaria pesada."
                ];
            }
            elseif (strpos($tema_norm, 'ambiente') !== false || strpos($tema_norm, 'natural') !== false || strpos($tema_norm, 'riesgo') !== false) {
                $titulo = "ROF: RR.NN. y Medio Ambiente";
                $subtitulo = "Gestión Ambiental y Riesgos";
                $puntos = [
                    "🌲 Gerencia RR.NN.: Ordenamiento territorial y calidad ambiental.",
                    "⚠️ Gerencia Riesgos: Gestión del Riesgo de Desastres (Art. 07.5).",
                    "🌍 Misión: Conservación de ecosistemas y prevención de desastres.",
                    "📜 Base: Cumplimiento de normas ambientales nacionales."
                ];
            }
            else {
                $titulo = "ROF 2025: $tema_especifico";
                $subtitulo = "Estructura Orgánica";
                $puntos = [
                    "📜 Marco Legal: $ordenanza.",
                    "📂 Ubicación: Consulta el índice para ver funciones específicas.",
                    "🏛️ Entidad: Gobierno Regional de Pasco.",
                    "📅 Vigencia: Documento activo desde Noviembre 2025."
                ];
            }
        } else {
            $titulo = "ROF Institucional 2025";
            $subtitulo = "Reglamento de Organización y Funciones";
            $puntos = [
                "⚖️ Aprobación: $ordenanza ($fecha_pub).",
                "🏛️ Estructura: Alta Dirección, 5 Gerencias de Línea y 2 Subregionales.",
                "📍 Desconcentrados: G.S.R. Oxapampa y Daniel Alcides Carrión.",
                "✅ Estado: Vigente y alineado a la modernización pública."
            ];
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/2666/2666505.png", $pdf_link, $puntos);
        break;

    // --- MOP Educación 2025 ---
    case 'consulta_mop_educacion':
        $pdf_link = $base_files . "mop_educacion.pdf";
        $tema_raw = $parametros['tema_educacion'] ?? ''; 
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $tema_norm = mb_strtolower($tema_especifico, 'UTF-8');

        $decreto = "Decreto Regional N° 005-2025-G.R.P./GOB";
        $fecha_aprob = "30 de Septiembre 2025";

        if (!empty($tema_especifico)) {
            if (strpos($tema_norm, 'dre') !== false || strpos($tema_norm, 'direccion') !== false) {
                $titulo = "MOP: DRE Pasco";
                $subtitulo = "Dirección Regional de Educación";
                $puntos = [
                    "🏛️ Rol: Órgano especializado del GORE Pasco.",
                    "📜 Función: Normar y supervisar la política educativa regional.",
                    "⚙️ Gestión: Dirección de Gestión Pedagógica e Institucional.",
                    "⚖️ Base: $decreto."
                ];
            } 
            elseif (strpos($tema_norm, 'ugel') !== false || strpos($tema_norm, 'unidad') !== false) {
                $titulo = "MOP: UGELs (Unidades de Gestión)";
                $subtitulo = "Ámbito Operativo";
                $puntos = [
                    "🏫 Alcance: Pasco, Daniel Alcides Carrión y Oxapampa.",
                    "🎓 Misión: Soporte pedagógico y administrativo a II.EE.",
                    "📋 Autonomía: Órganos desconcentrados con presupuesto propio.",
                    "✅ Estado: Estructura actualizada al 2025."
                ];
            }
            elseif (strpos($tema_norm, 'estadistica') !== false || strpos($tema_norm, 'cobertura') !== false || strpos($tema_norm, 'distrito') !== false) {
                $titulo = "MOP: Cobertura de Servicios";
                $subtitulo = "Estadísticas por Distrito (Pasco)";
                $puntos = [
                    "📊 Chaupimarca: 62 servicios (Básica, EBA, CETPRO).",
                    "📊 Paucartambo: 106 servicios educativos registrados.",
                    "📊 Huayllay: 63 servicios en diversos niveles.",
                    "📈 Modalidades: Incluye Básica Especial (EBE) y Alternativa (EBA)."
                ];
            }
            else {
                $titulo = "MOP Educación 2025: $tema_especifico";
                $subtitulo = "Manual de Operaciones";
                $puntos = [
                    "📂 Contenido: Estructura y funciones de la DRE y UGELs.",
                    "📅 Aprobación: $fecha_aprob.",
                    "⚖️ Norma: $decreto.",
                    "📥 Detalle: Revisa el PDF para funciones específicas."
                ];
            }

        } else {
            $titulo = "MOP Sector Educación 2025";
            $subtitulo = "Manual de Operaciones DRE/UGEL";
            $puntos = [
                "⚖️ Norma Aprobatoria: $decreto ($fecha_aprob).",
                "🏛️ Entidad: Dirección Regional de Educación (DRE) Pasco.",
                "🏫 Alcance: Gestión Pedagógica, Institucional y UGELs.",
                "📊 Data: Incluye inventario de servicios por distrito (Chaupimarca, etc.)."
            ];
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/3135/3135679.png", $pdf_link, $puntos);
        break;

    // --- PEI 2025-2030 ---
    case 'consulta_pei_general':
        $pdf_link = $base_files . "pei_2025_2030.pdf";
        $tema_raw = $parametros['tema_pei'] ?? ''; 
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $tema_norm = mb_strtolower($tema_especifico, 'UTF-8');

        $resolucion = "R.E.R. N° 328-2025-G.R.P./GOB";
        $vigencia = "Periodo 2025 - 2030";

        if (!empty($tema_especifico)) {
            if (strpos($tema_norm, 'social') !== false || strpos($tema_norm, 'salud') !== false || strpos($tema_norm, 'educa') !== false) {
                $titulo = "PEI: Eje Desarrollo Social";
                $subtitulo = "Objetivos Institucionales (OEI)";
                $puntos = [
                    "🏥 Salud: Mejorar la capacidad resolutiva de servicios (OEI Prioritario).",
                    "🎓 Educación: Cerrar brechas de infraestructura y aprendizaje.",
                    "🤝 Enfoque: Atención a poblaciones vulnerables y reducción de anemia.",
                    "📉 Indicador: Reducción de tasas de morbilidad y deserción escolar."
                ];
            }
            elseif (strpos($tema_norm, 'econom') !== false || strpos($tema_norm, 'agro') !== false || strpos($tema_norm, 'turi') !== false) {
                $titulo = "PEI: Eje Económico";
                $subtitulo = "Competitividad y Empleo";
                $puntos = [
                    "🚜 Agro: Impulso a cadenas productivas y seguridad alimentaria.",
                    "💼 Turismo: Puesta en valor de recursos turísticos regionales.",
                    "🏗️ Inversión: Ejecución eficiente de proyectos productivos.",
                    "📈 Meta: Incrementar el PBI regional y formalización laboral."
                ];
            }
            elseif (strpos($tema_norm, 'institucional') !== false || strpos($tema_norm, 'modern') !== false || strpos($tema_norm, 'gestion') !== false) {
                $titulo = "PEI: Fortalecimiento Institucional";
                $subtitulo = "Modernización de la Gestión";
                $puntos = [
                    "🏛️ OEI: Modernizar la gestión pública regional.",
                    "💻 Digital: Implementación de Gobierno Digital y Cero Papel.",
                    "👥 RR.HH.: Fortalecimiento de capacidades del servicio civil.",
                    "🛡️ Integridad: Lucha contra la corrupción y transparencia."
                ];
            }
            elseif (strpos($tema_norm, 'infra') !== false || strpos($tema_norm, 'vial') !== false) {
                $titulo = "PEI: Infraestructura Regional";
                $subtitulo = "Cierre de Brechas Físicas";
                $puntos = [
                    "🛣️ Vías: Mejoramiento de la red vial departamental.",
                    "⚡ Energía: Ampliación de cobertura de electrificación rural.",
                    "💧 Saneamiento: Proyectos de agua y desagüe sostenibles.",
                    "🏗️ Obras: Ejecución de cartera de inversiones priorizada."
                ];
            }
            else {
                $titulo = "PEI 2025-2030: $tema_especifico";
                $subtitulo = "Objetivo Estratégico";
                $puntos = [
                    "🎯 Definición: Acción estratégica para $tema_especifico.",
                    "📅 Horizonte: Metas programadas hasta el 2030.",
                    "⚖️ Base: $resolucion.",
                    "📂 Detalle: Revisa las matrices del PDF para indicadores."
                ];
            }

        } else {
            $titulo = "PEI Pasco 2025-2030";
            $subtitulo = "Plan Estratégico Institucional";
            $puntos = [
                "⚖️ Aprobación: $resolucion (Junio 2025).",
                "🎯 Visión: Pasco integrado, competitivo y con calidad de vida.",
                "📅 Alcance: Hoja de ruta institucional para los próximos 5 años.",
                "⚙️ Contenido: Objetivos (OEI) y Acciones Estratégicas (AEI)."
            ];
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/3358/3358964.png", $pdf_link, $puntos);
        break;

    // --- MCC (Manual de Clasificador de Cargos) ---
    case 'consulta_mcc_general':
        $pdf_link = $base_files . "mcc_cargos.pdf";
        $tema_raw = $parametros['cargo_mcc'] ?? ''; 
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $tema_norm = mb_strtolower($tema_especifico, 'UTF-8');

        $resolucion = "R.E.R. N° 646-2023-G.R.P./GOB";
        $fecha_aprob = "03 de Noviembre 2023";

        if (!empty($tema_especifico)) {
            if (strpos($tema_norm, 'directivo') !== false || strpos($tema_norm, 'gerente') !== false || strpos($tema_norm, 'confianza') !== false) {
                $titulo = "MCC: Directivos y Confianza";
                $subtitulo = "Requisitos Nivel Ejecutivo";
                $puntos = [
                    "👔 Clasificación: Empleado de Confianza (EC) o Directivo Superior (SP-DS).",
                    "🎓 Educación: Título Profesional y/o Grado Académico (según área).",
                    "💼 Experiencia: Generalmente >3 años (con exp. en gestión pública).",
                    "⚖️ Base Legal: $resolucion."
                ];
            } 
            elseif (strpos($tema_norm, 'profesional') !== false || strpos($tema_norm, 'especialista') !== false || strpos($tema_norm, 'ingeniero') !== false || strpos($tema_norm, 'analista') !== false) {
                $titulo = "MCC: Profesionales (SP-ES)";
                $subtitulo = "Especialistas y Analistas";
                $puntos = [
                    "🎓 Requisito: Título Profesional Universitario y Colegiatura.",
                    "💼 Experiencia: General min. 2 años / Específica 1 año (Sector Público).",
                    "🧠 Competencias: Análisis, redacción técnica y manejo de sistemas.",
                    "📂 Categoría: Servidor Público - Especialista (SP-ES)."
                ];
            }
            elseif (strpos($tema_norm, 'tecnico') !== false || strpos($tema_norm, 'asistente') !== false || strpos($tema_norm, 'auxiliar') !== false || strpos($tema_norm, 'secretaria') !== false) {
                $titulo = "MCC: Técnicos y Auxiliares";
                $subtitulo = "Personal de Apoyo (SP-AP)";
                $puntos = [
                    "🔧 Requisito: Secundaria Completa o Título Técnico (Instituto).",
                    "🛠️ Ejemplo (Electricidad): 2 años exp. general / 1 año específica.",
                    "📂 Funciones: Soporte administrativo, operativo o mantenimiento.",
                    "📋 Habilidades: Ofimática básica y trabajo en equipo."
                ];
            }
            else {
                $titulo = "Perfil: $tema_especifico";
                $subtitulo = "Consultar Requisitos en MCC";
                $puntos = [
                    "📄 Detalle: Busca '$tema_especifico' en el índice del PDF.",
                    "⚖️ Normativa: $resolucion.",
                    "🎯 Contenido: Funciones específicas, formación y experiencia requerida.",
                    "📅 Vigencia: Documento activo (Gestión 2023-2026)."
                ];
            }
        } else {
            $titulo = "MCC GORE Pasco 2023-2026";
            $subtitulo = "Manual de Clasificador de Cargos";
            $puntos = [
                "⚖️ Aprobación: $resolucion ($fecha_aprob).",
                "🎯 Objetivo: Estándares para contratación de personal (CAS/Nombrados).",
                "📊 Grupos: Funcionarios, Directivos, Profesionales, Técnicos y Auxiliares.",
                "✅ Uso: Base para concursos públicos y convocatorias CAS."
            ];
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/942/942748.png", $pdf_link, $puntos);
        break;

    // --- CAP (Cuadro para Asignación de Personal) ---
    case 'consulta_cap_general':
        $pdf_link = $base_files . "CAP_2014(Ord-344-2014).pdf";
        $tema_raw = $parametros['area_cap'] ?? ''; 
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $tema_norm = mb_strtolower($tema_especifico, 'UTF-8');

        $ordenanza = "Ordenanza Regional N° 344-2014-G.R.PASCO/CR";
        $fecha_doc = "10 de Abril 2014";

        if (!empty($tema_especifico)) {
            if (strpos($tema_norm, 'infraestructura') !== false || strpos($tema_norm, 'obras') !== false || strpos($tema_norm, 'estudios') !== false) {
                $titulo = "CAP: Gerencia de Infraestructura";
                $subtitulo = "Plazas Estructurales";
                $puntos = [
                    "🏗️ S.G. Obras y Equipo Mecánico: 9 Plazas (Incluye Especialistas).",
                    "📐 S.G. Estudios: 6 Plazas asignadas.",
                    "👷 Supervisión: S.G. Supervisión de Obras (7 Plazas).",
                    "📋 Liquidación: S.G. Liquidación y Transferencia (6 Plazas)."
                ];
            } 
            elseif (strpos($tema_norm, 'social') !== false || strpos($tema_norm, 'oredis') !== false || strpos($tema_norm, 'discapacidad') !== false) {
                $titulo = "CAP: Desarrollo Social";
                $subtitulo = "Inclusión y Poblaciones Vulnerables";
                $puntos = [
                    "♿ OREDIS: 3 Plazas (Atención a Personas con Discapacidad).",
                    "🤝 Asuntos Andinos/Amazónicos: 5 Plazas.",
                    "🏛️ Gerencia Regional: 2 Plazas (Gerente + Asistente).",
                    "⚖️ Base: Documento de Gestión Institucional."
                ];
            }
            elseif (strpos($tema_norm, 'sede') !== false || strpos($tema_norm, 'central') !== false) {
                $titulo = "CAP: Sede Central";
                $subtitulo = "Resumen Global";
                $puntos = [
                    "🏢 Alcance: Personal de la Sede del Gobierno Regional.",
                    "📊 Clasificación: FP (Funcionarios), EC (Confianza), SP (Servidores).",
                    "📝 Estado: Plazas previstas y ocupadas según Ordenanza.",
                    "📅 Documento Base: $fecha_doc."
                ];
            }
            else {
                $titulo = "CAP GORE Pasco: $tema_especifico";
                $subtitulo = "Consulta de Plazas";
                $puntos = [
                    "📄 Detalle: Revisa el cuadro adjunto para ver la asignación.",
                    "⚖️ Normativa: $ordenanza.",
                    "👥 Categorías: Directivos (SP-DS), Especialistas (SP-ES), Apoyo (SP-AP).",
                    "📥 Descarga: PDF completo disponible."
                ];
            }
        } else {
            $titulo = "CAP GORE Pasco (Vigente)";
            $subtitulo = "Cuadro para Asignación de Personal";
            $puntos = [
                "⚖️ Aprobación: $ordenanza ($fecha_doc).",
                "🏛️ Entidad: Gobierno Regional Pasco - Sede Central.",
                "📋 Contenido: Relación de cargos definidos y estructurados.",
                "🔍 Códigos: Define plazas de Confianza (EC) y Carrera."
            ];
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/1256/1256650.png", $pdf_link, $puntos);
        break;

    // --- MOP Oxapampa ---
    case 'consulta_mop_oxapampa':
        $pdf_link = $base_files . "mop_oxapampa.pdf";
        $tema_raw = $parametros['area_mop_oxa'] ?? ''; 
        $tema_especifico = is_array($tema_raw) ? (!empty($tema_raw) ? $tema_raw[0] : '') : (string)$tema_raw;
        $tema_norm = mb_strtolower($tema_especifico, 'UTF-8');

        $decreto = "Decreto Regional N° 003-2023-G.R.P./GOB";
        $fecha_aprob = "07 de Agosto 2023";
        $entidad = "Gerencia Sub Regional Oxapampa (Unidad Ejecutora)";

        if (!empty($tema_especifico)) {
            if (strpos($tema_norm, 'infraestructura') !== false || strpos($tema_norm, 'obras') !== false || strpos($tema_norm, 'estudios') !== false) {
                $titulo = "MOP Oxapampa: Infraestructura";
                $subtitulo = "Dirección de Línea";
                $puntos = [
                    "🏗️ Función: Ejecución y supervisión de obras en la provincia.",
                    "📐 Gestión: Elaboración de expedientes técnicos y perfiles.",
                    "🚜 Maquinaria: Administración del pool de maquinaria pesada.",
                    "✅ Meta: Cierre de brechas físicas en la Selva Central."
                ];
            } 
            elseif (strpos($tema_norm, 'econom') !== false || strpos($tema_norm, 'agro') !== false || strpos($tema_norm, 'turismo') !== false || strpos($tema_norm, 'selva') !== false) {
                $titulo = "MOP Oxapampa: Desarrollo Económico";
                $subtitulo = "Agro y Turismo";
                $puntos = [
                    "☕ Sectores: Fomento a cadenas productivas (Café, Cacao, Granadilla).",
                    "✈️ Turismo: Promoción de la Reserva de Biósfera y circuitos turísticos.",
                    "🤝 Proyectos: Apoyo a productores locales y comunidades nativas.",
                    "📈 Objetivo: Dinamizar la economía de la provincia."
                ];
            }
            elseif (strpos($tema_norm, 'admin') !== false || strpos($tema_norm, 'logistica') !== false || strpos($tema_norm, 'personal') !== false) {
                $titulo = "MOP Oxapampa: Administración";
                $subtitulo = "Órgano de Apoyo";
                $puntos = [
                    "🏢 Gestión: Recursos Humanos, Logística y Contabilidad.",
                    "💰 Tesorería: Ejecución financiera de la Unidad Ejecutora.",
                    "📋 Bienes: Control patrimonial de la sede subregional.",
                    "⚖️ Base: Normas del Sistema Administrativo de Gestión Pública."
                ];
            }
            elseif (strpos($tema_norm, 'asesoria') !== false || strpos($tema_norm, 'legal') !== false || strpos($tema_norm, 'juridica') !== false) {
                $titulo = "MOP Oxapampa: Asesoría Jurídica";
                $subtitulo = "Órgano de Asesoramiento";
                $puntos = [
                    "⚖️ Rol: Emitir opinión legal sobre actos administrativos.",
                    "🛡️ Defensa: Asesorar en convenios y defensa de la entidad.",
                    "📜 Normativa: Interpretación legal del Decreto $decreto.",
                    "🤝 Apoyo: Soporte al Gerente Sub Regional."
                ];
            }
            else {
                $titulo = "MOP Oxapampa: $tema_especifico";
                $subtitulo = "Consulta de Funciones";
                $puntos = [
                    "📄 Detalle: Revisa el manual para funciones específicas.",
                    "🏛️ Entidad: $entidad.",
                    "📅 Vigencia: Documento activo desde Agosto 2023.",
                    "📍 Alcance: Provincia de Oxapampa y distritos."
                ];
            }
        } else {
            $titulo = "MOP G.S.R. Oxapampa";
            $subtitulo = "Manual de Operaciones 2023";
            $puntos = [
                "⚖️ Aprobación: $decreto ($fecha_aprob).",
                "🏛️ Naturaleza: Unidad Ejecutora Desconcentrada del GORE Pasco.",
                "⚙️ Estructura: Gerencia, Administración, Infraestructura y Desarrollo Económico.",
                "📍 Sede: Oxapampa (Selva Central)."
            ];
        }
        $response_array = crearTarjetaDescarga($titulo, $subtitulo, "https://cdn-icons-png.flaticon.com/512/4300/4300540.png", $pdf_link, $puntos);
        break;

    // --- RESOLUCIONES (TUTORIAL CON IMAGEN GRANDE) ---
    case 'consultar_resoluciones':
        $url_resoluciones = "https://www.gob.pe/institucion/regionpasco/normas-legales";
        $url_video_tutorial = "https://www.youtube.com/watch?v=jXXAx11HTo4"; 
        $imagen_tutorial = "https://img.youtube.com/vi/jXXAx11HTo4/sddefault.jpg"; 

        $response_array = [
            "fulfillmentMessages" => [
                [
                    "payload" => [
                        "richContent" => [
                            [
                                [
                                    "type" => "image",
                                    "rawUrl" => $imagen_tutorial,
                                    "accessibilityText" => "Portada del Tutorial"
                                ],
                                [
                                    "type" => "info",
                                    "title" => "📘 Buscador de Normas Legales",
                                    "subtitle" => "Tutorial: Aprende a buscar resoluciones y decretos en el portal oficial.",
                                    "actionLink" => $url_video_tutorial
                                ],
                                [
                                    "type" => "button", 
                                    "icon" => ["type" => "play_circle", "color" => "#FF0000"],
                                    "text" => "🎥 Ver Video Tutorial", 
                                    "link" => $url_video_tutorial
                                ],
                                [
                                    "type" => "button", 
                                    "icon" => ["type" => "public", "color" => "#0057b7"],
                                    "text" => "🏛️ Ir al Buscador Oficial", 
                                    "link" => $url_resoluciones
                                ],
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
                                [
                                    "type" => "info", 
                                    "title" => "Atención al Ciudadano", 
                                    "subtitle" => "Sede Central GORE Pasco", 
                                    "image" => ["src" => ["rawUrl" => "https://cdn-icons-png.flaticon.com/512/3059/3059502.png"]]
                                ],
                                [
                                    "type" => "description",
                                    "title" => "📌 Datos de Contacto:",
                                    "text" => [
                                        "📍 Dir: Sede Central: Edificio Estatal Nº 01 San Juan Pampa - Pasco",
                                        "📞 Tel: (063) 281843",
                                        "💻 Mesa de Partes: https://mesadepartes.regionpasco.gob.pe",
                                        "⏰ Horario: Lun-Vie (8:00am - 5:30pm)"
                                    ]
                                ],
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
                "💻 Mesa de Partes: https://mesadepartes.regionpasco.gob.pe",
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
                "💻 Mesa de Partes: https://mesadepartes.regionpasco.gob.pe",
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
                "💻 Mesa de Partes: https://mesadepartes.regionpasco.gob.pe",
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
                "💻 Mesa de Partes: https://mesadepartes.regionpasco.gob.pe",
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
                "💻 Mesa de Partes: https://mesadepartes.regionpasco.gob.pe",
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
                "💻 Mesa de Partes: https://mesadepartes.regionpasco.gob.pe",
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