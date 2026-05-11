<?php
session_start(); // Access the current session
session_unset(); // Remove all session variables (name, id, etc.)
session_destroy(); // Completely destroy the session on the server

// Redirect back to the home page as a guest
header("Location: index.php");
exit();
?>