<?php
declare(strict_types=1);
use App\Config\Database;



require_once __DIR__ . '/../vendor/autoload.php';

session_start();

require_once __DIR__ . '/../routes/web.php';



try{
$db = Database::connect();
echo "Database Successfully Connected";
}
catch(PDOException $e){
echo "Database connection failed". $e;
}

?>