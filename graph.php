<?php
error_reporting(E_ALL); 
ini_set('display_errors', '1');
ini_set('memory_limit', '512M'); 
require_once __DIR__ . '/vendor/autoload.php';

// Define the site models
$site_models = [
    1 => ['location' => 'Campania', 'filename' => 'csv/Campania.csv'],
    2 => ['location' => 'Hobart', 'filename' => 'csv/Hobart.csv'],
    3 => ['location' => 'Launceston', 'filename' => 'csv/Launceston.csv'],
    4 => ['location' => 'Smithton', 'filename' => 'csv/Smithton.csv'],
    5 => ['location' => 'Wynyard', 'filename' => 'csv/Wynyard.csv']
];

// Function to read selected day and site from the XML file
function get_selected_day_and_site($xml_file) {
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        $latest_entry = $xml->entry[count($xml->entry) - 1];
        $date = (string)$latest_entry->date;
        $site = (int)$latest_entry->site;
        return [$date, $site];
    } else {
        throw new Exception("XML file not found.");
    }
}

// Function to calculate average humidity and temperature for each half-hour increment
function calculate_averages($csv_file, $month, $day) {
    $data = array_map('str_getcsv', file($csv_file));
    array_walk($data, function(&$a) use ($data) {
        $a = array_combine($data[0], $a);
    });
    array_shift($data); // Remove header row

    $humidity_sums = array_fill(0, 48, 0);
    $temperature_sums = array_fill(0, 48, 0);
    $counts = array_fill(0, 48, 0);

    foreach ($data as $row) {
        $date_time = strtotime($row['Date_time']);
        if (date('m', $date_time) == $month && date('d', $date_time) == $day) {
            $hour = (int)date('H', $date_time);
            $minute = (int)date('i', $date_time);
            $index = $hour * 2 + ($minute >= 30 ? 1 : 0);
            
            $humidity_sums[$index] += floatval($row['Relative_humidity']);
            $temperature_sums[$index] += floatval($row['Temperature']);
            $counts[$index] += 1;
        }
    }

    $average_humidities = array_map(function($sum, $count) {
        return $count ? $sum / $count : 0;
    }, $humidity_sums, $counts);

    $average_temperatures = array_map(function($sum, $count) {
        return $count ? $sum / $count : 0;
    }, $temperature_sums, $counts);

    return [$average_humidities, $average_temperatures];
}

try {
    list($selected_date, $selected_site) = get_selected_day_and_site('dateAndSite.xml');
    list($year, $month, $day) = explode('-', $selected_date);

    $csv_file = $site_models[$selected_site]['filename'];
    list($average_humidities, $average_temperatures) = calculate_averages($csv_file, $month, $day);

    $data = [
        'average_humidities' => $average_humidities,
        'average_temperatures' => $average_temperatures,
        'location' => $site_models[$selected_site]['location'],
        'date' => "$month-$day"
    ];
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temperature and Humidity Graphs</title>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
</head>
<body>
    <h1>Temperature and Humidity Graphs for <?php echo "{$data['date']} at {$data['location']}"; ?></h1>
    <button onclick="showGraph('temperature')">Show Temperature Graph</button>
    <button onclick="showGraph('humidity')">Show Humidity Graph</button>

    <div id="temperatureGraph" style="height: 370px; width: 100%; display:none;">
        <div id="temperatureChart" style="height: 370px; width: 100%;"></div>
    </div>
    <div id="humidityGraph" style="height: 370px; width: 100%; display:none;">
        <div id="humidityChart" style="height: 370px; width: 100%;"></div>
    </div>

    <script>
        const data = <?php echo json_encode($data); ?>;
        const timeLabels = Array.from({length: 48}, (_, i) => {
            const hour = String(Math.floor(i / 2)).padStart(2, '0');
            const minute = i % 2 === 0 ? '00' : '30';
            return `${hour}:${minute}`;
        });

        const averageHumidities = data.average_humidities;
        const averageTemperatures = data.average_temperatures;

        const temperatureConfig = {
            animationEnabled: true,
            theme: "light2",
            title: {
                text: "Average Temperature (°C)"
            },
            axisX: {
                title: "Time of Day",
                interval: 1,
                intervalType: "hour",
                valueFormatString: "HH:mm"
            },
            axisY: {
                title: "Temperature (°C)"
            },
            data: [{
                type: "line",
                xValueFormatString: "HH:mm",
                yValueFormatString: "#,##0.## °C",
                dataPoints: timeLabels.map((label, index) => ({ label, y: averageTemperatures[index] }))
            }]
        };

        const humidityConfig = {
            animationEnabled: true,
            theme: "light2",
            title: {
                text: "Average Humidity (%)"
            },
            axisX: {
                title: "Time of Day",
                interval: 1,
                intervalType: "hour",
                valueFormatString: "HH:mm"
            },
            axisY: {
                title: "Humidity (%)"
            },
            data: [{
                type: "line",
                xValueFormatString: "HH:mm",
                yValueFormatString: "#,##0.## %",
                dataPoints: timeLabels.map((label, index) => ({ label, y: averageHumidities[index] }))
            }]
        };

        const temperatureChart = new CanvasJS.Chart("temperatureChart", temperatureConfig);
        const humidityChart = new CanvasJS.Chart("humidityChart", humidityConfig);

        function showGraph(graph) {
            document.getElementById('temperatureGraph').style.display = 'none';
            document.getElementById('humidityGraph').style.display = 'none';

            if (graph === 'temperature') {
                document.getElementById('temperatureGraph').style.display = 'block';
                temperatureChart.render();
            } else if (graph === 'humidity') {
                document.getElementById('humidityGraph').style.display = 'block';
                humidityChart.render();
            }
        }

        // Show the temperature graph by default
        showGraph('temperature');
    </script>
</body>
</html>
