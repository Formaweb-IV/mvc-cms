<?php
// echo password_hash("admin",  PASSWORD_BCRYPT, ['cost'=>12])

$value = "admin";

$hash = password_hash($value, PASSWORD_BCRYPT, ['cost'=>12]);
echo $hash;
?>