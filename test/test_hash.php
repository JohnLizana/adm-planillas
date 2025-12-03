<?php
$password_plana = '123456';
// Este es el hash que DEBE estar en tu base de datos:
$hash_almacenado = '$2y$10$Yq79j8I.J6vR.N0E0eOqF.v6Q9tT3M1u/iA2J2J2J2J2J2J2J2J2'; 

if (password_verify($password_plana, $hash_almacenado)) {
    echo "✅ El hash SÍ coincide con la contraseña '123456'. El problema NO es la contraseña en la BD.";
} else {
    echo "❌ ERROR: El hash NO coincide. ¡Debes volver a insertar los usuarios!";
}

// Opcional: Genera un nuevo hash por si el anterior fue corrompido
echo "<br><br>Nuevo hash generado para '123456': " . password_hash($password_plana, PASSWORD_BCRYPT);
?>