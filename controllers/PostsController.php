<?php

class PostController {
    private $pdo;

    // Al instanciar el controlador, le pasamos la conexión a la base de datos
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function handleGet( $id, $page = null, $limit = null) {
        try{
            if ($id !== null) {
                $query = "SELECT * FROM posts WHERE id = :id";
                $stmt = $this->pdo->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                $post = $stmt->fetch(PDO::FETCH_ASSOC);

                // Si el ID no existe en la BD, respondemos 404
                if (!$post) {
                    http_response_code(404);
                    echo json_encode(['error' => 'El post solicitado no existe.']);
                    return;
                }

                // Si existe, devolvemos solo ese objeto
                echo json_encode(['data' => $post]);

            } else {
                // Obtener todos los posts
                $query = "SELECT * FROM posts";

                if ($page !== null && $limit !== null) {
                    $offset = ($page - 1) * $limit;
                    $query .= " LIMIT :limit OFFSET :offset";
                }

                $stmt = $this->pdo->prepare($query);

                if ($page !== null && $limit !== null) {
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                }

                $stmt->execute();
                $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['data' => $posts]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno al consultar los datos.']);
        }
        
    }


    public function handlePost($input) {
        // 1. Validar que las llaves existan y no estén vacías
        if (!isset($input['title']) || trim($input['title']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El campo "title" es obligatorio y no puede estar vacío.']);
            return; // Detiene la ejecución de la función
        }

        if (!isset($input['content']) || trim($input['content']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El campo "content" es obligatorio y no puede estar vacío.']);
            return; 
        }

        if (!isset($input['status']) || trim($input['status']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El campo "status" es obligatorio.']);
            return;
        }

        // 2. Validar reglas de negocio (ej. estados permitidos)
        $allowedStatuses = ['draft', 'published'];
        if (!in_array($input['status'], $allowedStatuses)) {
            http_response_code(422); // Unprocessable Entity (Datos semánticamente incorrectos)
            echo json_encode(['error' => 'El estado enviado no es válido. Valores permitidos: ' . implode(', ', $allowedStatuses)]);
            return;
        }

        // 3. Intento de inserción seguro con un bloque try-catch
        try {
            $query = "INSERT INTO posts (title, content, status) VALUES (:title, :content, :status)";
            $stmt = $this->pdo->prepare($query);
            
            // Al usar bindParam, podemos sanitizar la entrada si fuera necesario, 
            // pero PDO ya se encarga de que no rompa el SQL.
            $stmt->bindParam(':title', $input['title']);
            $stmt->bindParam(':content', $input['content']);
            $stmt->bindParam(':status', $input['status']);
            
            $stmt->execute();

            // Respuesta exitosa
            http_response_code(201); 
            echo json_encode(['data' => ['message' => 'Post creado exitosamente']]);

        } catch (PDOException $e) {
            // Si la base de datos falla (ej. tabla caída, error de sintaxis interna)
            http_response_code(500); 
            // NOTA: En producción, nunca muestres $e->getMessage() al usuario, ya que revela info del servidor.
            // Solo usa un mensaje genérico y guarda el error en un archivo log.
            echo json_encode(['error' => 'Error interno del servidor al intentar guardar el post.']);
        }
    }

    public function handlePut($id, $input) {
        // 1. Validar que el ID exista en la petición
        if (!isset($id) || empty($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'El campo "id" es obligatorio para actualizar.']);
            return;
        }

        // PUT debe recibir el recurso completo
        if (!isset($input['title']) || trim($input['title']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El campo "title" es obligatorio y no puede estar vacío.']);
            return;
        }

        if (!isset($input['content']) || trim($input['content']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El campo "content" es obligatorio y no puede estar vacío.']);
            return;
        }

        if (!isset($input['status']) || trim($input['status']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El campo "status" es obligatorio.']);
            return;
        }

        $allowedStatuses = ['draft', 'published'];
        if (!in_array($input['status'], $allowedStatuses)) {
            http_response_code(422); // Unprocessable Entity (Datos semánticamente incorrectos)
            echo json_encode(['error' => 'El estado enviado no es válido. Valores permitidos: ' . implode(', ', $allowedStatuses)]);
            return;
        }

        try {
            // 2. Ejecutar la actualización
            $query = "UPDATE posts SET title = :title, content = :content, status = :status WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $input['title']);
            $stmt->bindParam(':content', $input['content']);
            $stmt->bindParam(':status', $input['status']);
            $stmt->execute();

            // 3. ¿Realmente se actualizó algo?
            // rowCount() nos dice cuántas filas cambiaron en la base de datos
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'No se encontró el post con el ID provisto o los datos son idénticos.']);
            } else {
                echo json_encode(['data' => ['message' => 'Post actualizado exitosamente.']]);
            }

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno al intentar actualizar el post.']);
        }
    }

    public function handlePatch($id, $input) {
        // 1. Verificar si el cliente envió al menos algo para actualizar
        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'No se proporcionaron campos para actualizar.']);
            return;
        }

        $fields = [];     // Guardará los pedazos de SQL: "title = :title"
        $bindings = [];   // Guardará los valores para el bindParam

        // 2. Analizar dinámicamente qué campos venían en el JSON
        if (isset($input['title'])) {
            $fields[] = "title = :title";
            $bindings[':title'] = $input['title'];
        }

        if (isset($input['content'])) {
            $fields[] = "content = :content";
            $bindings[':content'] = $input['content'];
        }

        if (isset($input['status'])) {
            // Aquí puedes meter la validación del estado si viene en la petición
            $allowedStatuses = ['draft', 'published', 'archived'];
            if (!in_array($input['status'], $allowedStatuses)) {
                http_response_code(422);
                echo json_encode(['error' => 'Estado no válido.']);
                return;
            }
            $fields[] = "status = :status";
            $bindings[':status'] = $input['status'];
        }

        // 3. Construir la consulta SQL dinámicamente
        // implode toma el array y lo une con comas: "title = :title, content = :content"
        $queryString = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($queryString);
            
            // Unir el ID
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            // Unir dinámicamente todos los campos que sí venían en el JSON
            foreach ($bindings as $placeholder => $value) {
                $stmt->bindValue($placeholder, $value);
            }

            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Post no encontrado o los datos enviados son idénticos a los actuales.']);
            } else {
                echo json_encode(['data' => ['message' => 'Post actualizado parcialmente con éxito.']]);
            }

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno al actualizar.']);
        }
    }

    public function handleDelete($id) {
        // 1. Validar que el ID exista en la petición
        if (!isset($id) || empty($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'El campo "id" es obligatorio para eliminar.']);
            return;
        }

        try {
            $query = "DELETE FROM posts WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // 2. Verificar si el registro existía y fue borrado
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'No se pudo eliminar. El post con el ID provisto no existe.']);
            } else {
                echo json_encode(['data' => ['message' => 'Post eliminado exitosamente.']]);
            }

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno al intentar eliminar el post.']);
        }
    }
}