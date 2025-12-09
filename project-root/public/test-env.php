<?php
echo "<pre>";

echo "DB_HOST = " . var_export(getenv("DB_HOST"), true) . "\n";
echo "DB_DATABASE = " . var_export(getenv("DB_DATABASE"), true) . "\n";
echo "DB_USERNAME = " . var_export(getenv("DB_USERNAME"), true) . "\n";
echo "DB_PASSWORD = " . var_export(getenv("DB_PASSWORD"), true) . "\n";
echo "DB_PORT = " . var_export(getenv("DB_PORT"), true) . "\n";

echo "\n\nSERVER VARS:\n";
print_r($_SERVER);

echo "</pre>";
