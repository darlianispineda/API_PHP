<?php

class CommentController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Listar comentarios de un post específico
    public function getCommentsByPost($postId, $page = null, $limit = null) {
        try {
            $query = "SELECT * FROM comments WHERE post_id = :post_id ORDER BY created_at DESC";
            if ($page !== null && $limit !== null) {
                $offset = ($page - 1) * $limit;
                $query .= " LIMIT :limit OFFSET :offset";
            }
            $stmt = $this->pdo->prepare($query);

            if ($page !== null && $limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->execute();
            
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($comments)) {
                echo json_encode(['message' => 'No hay comentarios para este post.']);
                return;
            }
            echo json_encode($comments);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error al obtener los comentarios.']);
        }
    }

    // Agregar un comentario a un post
    public function createComment($postId, $input) {
        // Validación básica
        if($postId === null || !is_numeric($postId)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID del post inválido.']);
            return;
        }

        if (!isset($input['author']) || trim($input['author']) === '' || !isset($input['content']) || trim($input['content']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Los campos "author" y "content" son obligatorios.']);
            return;
        }

        try {
            $query = "INSERT INTO comments (post_id, author, content) VALUES (:post_id, :author, :content)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->bindParam(':author', $input['author']);
            $stmt->bindParam(':content', $input['content']);
            $stmt->execute();

            http_response_code(201);
            echo json_encode(['message' => 'Comentario agregado con éxito.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error al guardar el comentario.']);
        }
    }

   public function deleteComment($commentId) {
        try {
            $query = "DELETE FROM comments WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(['message' => 'Comentario eliminado con éxito.']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Comentario no encontrado.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error al eliminar el comentario.']);
        }
    }
}