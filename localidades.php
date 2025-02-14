<?php
$host = 'localhost';  
$user = 'usuario';    
$password = ''; 
$dbname = 'localidades'; 

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$sql = "SELECT * FROM localidades ORDER BY nombre ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {

    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['idlocalidad'] . " - Nombre: " . $row['nombre'] . " - Precio Envio: " . $row['precio_envio'] . "<br>";
    }
} else {
    echo "No se encontraron localidades.";
}

$conn->close();
?>