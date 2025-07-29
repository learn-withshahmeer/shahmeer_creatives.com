<?php
// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin (for development)
                                        // In production, change * to your website's domain (e.g., https://yourshahmeercreatives.com)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Email Configuration ---
// IMPORTANT: Replace with your actual email address and recipient.
// For production, consider using a dedicated SMTP library like PHPMailer for robust email sending.
$recipient_email = "mallahshahmeer10@gmail.com"; // Your email where you want to receive messages
$sender_email_for_mail_function = "no-reply@yourdomain.com"; // This can be a generic email, or configured on your web server

// --- Database Configuration ---
$db_file = 'submissions.db';

// --- reCAPTCHA Configuration ---
// IMPORTANT: Replace with your actual reCAPTCHA Secret Key
$recaptcha_secret_key = "6LfM5JIrAAAAAK51wa4SIXtJpCbdCtx7lk1ZU_pu"; // Your reCAPTCHA secret key

// --- Initialize Database ---
function init_db($db_file) {
    try {
        $db = new PDO("sqlite:$db_file");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("CREATE TABLE IF NOT EXISTS contact_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            subject TEXT,
            message TEXT NOT NULL,
            submission_time DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        return $db;
    } catch (PDOException $e) {
        // Log the error (e.g., to a file, not to the user)
        error_log("Database initialization error: " . $e->getMessage());
        return null;
    }
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// --- Input Validation ---
if (empty($data['name']) || empty($data['email']) || empty($data['subject']) || empty($data['message']) || empty($data['recaptchaToken'])) {
    echo json_encode(['success' => false, 'message' => 'All fields, including reCAPTCHA, are required.']);
    exit();
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit();
}

$name = htmlspecialchars(strip_tags($data['name']));
$email = htmlspecialchars(strip_tags($data['email']));
$subject = htmlspecialchars(strip_tags($data['subject']));
$message = htmlspecialchars(strip_tags($data['message']));
$recaptcha_token = $data['recaptchaToken']; // Get the reCAPTCHA token from the frontend

// --- reCAPTCHA Verification ---
$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_response = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret_key . '&response=' . $recaptcha_token);
$recaptcha_data = json_decode($recaptcha_response);

// Check if reCAPTCHA verification was successful
if (!$recaptcha_data->success) {
    // Optionally, you can log $recaptcha_data->{'error-codes'} for debugging
    error_log("reCAPTCHA verification failed: " . json_encode($recaptcha_data->{'error-codes'}));
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again.']);
    exit();
}

// Optionally, you can check the score for reCAPTCHA v3
// if ($recaptcha_data->score < 0.5) { // Adjust score threshold as needed
//     error_log("reCAPTCHA score too low: " . $recaptcha_data->score);
//     echo json_encode(['success' => false, 'message' => 'Bot detected by reCAPTCHA. Please try again.']);
//     exit();
// }


try {
    // --- Store in Database ---
    $db = init_db($db_file);
    if ($db) {
        $stmt = $db->prepare("INSERT INTO contact_submissions (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
    } else {
        throw new Exception("Could not initialize database.");
    }

    // --- Send Email ---
    $email_subject = "New Contact Form Submission: " . $subject;
    $email_body = "Name: $name\n";
    $email_body .= "Email: $email\n";
    $email_body .= "Subject: $subject\n\n";
    $email_body .= "Message:\n$message\n\n";
    $email_body .= "This submission has also been saved to your database.";

    // Headers for the mail function
    $headers = "From: " . $sender_email_for_mail_function . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n"; // Set reply-to to sender's email
    $headers .= "Content-type: text/plain; charset=UTF-8\r\n";

    // Use @mail to suppress errors, handle them below
    if (@mail($recipient_email, $email_subject, $email_body, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully and saved to our records!']);
    } else {
        // If mail() fails, it's often due to server configuration. Still, database save is a success.
        error_log("Email sending failed. Recipient: $recipient_email, Subject: $email_subject");
        echo json_encode(['success' => true, 'message' => 'Your message has been saved, but there was an issue sending the email notification.']);
    }

} catch (Exception $e) {
    // Log the error but provide a generic message to the user
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'There was an error processing your request. Please try again later.']);
}

?>
