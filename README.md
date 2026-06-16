# API de Posts y Comentarios

Este proyecto es una API REST simple desarrollada en PHP puro usando PDO para conectar con MySQL.

## Estructura del proyecto

- `api.php` - Punto de entrada principal y enrutador.
- `config/db.php` - Configuración de la conexión PDO con MySQL.
- `controllers/PostsController.php` - Controlador para operaciones CRUD de posts.
- `controllers/CommentController.php` - Controlador para obtener, crear y borrar comentarios de posts.
- `models/` - Carpeta preparada para modelos (actualmente vacía).

## Requisitos

- PHP 7.4+ con PDO y la extensión PDO MySQL habilitada.
- MySQL/MariaDB.
- Servidor local como XAMPP o similar.

## Configuración

1. Copia el proyecto al directorio raíz de tu servidor local, por ejemplo `c:\xampp\htdocs\api`.
2. Asegúrate de que MySQL esté ejecutándose.
3. Edita `config/db.php` si necesitas cambiar los datos de conexión:

```php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'post_api';
```

4. Crea la base de datos y las tablas necesarias según tu esquema.

## Endpoints disponibles

### Posts

- `GET /posts`
  - Lista todos los posts.

- `GET /posts/{id}`
  - Obtiene un post por su ID.

- `POST /posts`
  - Crea un nuevo post.
  - Body JSON obligatorio:
    - `title` (string)
    - `content` (string)
    - `status` (string) - valores permitidos: `draft`, `published`

- `PATCH /posts/{id}`
  - Actualiza parcialmente un post existente.
  - Body JSON con los campos a modificar.

- `DELETE /posts/{id}`
  - Elimina un post por su ID.

### Comentarios

- `GET /posts/{id}/comments`
  - Lista comentarios asociados a un post.

- `POST /posts/{id}/comments`
  - Crea un comentario para un post.
  - Body JSON obligatorio:
    - `author` (string)
    - `content` (string)

- `DELETE /comments/{id}`
  - Elimina un comentario por su ID.

## Ejemplos

### Crear un post

```bash
curl -X POST http://localhost/api/posts \
  -H "Content-Type: application/json" \
  -d '{"title":"Mi post","content":"Contenido del post","status":"draft"}'
```

### Obtener comentarios de un post

```bash
curl http://localhost/api/posts/5/comments
```

### Eliminar un comentario

```bash
curl -X DELETE http://localhost/api/comments/12
```

## Notas

- Las respuestas se devuelven en JSON.
- La API devuelve códigos HTTP adecuados para validación y errores.
