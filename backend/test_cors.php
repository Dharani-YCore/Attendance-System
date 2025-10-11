<?php
include_once 'cors.php';

echo json_encode(array(
    "success" => true,
    "message" => "CORS is working correctly!",
    "timestamp" => date('Y-m-d H:i:s')
));
?>
