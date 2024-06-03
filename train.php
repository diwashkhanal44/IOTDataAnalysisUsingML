<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '512M'); 
require_once __DIR__ . '/vendor/autoload.php';

use Phpml\Regression\LeastSquares;
use Phpml\ModelManager;

// Load the data from the CSV file
$file_path = 'csv/Campania.csv';
$data = array_map('str_getcsv', file($file_path));
array_walk($data, function(&$a) use ($data) {
    $a = array_combine($data[0], $a);
});
array_shift($data); // Remove header row

$samples = [];
$temperature_targets = [];
$humidity_targets = [];

// Process the data
foreach ($data as $row) {
    // Extracting the Date_time
    $date_time = strtotime($row['Date_time']);
    $year = floatval(date("Y", $date_time));
    $month = floatval(date("m", $date_time));
    $day = floatval(date("d", $date_time));
    $hour = floatval(date("H", $date_time));
    $minute = floatval(date("i", $date_time));
    
    $samples[] = [$year, $month, $day, $hour, $minute];
    $temperature_targets[] = floatval($row['Temperature']);
    $humidity_targets[] = floatval($row['Relative_humidity']);
}

// Train the temperature model
$temp_model = new LeastSquares();
$temp_model->train($samples, $temperature_targets);

// Train the humidity model
$hum_model = new LeastSquares();
$hum_model->train($samples, $humidity_targets);

// Save the models
$modelManager = new ModelManager();
$modelManager->saveToFile($temp_model, 'campania_temp_model_lsq.phpml');
$modelManager->saveToFile($hum_model, 'campania_hum_model_lsq.phpml');

echo "Models trained and saved successfully.\n";
?>
