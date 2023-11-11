<?php

// Retrieve the incoming JSON payload from the Alertmanager webhook
$jsonPayload = file_get_contents('php://input');
$webhookData = json_decode($jsonPayload, true);

// Get the current system time in UTC
$currentDateTime = new DateTime('now', new DateTimeZone('UTC'));

// Loop through the alerts and send API request for each alert
if (isset($webhookData['alerts']) && is_array($webhookData['alerts'])) {
    foreach ($webhookData['alerts'] as $alert) {
        // Extract the job name from the alert labels
        $jobName = isset($alert['labels']['job']) ? $alert['labels']['job'] : 'Unknown Job';

        // Extract the status of the alert (firing, resolved, etc.)
        $status = isset($alert['status']) ? $alert['status'] : 'unknown';

        if ($status === 'firing') {
            // Get the startAt date and time from the alert in ISO 8601 format
            $startAtISO8601 = isset($alert['startsAt']) ? $alert['startsAt'] : null;

            if ($startAtISO8601) {
                // Convert the startAt time to DateTime object
                $startAtDateTime = new DateTime($startAtISO8601, new DateTimeZone('UTC'));

                // Calculate the time difference in hours between startAt and current time
                $timeDifference = $startAtDateTime->diff($currentDateTime);
                $hoursDifference = $timeDifference->h + ($timeDifference->days * 24);

                if ($hoursDifference <= 1) {
                    // Set the subject for the API request
                    $subject = 'Disk Space is 90% full for ' . $jobName;

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => '<yourAPIEndpoint>',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode(array(
                            "subject" => $subject,
                            "description" => json_encode($alert), // Send individual alert as the description
                            "pipeline" => 1,
                            "pipelineStage" => 1
                        )),
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'token: <yourTokenOrAnyotherHeaderValue>'
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    echo $response;
                } else {
                    echo "StartAt time difference is more than 6 hours. API call will not be made.";
                }
            } else {
                echo "StartAt time not found in the alert data.";
            }
        } else {
            // Alert is resolved
            echo "Alert is resolved";
        }
    }
}