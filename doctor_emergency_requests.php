<?php
include 'config.php';

$res = mysqli_query($conn, "SELECT * FROM emergency_room_requests WHERE status = 'pending'");

echo "<h3>Incoming Emergency Calls</h3>";
while ($row = mysqli_fetch_assoc($res)) {
  echo "<p><b>{$row['request_type']}</b> request from patient<br>";
  echo "<a href='{$row['meet_link']}' target='_blank'>Join Call</a></p><hr>";
}
?>
