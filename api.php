<?php

header('Content-Type: application/json');
include 'config/db.php';
include 'controllers/PostsController.php';
include 'controllers/CommentController.php'; // 1. Importamos el nuevo controlador

function verificarAutenticacion() {
    // 1. Obtener todos los encabezados de la petición
    $headers = getallheaders();

    // 2. Definir tu clave secreta maestra (En el futuro, esto vendrá de la BD o un archivo .env)
    $api_key_secreta = isset($_ENV['API_KEY']) ? $_ENV['API_KEY'] : null;
    
    // 3. Verificar si viene el encabezado 'Authorization'
    // Nota: Dependiendo del servidor, puede escribirse 'Authorization' o 'authorization'
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);

    if (!$authHeader) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Acceso denegado. Token de autorización ausente.']);
        exit; // Frena la API de inmediato
    }

    // 4. El estándar dicta que el token viaja como: "Bearer MI_LLAVE"
    // Vamos a separar la palabra "Bearer" del token real
    $parts = explode(' ', $authHeader);
    
    if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') { # Verificamos que el formato sea correcto
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Formato de autorización inválido. Debe ser: Bearer [token]']);
        exit;
    }

    $tokenRecibido = $parts[1];

    // 5. Comparar el token recibido con nuestra clave secreta
    if ($tokenRecibido !== $api_key_secreta) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Token inválido o expirado.']);
        exit;
    }
    
    // Si todo sale bien, la función no hace nada y permite que el código continúe
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true); # Decodificamos el JSON recibido en un array asociativo de PHP

$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';
$pathParts = explode('/', trim($path, '/'));

// 2. Extraemos las variables de la ruta de forma segura
$resource    = isset($pathParts[0]) ? $pathParts[0] : null;
$id          = (isset($pathParts[1]) && is_numeric($pathParts[1])) ? (int)$pathParts[1] : null;
$subResource = isset($pathParts[2]) ? $pathParts[2] : null; // Aquí capturamos 'comments'

// 3. Instanciamos ambos controladores
$postController    = new PostController($pdo);
$commentController = new CommentController($pdo);

// 4. Enrutador Principal
if ($resource === 'comments') {
    if ($id === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Debes especificar el ID del comentario. Ej: /comments/12']);
        exit;
    }

    switch ($method) {
        case 'DELETE':
            verificarAutenticacion(); //  Si no hay token válido, aquí muere la petición
            // Aquí llamarías a un método en tu CommentController para borrar por ID
            $commentController->deleteComment($id);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido para operaciones directas de comentarios.']);
            break;
    }
    exit;

}elseif ($resource === 'posts') {

 
    // CASO A: La ruta pide comentarios de un post (Ej: /posts/5/comments)
    if ($subResource === 'comments') {
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Debes especificar el ID del post. Ej: /posts/5/comments']);
            exit;
        }

        switch ($method) {
            case 'GET':
                $commentController->getCommentsByPost($id);
                break;
            case 'POST':
                verificarAutenticacion(); //  Si no hay token válido, aquí muere la petición
                $commentController->createComment($id, $input);
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido para comentarios de un post.']);
                break;
        }
        exit; // Terminamos la ejecución para que no pase al código de abajo
    }

    // CASO B: La ruta es para los posts directamente (Ej: /posts o /posts/5)
    if ($subResource === null) {
        switch ($method) {
            case 'GET':
                $postController->handleGet($id);
                break;
            case 'POST':
                verificarAutenticacion(); //  Si no hay token válido, aquí muere la petición
                $postController->handlePost($input);
                break;
            case 'PATCH':
                verificarAutenticacion(); //  Si no hay token válido, aquí muere la petición
                $postController->handlePatch($id, $input);
                break;
            case 'PUT':
                verificarAutenticacion(); //  Si no hay token válido, aquí muere la petición
                $postController->handlePut($id, $input);
                break;
            case 'DELETE':
                verificarAutenticacion(); //  Si no hay token válido, aquí muere la petición
                $postController->handleDelete($id);
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido.']);
                break;
        }
        exit;
    }
}

// Si no entró a ninguna de las condiciones anteriores
http_response_code(404);
echo json_encode(['error' => 'Ruta o recurso no encontrado.']);
