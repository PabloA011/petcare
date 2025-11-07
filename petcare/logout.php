<?php

session_start();

unset($_SESSION['id_veterinario']);
unset($_SESSION['nombre_veterinario']);
session_destroy();
header('Location: login.php');

exit;

?>