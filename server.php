<?php
// This PHP script handles the GET request and displays the date and site number

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get the query parameters from the request
    $day = isset($_GET['day']) ? $_GET['day'] : null;
    $month = isset($_GET['month']) ? $_GET['month'] : null;
    $year = isset($_GET['year']) ? $_GET['year'] : null;
    $site = isset($_GET['site']) ? $_GET['site'] : null;

    // Check if the required fields are present
    if ($day && $month && $year && $site) {
        // Display the date and site number
        echo "Received data:\n";
        echo "Date: $day/$month/$year\n";
        echo "Site Number: $site\n";

        // Return a JSON response with predicted values (example values)
        $response = [
            'location_name' => 'Example Location',
            'min_temp' => 10,
            'max_temp' => 25,
            'min_humidity' => 30,
            'max_humidity' => 60
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
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
