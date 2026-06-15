<?php

// $host = "sql210.infinityfree.com"; 
//  $username = "if0_39459714"; 
//  $password = "jeycyn03082007";
//  $database = "if0_39459714_multiversecare"; 

//  $conn = new mysqli($host, $username, $password, $database);

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

//  $conn->set_charset("utf8");




$host = "localhost";           
$username = "root";            
$password = "";                
$database = "doctorscare";    

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8");
  ?>
