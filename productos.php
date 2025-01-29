<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite solicitudes desde cualquier dominio
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$idsubcategoria = $_GET['idsubcategoria'] ?? null;

// Paginación
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($endpoint === 'producto') {
            $search = $_GET['search'] ?? '';
            
            // Construir la consulta con filtros opcionales
            $whereClause = "WHERE descripcion LIKE :search";
            if ($idsubcategoria) {
                $whereClause .= " AND idsubcategoria = :idsubcategoria";
            }
            
            // Obtener el total de registros
            $countSql = "SELECT COUNT(*) as total FROM productos_web $whereClause";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->bindValue(':search', "%$search%");
            if ($idsubcategoria) {
                $countStmt->bindValue(':idsubcategoria', (int)$idsubcategoria, PDO::PARAM_INT);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calcular páginas totales
            $totalPages = ceil($total / $limit);

            // Obtener los registros
            $sql = "SELECT idproducto, idcategoria, idsubcategoria, idproveedor, descripcion, precioventa, preciocosto, deposito, ubicacion, stockmin, stock, stockmax, descripcioncompleta, codigoArticulo, estado, nivel, imagen
                    FROM productos_web 
                    $whereClause 
                    LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            if ($idsubcategoria) {
                $stmt->bindValue(':idsubcategoria', (int)$idsubcategoria, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Respuesta JSON
            echo json_encode([
                'producto' => $result,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => (int)$page,
            ]);
        }
        break;

    default:
        echo json_encode(['error' => 'Método no soportado']);
        break;
}
