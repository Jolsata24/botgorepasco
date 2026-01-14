<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo_gore.png" type="image/x-icon" />
    <title>Chatbot GORE Pasco</title>
    <style>
        /* --- ESTILOS GENERALES DE LA PÁGINA --- */
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            
            /* AQUÍ ESTÁ EL CAMBIO PARA EL FONDO DE PANTALLA */
            background-image: url('fondo_login.jpg'); /* La imagen de la empresa */
            background-size: cover; /* Cubre toda la pantalla */
            background-position: center; /* Centrada */
            background-repeat: no-repeat; /* No repetirse */
            background-attachment: fixed; /* Fijo al hacer scroll */
            
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: rgba(255, 255, 255, 0.95); /* Blanco con un poquitín de transparencia */
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2); /* Sombra más fuerte para resaltar del fondo */
            text-align: center;
            max-width: 600px;
            border-top: 5px solid #005C9F;
        }

        h1 { color: #005C9F; margin-bottom: 15px; }
        p { color: #333; line-height: 1.6; font-weight: 500; }

        /* --- PERSONALIZACIÓN DEL CHATBOT --- */
        df-messenger {
            /* COLORES PRINCIPALES */
            --df-messenger-button-titlebar-color: #005C9F; /* Azul GORE */
            --df-messenger-button-titlebar-font-color: #ffffff;
            
            --df-messenger-bot-message: #FFB81C; /* Amarillo GORE */
            --df-messenger-font-color: #000000; /* Texto negro */
            
            --df-messenger-user-message: #004a7f; /* Azul oscuro usuario */
            
            --df-messenger-send-icon: #005C9F;
            --df-messenger-chat-background-color: #ffffff;

            /* Posición */
            bottom: 25px;
            right: 25px;
            z-index: 9999;
        }

        /* 1. LOGO EN LA CABECERA (Junto al título "Aló GORE") */
        df-messenger::part(header) {
            display: flex;
            align-items: center;
        }
        
        df-messenger::part(title)::before {
            content: "";
            display: inline-block;
            /* Volví a poner el LOGO aquí porque se ve mejor en pequeño que la foto de fondo */
            background-image: url('logo_gore.png'); 
            background-size: cover;
            background-repeat: no-repeat;
            width: 30px;
            height: 30px;
            margin-right: 10px;
            vertical-align: middle;
            background-color: white;
            border-radius: 50%;
            border: 2px solid white;
        }

        /* 2. LOGO DE FONDO EN EL CHAT (Marca de agua) */
        df-messenger::part(content) {
            background-image: url('logo_gore.png');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 50%; 
            background-blend-mode: overlay;
            opacity: 1; 
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

    <script src="https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1"></script>

    <df-messenger
        intent="WELCOME"
        chat-title="Aló GORE Pasco 🤖"
        agent-id="499c44d3-1764-4854-8ab1-65e16cac7244"
        language-code="es"
        chat-icon="logo_gore.png"
    ></df-messenger>

    <script>
        // Limpieza de memoria al recargar
        window.addEventListener('load', function () {
            const chatStorageKeys = Object.keys(localStorage).filter(key => key.startsWith('df-messenger-'));
            chatStorageKeys.forEach(key => localStorage.removeItem(key));
        });
    </script>

</body>
</html>