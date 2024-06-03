<?php
error_reporting(E_ALL); // Show any errors if there are any
ini_set('display_errors', '1');
require_once __DIR__ . '/vendor/autoload.php';
ini_set('memory_limit', '512M'); 
//http://iotserver.com/server.php?day=22&month=10&year=2022&site=5

use Phpml\ModelManager;

$site_models = [
    1 => ['location' => 'Campania', 'temp_model' => 'campania_temp_model_lsq.phpml', 'hum_model' => 'campania_hum_model_lsq.phpml'],
    2 => ['location' => 'Hobart', 'temp_model' => 'Hobart_temp_model_lsq.phpml', 'hum_model' => 'Hobart_hum_model_lsq.phpml'],
    3 => ['location' => 'Launceston', 'temp_model' => 'Launceston_temp_model_lsq.phpml', 'hum_model' => 'Launceston_hum_model_lsq.phpml'],
    4 => ['location' => 'Smithton', 'temp_model' => 'Smithton_temp_model_lsq.phpml', 'hum_model' => 'Smithton_hum_model_lsq.phpml'],
    5 => ['location' => 'Wynyard', 'temp_model' => 'Wynyard_temp_model_lsq.phpml', 'hum_model' => 'Wynyard_hum_model_lsq.phpml']
];

// Function to predict maximum and minimum temperature and humidity for a given date
function predict_max_min_temp_humidity($date_to_predict, $site) {
    global $site_models;
    
    $modelManager = new ModelManager();
    $temp_model = $modelManager->restoreFromFile($site_models[$site]['temp_model']);
    $hum_model = $modelManager->restoreFromFile($site_models[$site]['hum_model']);
    
    $date_start = strtotime($date_to_predict . ' 00:00:00');
    $date_end = strtotime($date_to_predict . ' 23:59:59');
    
    $max_temp = null;
    $min_temp = null;
    $max_hum = null;
    $min_hum = null;

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

        if ($min_temp === null || $predicted_temp < $min_temp) {
            $min_temp = $predicted_temp;
        }

        if ($max_hum === null || $predicted_hum > $max_hum) {
            $max_hum = $predicted_hum;
        }

        if ($min_hum === null || $predicted_hum < $min_hum) {
            $min_hum = $predicted_hum;
        }
    }

    return [
        'location_name' => $site_models[$site]['location'],
        'max_temperature' => $max_temp,
        'min_temperature' => $min_temp,
        'max_humidity' => $max_hum,
        'min_humidity' => $min_hum
    ];
}

// Function to save data to an XML file and append new entries
function save_to_xml($date, $site) {
    $file_name = 'dateAndSite.xml';
    
    if (file_exists($file_name)) {
        $xml = simplexml_load_file($file_name);
    } else {
        $xml = new SimpleXMLElement('<DateAndSite/>');
    }
    
    $entry = $xml->addChild('entry');
    $entry->addChild('date', $date);
    $entry->addChild('site', $site);
    
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    $dom->save($file_name);
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get the query parameters from the request
    $day = isset($_GET['day']) ? $_GET['day'] : null;
    $month = isset($_GET['month']) ? $_GET['month'] : null;
    $year = isset($_GET['year']) ? $_GET['year'] : null;
    $site = isset($_GET['site']) ? $_GET['site'] : null;

    // Check if the required fields are present
    if ($day && $month && $year && $site) {
        // Format the date
        $date_to_predict = "$year-$month-$day";

        // Predict and return the results
        $prediction = predict_max_min_temp_humidity($date_to_predict, $site);
        
        // Save the results to an XML file
        save_to_xml($date_to_predict, $site);

        // Output the results
        echo json_encode($prediction);
    } else {
        // If the required fields are not present, return an error response
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. Missing day, month, year, or site fields.']);
    }
} else {
    // If the request method is not GET, return an error response
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method. Only GET is allowed.']);
}
?>
