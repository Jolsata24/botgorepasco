<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo_gore.png" type="image/x-icon" />
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.11/dist/dotlottie-wc.js" type="module"></script>
    <title>Chatbot GORE Pasco</title>
    <style>
        /* --- ESTILOS GENERALES --- */
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-image: url('fondo_login.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 600px;
            border-top: 5px solid #005C9F;
        }

        h1 { color: #005C9F; margin-bottom: 15px; }
        p { color: #333; line-height: 1.6; font-weight: 500; }

        /* --- CONTENEDOR DEL ROBOT (El área clickeable) --- */
        .robot-container {
            position: fixed;
            bottom: 20px; 
            right: 20px;
            width: 160px;
            height: 200px;
            z-index: 9990;
            cursor: pointer;
            
            /* Animación de flotar */
            animation: flotar 3s ease-in-out infinite;
            transition: all 0.5s ease;
        }

        /* --- 1. EL GLOBO --- */
        .burbuja-saludo {
            position: absolute;
            top: -20px; 
            right: 0; 
            left: auto;
            transform: none;
            
            z-index: 10001;
            background: #ffffff;
            color: #005C9F;
            padding: 10px 15px;
            border-radius: 15px;
            border: 2px solid #FFB81C;
            
            width: 160px; 
            box-sizing: border-box; 
            text-align: center;
            font-size: 13px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            animation: aparecer 0.5s ease-out forwards;
        }

        /* La Cuerda del globo */
        .burbuja-saludo::after {
            content: '';
            position: absolute;
            bottom: -40px;
            right: 40px; 
            
            width: 2px;
            height: 40px;
            background-color: #33333300; /* Color de la cuerda visible */
            border: none; 
        }

        /* --- 2. EL ROBOT --- */
        dotlottie-wc {
            position: absolute;
            bottom: 0; 
            right: 0; 
            z-index: 9995;
            
            width: 150px !important; 
            height: 150px !important;
            
            transition: transform 0.3s ease;
            filter: drop-shadow(0 5px 5px rgba(0,0,0,0.3));
        }

        .robot-container:hover dotlottie-wc {
            transform: scale(1.05);
        }

        /* --- BOTÓN "X" PERSONALIZADO --- */
        /* --- BOTÓN "X" PERSONALIZADO (Corregido) --- */
        .boton-cerrar-custom {
            position: fixed;
            /* CAMBIO: Lo fijamos arriba a la derecha para que siempre se vea */
            top: 20px; 
            right: 25px;
            
            width: 40px; /* Un poco más grande para facilitar el clic */
            height: 40px;
            
            background-color: #ff4444; 
            color: white;
            border: 2px solid white;
            border-radius: 50%;
            
            font-family: Arial, sans-serif;
            font-size: 20px;
            font-weight: bold;
            
            cursor: pointer;
            z-index: 2147483647; /* El número más alto posible para que nada lo tape */
            display: none; /* Oculto al inicio */
            
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, background-color 0.2s;
        }

        .boton-cerrar-custom:hover {
            transform: scale(1.1);
            background-color: #cc0000;
        }
        /* --- ANIMACIONES --- */
        @keyframes flotar {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }

        @keyframes aparecer {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        /* =========================================
           📱 ESTILOS PARA CELULAR (Responsive)
           ========================================= */
        @media (max-width: 768px) {
            
            /* Ajustamos el contenedor principal */
            .container {
                padding: 20px;
                width: 85%;
            }
            
            h1 { font-size: 1.5rem; }
            
            /* Robot un poco más pequeño */
            
            .robot-container { 
                width: 130px; 
                height: 170px; 
                bottom: -50px;   /* Pegado abajo */
                right: 0px;    /* Pegado a la derecha */
            }
            
            dotlottie-wc {
                width: 120px !important;
                height: 120px !important;
            }

            /* Ajuste del globo */
            .burbuja-saludo {
                width: 140px;
                font-size: 12px;
                padding: 8px;
                top: -15px;
            }
            
            
            .burbuja-saludo::after {
                right: 30px; /* Ajuste para que coincida con la mano en tamaño móvil */
            }

            /* Ajuste de la X para que no estorbe */
            .boton-cerrar-custom {
                width: 40px;
                height: 40px;
                top: 15px;
                right: 15px;
                font-size: 20px;
            }

            /* El chat en móvil suele ocupar el 100%, aseguramos la X visible */
            df-messenger {
                --df-messenger-chat-window-height: 100%; /* Altura completa */
                --df-messenger-chat-window-width: 100%;
            }
        }
        /* --- ESTILOS DIALOGFLOW --- */
        df-messenger {
            --df-messenger-button-titlebar-color: #005C9F;
            --df-messenger-button-titlebar-font-color: #ffffff;
            --df-messenger-bot-message: #FFB81C;
            --df-messenger-font-color: #000000;
            --df-messenger-user-message: #004a7f;
            --df-messenger-send-icon: #005C9F;
            --df-messenger-chat-background-color: #ffffff;
            /* Forzamos una altura para que la X coincida siempre */
            --df-messenger-chat-window-height: 600px; 
            z-index: 20000; 
            
            /* Oculto al inicio */
            opacity: 0;
            visibility: hidden;
            z-index: -100;
        }

        /* Clase visible */
        df-messenger.activo {
            opacity: 1;
            visibility: visible;
            z-index: 20000;
        }
    </style>
</head>

<body>

    <div class="container">
        <img src="logo_gore.png" alt="Logo GORE" style="width: 80px; margin-bottom: 10px;">
        <h1>Gobierno Regional de Pasco</h1>
        <p><strong>Asistente Virtual de Trámites</strong></p>
        <p>Prueba de integración con Base de Datos.</p>
    </div>

    <button id="btn-cerrar-x" class="boton-cerrar-custom" onclick="cerrarChatCustom()">
        ✕
    </button>
    
    <div id="robot-wrapper" class="robot-container" onclick="abrirChat()">
        <div class="burbuja-saludo" id="globo-texto">
            👋 <strong>¡Hola!</strong><br>
            Soy el Bot del GORE Pasco.<br>
            <span style="font-size: 0.9em; text-decoration: underline;">Haz clic para chatear</span>
        </div>
        
        <dotlottie-wc
            src="https://lottie.host/752a3923-c9b9-4a64-990c-fb93265917ee/iLXH7qYPJn.lottie"
            autoplay
            loop
        ></dotlottie-wc>
    </div>

    <script src="https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1"></script>
    <df-messenger
        intent="WELCOME"
        chat-title="Aló GORE Pasco 🤖"
        agent-id="499c44d3-1764-4854-8ab1-65e16cac7244"
        language-code="es"
        chat-icon="logo_gore.png"
    ></df-messenger>

    <script>
        // Referencias a los elementos
        const dfMessenger = document.querySelector('df-messenger');
        const robot = document.getElementById('robot-wrapper');
        const globo = document.getElementById('globo-texto');
        const btnCerrar = document.getElementById('btn-cerrar-x');

        // 1. FUNCIÓN PARA ABRIR EL CHAT
        function abrirChat() {
            // A) CAMBIO VISUAL INMEDIATO
            robot.style.display = 'none';       // Robot se va
            btnCerrar.style.display = 'flex';   // Botón X aparece
            
            // B) ACTIVAR CONTENEDOR
            dfMessenger.classList.add('activo');
            
            // C) CLICK INTERNO PARA ABRIR
            const btn = dfMessenger.shadowRoot.querySelector('button#widgetIcon');
            if(btn) {
                btn.click();
            } else {
                setTimeout(() => {
                     const btnRetry = dfMessenger.shadowRoot.querySelector('button#widgetIcon');
                     if(btnRetry) btnRetry.click();
                }, 200);
            }
        }

        // 2. FUNCIÓN PARA CERRAR EL CHAT (Botón X)
        function cerrarChatCustom() {
            // A) Forzamos la desaparición del botón X inmediatamente (para mejor sensación)
            btnCerrar.style.display = 'none';

            // B) Click interno para cerrar el chat real
            const btn = dfMessenger.shadowRoot.querySelector('button#widgetIcon');
            if(btn) btn.click();

            // C) (Backup) Si por alguna razón el evento no se dispara, 
            // forzamos que el robot vuelva en 500ms
            setTimeout(() => {
                robot.style.display = 'block';
                dfMessenger.classList.remove('activo');
            }, 500);
        }

        // 3. ESCUCHA DE EVENTOS (Para mantener sincronía)
        window.addEventListener('df-chat-open-changed', (event) => {
            const isOpen = event.detail.isOpen;
            
            if (isOpen) {
                // --- ABIERTO ---
                robot.style.display = 'none';
                btnCerrar.style.display = 'flex';
                dfMessenger.classList.add('activo');
            } else {
                // --- CERRADO ---
                btnCerrar.style.display = 'none'; // Aseguramos que la X se vaya
                
                // Esperamos la animación y mostramos robot
                setTimeout(() => {
                    robot.style.display = 'block'; // ¡Vuelve robot sin condiciones!
                    dfMessenger.classList.remove('activo');
                }, 500);
            }
        });

        // 4. LIMPIEZA INICIAL
        window.addEventListener('load', function () {
            // Aseguramos estado inicial correcto
            if(btnCerrar) btnCerrar.style.display = 'none';
            if(robot) robot.style.display = 'block';

            // Ocultar botón azul nativo
            const ocultarBoton = setInterval(() => {
                if (dfMessenger && dfMessenger.shadowRoot) {
                    const btn = dfMessenger.shadowRoot.querySelector('button#widgetIcon');
                    if (btn) {
                        btn.style.opacity = '0'; 
                        btn.style.pointerEvents = 'none';
                        btn.style.width = '0px'; 
                        btn.style.height = '0px';
                    }
                }
            }, 100);
            setTimeout(() => { clearInterval(ocultarBoton); }, 5000);
        });
    </script>

</body>
</html>