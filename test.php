<?php
error_reporting(E_ALL); // Show any errors if there are any
ini_set('display_errors', '1');
require_once __DIR__ . '/vendor/autoload.php';

use Phpml\ModelManager;

// Load the models
$modelManager = new ModelManager();
$temp_model = $modelManager->restoreFromFile('campania_temp_model_lsq.phpml');
$hum_model = $modelManager->restoreFromFile('campania_hum_model_lsq.phpml');

// Function to predict maximum temperature and humidity for a given date
function predict_max_temp_humidity($date_to_predict) {
    global $temp_model, $hum_model;
    
    $date_start = strtotime($date_to_predict . ' 00:00:00');
    $date_end = strtotime($date_to_predict . ' 23:59:59');
    
    $max_temp = null;
    $max_hum = null;

    for ($timestamp = $date_start; $timestamp <= $date_end; $timestamp += 1800) {  // Every 30 minutes
        $year = floatval(date("Y", $timestamp));
        $month = floatval(date("m", $timestamp));
        $day = floatval(date("d", $timestamp));
        $hour = floatval(date("H", $timestamp));
        $minute = floatval(date("i", $timestamp));

        $input_features = [$year, $month, $day, $hour, $minute];

        // Predict temperature and humidity
        $predicted_temp = $temp_model->predict($input_features);
        $predicted_hum = $hum_model->predict($input_features);

        if ($max_temp === null || $predicted_temp > $max_temp) {
            $max_temp = $predicted_temp;
        }

        if ($max_hum === null || $predicted_hum > $max_hum) {
            $max_hum = $predicted_hum;
        }
    }

    return [
        'max_temperature' => $max_temp,
        'max_humidity' => $max_hum
    ];
}

// Example usage
$date_to_predict = '2022-01-01'; // Replace with the desired date
$prediction = predict_max_temp_humidity($date_to_predict);

echo "Max Predicted Temperature: " . $prediction['max_temperature'] . PHP_EOL;
echo "Max Predicted Humidity: " . $prediction['max_humidity'] . PHP_EOL;
?>

