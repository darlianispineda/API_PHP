<script>
// Ejemplos de consumo de la API de Posts y Comentarios

const API_URL = 'http://localhost/api/api.php';
const TOKEN = 'Tu_TOKEN_DE_AUTORIZACION_AQUI'; // Reemplaza con tu token real

async function getPosts() {
  const res = await fetch(`${API_URL}/posts`);
  const data = await res.json();
  console.log('GET /posts', data);
}

async function createPost() {
  const res = await fetch(`${API_URL}/posts`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${TOKEN}`,
    },
    body: JSON.stringify({
      title: 'Mi post',
      content: 'Contenido del post',
      status: 'draft',
    }),
  });
  const data = await res.json();
  console.log('POST /posts', data);
}

async function getComments(postId) {
  const res = await fetch(`${API_URL}/posts/${postId}/comments`);
  const data = await res.json();
  console.log(`GET /posts/${postId}/comments`, data);
}

async function deleteComment(commentId) {
  const res = await fetch(`${API_URL}/comments/${commentId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${TOKEN}`,
    },
  });
  const data = await res.json();
  console.log(`DELETE /comments/${commentId}`, data);
}

// Para probar, descomenta las llamadas que quieras ejecutar:
//getPosts();
// createPost();
 //getComments(7);
 deleteComment(4);

</script>