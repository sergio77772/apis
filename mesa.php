<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite solicitudes desde cualquier dominio
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Directorio en la raíz del servidor

// Paginación
$offset = ($page - 1) * $limit;

   // Generar la fecha y hora actual en el formato adecuado
   $fecha_hora_actual = date('Y-m-d H:i:s');
 

switch ($method) {
    case 'GET':
        if ($endpoint === 'mesa') {
            $search = $_GET['search'] ?? '';

            // Obtener el total de registros
            $countSql = "SELECT COUNT(*) as total FROM mesa_web WHERE nombre LIKE :search";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->bindValue(':search', "%$search%");
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calcular páginas totales
            $totalPages = ceil($total / $limit);

            // Obtener los registros
            $sql = "SELECT idmesa, titulo,dias,estados,nombre, solucion,estado, imagen,fechahora
                    FROM mesa_web 
                    WHERE nombre LIKE :search 
                    LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Respuesta JSON
            echo json_encode([
                'mesa' => $result,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => (int)$page,
            ]);
// BEGIN BITACORA 
        // Generar la fecha y hora actual en el formato adecuado
        $fecha_hora_actual = date('Y-m-d H:i:s');
        // Crear el mensaje concatenando los valores de $data
         $mensaje = "ingreso a Categoria";
           $bitacora_data = [
            'fechahora' => $fecha_hora_actual, 
             'usuario' => 'Brenda',
             'modulo' => 'listado de mesa de ayuda',
             'mensaje' => $mensaje      // Mensaje personalizado
          ];
         $sql1 = "INSERT INTO bitacora_web (fechahora, usuario, modulo, mensaje)
              VALUES (:fechahora, :usuario, :modulo, :mensaje)";
         $stmt1 = $pdo->prepare($sql1);
         $stmt1->execute($bitacora_data);
            // END BITACORA





        }
        break;

    case 'POST':
        if ($endpoint === 'mesa') {
            $data = json_decode(file_get_contents('php://input'), true);
           
              $data[ 'fechahora'] = $fecha_hora_actual;


            $sql = "INSERT INTO mesa_web (nombre,titulo,estados,dias, estado,solucion,imagen ,fechahora)
                    VALUES (:nombre,:titulo,:estados,:dias, :estado, :solucion, :imagen, :fechahora)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

// BEGIN BITACORA 
        // Generar la fecha y hora actual en el formato adecuado
        $fecha_hora_actual = date('Y-m-d H:i:s');
        // Crear el mensaje concatenando los valores de $data
         $mensaje = $data['nombre'] . ' , ' . $data['estado'] . ' , ' . $data['imagen'];
           $bitacora_data = [
            'fechahora' => $fecha_hora_actual, 
             'usuario' => 'Brenda',
             'modulo' => 'nueva mesa de ayuda',
             'mensaje' => $mensaje      // Mensaje personalizado
          ];
         $sql1 = "INSERT INTO bitacora_web (fechahora, usuario, modulo, mensaje)
              VALUES (:fechahora, :usuario, :modulo, :mensaje)";
         $stmt1 = $pdo->prepare($sql1);
         $stmt1->execute($bitacora_data);
            // END BITACORA




            echo json_encode(['message' => 'Mesa creada exitosamente']);
        } elseif ($endpoint === 'upload') {
            // Subida de imagen
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $fileName = basename($file['name']);
                $targetFilePath = $uploadDir . $fileName;

                // Crear directorio si no existe
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Validar el tipo de archivo
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($file['type'], $allowedTypes)) {
                    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                        echo json_encode(['message' => 'Imagen subida exitosamente', 'filePath' => "/img/$fileName"]);
                    } else {
                        echo json_encode(['error' => 'Error al mover el archivo']);
                    }
                } else {
                    echo json_encode(['error' => 'Tipo de archivo no permitido']);
                }
            } else {
                echo json_encode(['error' => 'No se recibió un archivo válido']);
            }
        }
        break;

    case 'PUT':
        if ($endpoint === 'mesa') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = json_decode(file_get_contents('php://input'), true);
                $data[ 'fechahora'] = $fecha_hora_actual;
                $sql = "UPDATE mesa_web 
                        SET nombre = :nombre,titulo = :titulo,dias =:dias,estados =:estados, estado = :estado, solucion = :solucion, imagen = :imagen, fechahora = :fechahora 
                        WHERE idmesa = :idmesa";
                $data['idmesa'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);

  // BEGIN BITACORA 
        // Generar la fecha y hora actual en el formato adecuado
        $fecha_hora_actual = date('Y-m-d H:i:s');
        // Crear el mensaje concatenando los valores de $data
         $mensaje = $data['nombre'] . ' , ' . $data['estado'] . ' , ' . $data['imagen'];
           $bitacora_data = [
            'fechahora' => $fecha_hora_actual, 
             'usuario' => 'Brenda',
             'modulo' => 'Editar mesa de ayuda',
             'mensaje' => $mensaje      // Mensaje personalizado
          ];
         $sql1 = "INSERT INTO bitacora_web (fechahora, usuario, modulo, mensaje)
              VALUES (:fechahora, :usuario, :modulo, :mensaje)";
         $stmt1 = $pdo->prepare($sql1);
         $stmt1->execute($bitacora_data);
            // END BITACORA




                echo json_encode(['message' => 'Mesa actualizada exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'mesa') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $sql = "DELETE FROM mesa_web WHERE idmesa = :idmesa";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['idmesa' => $id]);


    // BEGIN BITACORA   
    //falta usuario y mensaje
        // Generar la fecha y hora actual en el formato adecuado
        $fecha_hora_actual = date('Y-m-d H:i:s');
        // Crear el mensaje concatenando los valores de $data
         $mensaje = $id;
           $bitacora_data = [
            'fechahora' => $fecha_hora_actual, 
             'usuario' => 'Brenda',
             'modulo' => 'borrar mesa de ayuda',
             'mensaje' => $mensaje      // Mensaje personalizado
          ];
         $sql1 = "INSERT INTO bitacora_web (fechahora, usuario, modulo, mensaje)
              VALUES (:fechahora, :usuario, :modulo, :mensaje)";
         $stmt1 = $pdo->prepare($sql1);
         $stmt1->execute($bitacora_data);
            // END BITACORA





                echo json_encode(['message' => 'Categoría eliminada exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    default:
        echo json_encode(['error' => 'Método no soportado']);
        break;
}
?>
