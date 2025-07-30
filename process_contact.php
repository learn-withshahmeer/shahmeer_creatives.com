<?php
header('Content-Type: application/json'); // Set header to indicate JSON response

// Initialize response array
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $services = $_POST['services'] ?? []; // Get array of services, default to empty array if not set

    // Validate required fields
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($subject) || empty($message)) {
        $response['message'] = 'Please fill in all required fields and provide a valid email address.';
        echo json_encode($response);
        exit;
    }

    // Prepare email details
    $to = 'your-email@example.com'; // IMPORTANT: Replace with the actual email address where you want to receive messages
    $email_subject = "New Contact Form Submission: " . $subject;

    // Build the email body
    $email_body = "You have received a new message from your website contact form.\n\n";
    $email_body .= "Name: " . $name . "\n";
    $email_body .= "Email: " . $email . "\n";
    if (!empty($phone)) {
        $email_body .= "Phone: " . $phone . "\n";
    }
    $email_body .= "Subject: " . $subject . "\n";
    
    if (!empty($services)) {
        $email_body .= "Services Interested In: " . implode(", ", $services) . "\n";
    }
    $email_body .= "Message:\n" . $message . "\n";

    // Set email headers
    $headers = "From: webmaster@example.com\r\n"; // IMPORTANT: Replace with a valid sender email for your domain
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Attempt to send the email
    if (mail($to, $email_subject, $email_body, $headers)) {
        $response['success'] = true;
        $response['message'] = 'Your message has been sent successfully!';
    } else {
        $response['message'] = 'Failed to send your message. Please try again later.';
        // This line will now log detailed mail errors to your server's PHP error log:
        error_log("Mail failed to send from contact form: " . error_get_last()['message']);
    }

} else {
    $response['message'] = 'Invalid request method.';
}

// Send the JSON response back to the client
echo json_encode($response);
?>
