<?php
session_start();

if (isset($_SESSION['messages'])) {
    $messages = $_SESSION['messages'];
    if (count($messages) > 5) {
        array_shift($messages);
    }
    echo "<ul>";
    foreach ($messages as $message) {
        echo "<li>$message</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No messages available.</p>";
}
?>
