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
            width: 160px; /* Definimos ancho fijo para centrar elementos */
            height: 200px; /* Altura suficiente para globo + robot */
            z-index: 9990;
            cursor: pointer;
            
            /* Animación de todo el conjunto flotando */
            animation: flotar 3s ease-in-out infinite;
            transition: bottom 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55), right 0.5s ease;
        }

        /* CLASE PARA CUANDO SE ABRE EL CHAT (Se va para arriba) */
        .robot-arriba {
            bottom: 630px !important; 
            right: 20px !important;
        }

        /* --- 1. EL GLOBO (CAPA SUPERIOR - Z-INDEX ALTO) --- */
        .burbuja-saludo {
            position: absolute; /* Posición absoluta dentro del contenedor */
            top: 0; /* Lo pegamos arriba */
            right: 10px; /* Alineado un poco a la derecha */
            z-index: 10001; /* ENCIMA DEL ROBOT */
            
            background: #ffffff;
            color: #005C9F;
            padding: 10px 15px;
            border-radius: 18px;
            border-bottom-right-radius: 0; /* Esquina recta para simular conexión */
            
            font-weight: bold;
            font-size: 14px;
            line-height: 1.3;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 2px solid #FFB81C;
            
            width: 180px; /* Ancho del globo */
            text-align: center;
            
            /* Animación de entrada */
            animation: aparecer 0.5s ease-out forwards;
        }

        /* La "colita" del globo apuntando hacia la mano/cabeza del robot */
        .burbuja-saludo::after {
            content: '';
            position: absolute;
            bottom: -8px; /* Que salga por debajo */
            right: 0; /* Pegado a la esquina derecha donde está el robot */
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-top: 10px solid #FFB81C; /* Color del borde */
        }

        /* --- 2. EL ROBOT (CAPA INFERIOR - Z-INDEX MEDIO) --- */
        dotlottie-wc {
            position: absolute;
            bottom: 0; /* Pegado al fondo del contenedor */
            right: 0;  /* Pegado a la derecha */
            z-index: 9995; /* DEBAJO DEL GLOBO */
            
            width: 150px !important; 
            height: 150px !important;
            
            transition: transform 0.3s ease;
            filter: drop-shadow(0 5px 5px rgba(0,0,0,0.3));
        }

        .robot-container:hover dotlottie-wc {
            transform: scale(1.05); /* Efecto leve al pasar mouse */
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

        /* --- DIALOGFLOW (OCULTO) --- */
        df-messenger {
            --df-messenger-button-titlebar-color: #005C9F;
            --df-messenger-button-titlebar-font-color: #ffffff;
            --df-messenger-bot-message: #FFB81C;
            --df-messenger-font-color: #000000;
            --df-messenger-user-message: #004a7f;
            --df-messenger-send-icon: #005C9F;
            --df-messenger-chat-background-color: #ffffff;
            z-index: 20000; 
        }

        df-messenger::part(header) { display: flex; align-items: center; }
        df-messenger::part(title)::before {
            content: ""; display: inline-block;
            background-image: url('logo_gore.png'); 
            background-size: cover; width: 30px; height: 30px;
            margin-right: 10px; vertical-align: middle;
            background-color: white; border-radius: 50%; border: 2px solid white;
        }
        df-messenger::part(content) {
            background-image: url('logo_gore.png');
            background-repeat: no-repeat; background-position: center;
            background-size: 50%; background-blend-mode: overlay;
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

    <div id="robot-wrapper" class="robot-container" onclick="abrirChat()">
        
        <div class="burbuja-saludo" id="globo-texto">
            👋 <strong>¡Hola!</strong><br>
            Soy el Bot del GORE Pasco.<br>
            <span style="font-size: 0.9em; text-decoration: underline;">Haz clic aquí</span>
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
        function abrirChat() {
            const dfMessenger = document.querySelector('df-messenger');
            const robot = document.getElementById('robot-wrapper');
            const globo = document.getElementById('globo-texto');

            // Abrir chat
            dfMessenger.shadowRoot.querySelector('button#widgetIcon').click();
            
            // Subir robot
            robot.classList.add('robot-arriba');

            // Ocultar globo
            if(globo) globo.style.display = 'none';
        }

        window.addEventListener('load', function () {
            // Limpieza
            const chatStorageKeys = Object.keys(localStorage).filter(key => key.startsWith('df-messenger-'));
            chatStorageKeys.forEach(key => localStorage.removeItem(key));
            
            // Ocultar Botón Azul insistentemente
            const ocultarBoton = setInterval(() => {
                const dfMessenger = document.querySelector('df-messenger');
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