<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Asistente</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Estilos personalizados para la barra de navegación -->
    <link rel="stylesheet" href="css/navbar-custom.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-light">
    <div class="container-fluid px-0">
        <div class="custom-navbar">
            <div class="custom-navbar-container">
                <a class="custom-navbar-brand" href="#">
                    <i class="fas fa-robot"></i>Asistente Virtual
                </a>
                <div class="custom-navbar-menu">
                    <a class="custom-navbar-link" href="admin.php">
                        <i class="fas fa-cog"></i>Administración
                    </a>
                </div>
            </div>
        </div>
        
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex align-items-center">
                                <div class="position-relative me-3">
                                    <div class="bg-primary rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-robot text-white"></i>
                                    </div>
                                    <span class="position-absolute bottom-0 end-0 bg-success rounded-circle" style="width: 12px; height: 12px; border: 2px solid white;"></span>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-semibold">Asistente IA</h5>
                                    <small class="text-muted">En línea</small>
                                </div>
                            </div>
                        </div>
                        
                        <div id="chat-messages" class="chat-messages bg-light p-4" style="height: 60vh; overflow-y: auto;">
                            <div class="message bot">
                                <div class="message-content shadow-sm"><?php 
                                $welcome_file = 'memory-bank/welcome_message.txt';
                                if (file_exists($welcome_file)) {
                                    echo htmlspecialchars(file_get_contents($welcome_file));
                                } else {
                                    echo '¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte hoy?';
                                }
                                ?></div>
                            </div>
                        </div>
                        
                        <div class="chat-input bg-white p-3 border-top">
                            <form id="chat-form" class="d-flex align-items-center">
                                <div class="input-group">
                                    <input type="text" id="user-input" class="form-control border-0 bg-light rounded-pill py-2 px-3" placeholder="Escribe tu mensaje aquí..." autocomplete="off">
                                    <button type="submit" class="btn btn-primary rounded-circle ms-2" style="width: 45px; height: 45px;">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted small">
                            <i class="fas fa-shield-alt me-1"></i> Tus conversaciones son privadas y seguras
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/chatbot.js"></script>
</body>
</html>