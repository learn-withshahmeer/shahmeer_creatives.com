<?php
header('Content-Type: application/json'); // Set header to indicate JSON response

// Initialize response array
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Include PHPMailer classes
// In a real project, you would typically install PHPMailer via Composer:
// composer require phpmailer/phpmailer
// and then use: require 'vendor/autoload.php';
// For this example, we'll use direct includes (assuming files are available).
// You'll need to download PHPMailer and place these files in your server.
// For testing, you can place them in the same directory as this script,
// or a 'phpmailer' subdirectory.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust these paths if PHPMailer is in a different directory
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


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

    // Create an instance of PHPMailer
    $mail = new PHPMailer(true); // Passing true enables exceptions

    try {
        // Server settings for SMTP
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.your-email-provider.com';       // IMPORTANT: Set the SMTP server to send through (e.g., 'smtp.gmail.com' for Gmail)
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'your-smtp-username@example.com';     // IMPORTANT: SMTP username (e.g., your full email address)
        $mail->Password   = 'your-smtp-password';                 // IMPORTANT: SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = 587;                                    // TCP port to connect to (e.g., 587 for STARTTLS, 465 for SMTPS)

        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'Shahmeer Creatives Contact Form'); // IMPORTANT: Sender email and name. Must be a valid email on your domain for many hosts.
        $mail->addAddress('your-recipient-email@example.com', 'Recipient Name'); // IMPORTANT: Add a recipient email address
        $mail->addReplyTo($email, $name); // Set the reply-to address from the user's input

        // Content
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = "New Contact Form Submission: " . $subject;
        
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

        $mail->Body = $email_body;

        $mail->send();
        $response['success'] = true;
        $response['message'] = 'Your message has been sent successfully!';
    } catch (Exception $e) {
        $response['message'] = 'Failed to send your message. Mailer Error: ' . $mail->ErrorInfo;
        // Log the detailed error for debugging
        error_log("Contact Form Mailer Error: " . $mail->ErrorInfo);
    }

} else {
    $response['message'] = 'Invalid request method.';
}

// Send the JSON response back to the client
echo json_encode($response);
?>
