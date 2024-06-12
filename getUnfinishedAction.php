<?php
session_start();

if (!isset($_SESSION['userid'])) {
    exit(json_encode(['error' => 'No access!']));
}

require_once 'settings/conn.php';

$stmt = $conn->prepare("SELECT useractionId, payload FROM useractions WHERE userId = ? AND Done = 0");
$stmt->bind_param("i", $_SESSION['userid']);
$stmt->execute();
$result = $stmt->get_result();
$response = [];

while ($row = $result->fetch_assoc()) {
    $payload = json_decode($row['payload'], true);
    $payload['actionId'] = $row['useractionId'];
    $response[] = $payload;
}

$stmt->close();
$conn->close();

if (empty($response)) {
    exit(json_encode(['success' => true, 'data' => []]));
}

function sortActions($a, $b) {
    $order = ['levelup', 'learned', 'evolve'];

    foreach ($order as $key) {
        $aHasKey = isset($a[$key]);
        $bHasKey = isset($b[$key]);

        if ($aHasKey && !$bHasKey) {
            return -1;
        } elseif (!$aHasKey && $bHasKey) {
            return 1;
        } elseif ($aHasKey && $bHasKey) {
            return 0;
        }
    }

    return 0;
}

usort($response, 'sortActions');

echo json_encode(['success' => true, 'data' => $response]);
