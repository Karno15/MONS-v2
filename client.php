<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Client</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <input type="text" id="message" placeholder="Enter message">
    <button id="sendBtn">Send Message</button>
    
    <script>
        $(document).ready(function() {
            const socket = new WebSocket('ws://127.0.0.1:8080');
            // Connection opened
            socket.addEventListener('open', function (event) {
                console.log('Connected to WebSocket server');
            });

            // Connection closed
            socket.addEventListener('close', function (event) {
                console.log('Disconnected from WebSocket server');
            });

            // Listen for messages
            socket.addEventListener('message', function (event) {
                // Handle incoming messages
                console.log('Received message:', event.data);
            });

            // Send a message when the button is clicked
            $('#sendBtn').click(function() {
                const message = $('#message').val();
                socket.send(message);
                $('#message').val(''); // Clear the input field
            });
        });
    </script>
</body>
</html>
