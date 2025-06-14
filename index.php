<?php

/**
 * Function to send an email using Sendmail transport
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email message body
 * @param string $from Sender email address (optional)
 * @return array ['success' => bool, 'error' => string] Result of the operation
 */
function sendEmail($to, $subject, $message, $from = '') {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Log the attempt
    error_log("Attempting to send email to: " . $to);
    
    // Validate email addresses
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid recipient email: " . $to);
        return ['success' => false, 'error' => 'Invalid recipient email address'];
    }
    if (!empty($from) && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid sender email: " . $from);
        return ['success' => false, 'error' => 'Invalid sender email address'];
    }
    
    // Set headers
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    
    if (!empty($from)) {
        $headers[] = 'From: ' . $from;
    }
    
    // Combine headers into a single string
    $headersString = implode("\r\n", $headers);
    
    // Configure Sendmail
    $sendmailPath = '/usr/sbin/sendmail -bs';
    error_log("Using Sendmail path: " . $sendmailPath);
    
    // Create the email content
    $emailContent = "To: $to\r\n";
    $emailContent .= "Subject: $subject\r\n";
    $emailContent .= $headersString . "\r\n\r\n";
    $emailContent .= $message;
    
    // Open a pipe to Sendmail
    $handle = popen($sendmailPath, 'w');
    if ($handle === false) {
        error_log("Failed to open Sendmail process");
        return ['success' => false, 'error' => 'Failed to open Sendmail process. Check if Sendmail is installed and accessible.'];
    }
    
    // Write the email content to Sendmail
    $result = fwrite($handle, $emailContent);
    if ($result === false) {
        error_log("Failed to write to Sendmail process");
        pclose($handle);
        return ['success' => false, 'error' => 'Failed to write to Sendmail process. Check Sendmail permissions.'];
    }
    
    // Close the pipe and check the result
    $closeResult = pclose($handle);
    if ($closeResult !== 0) {
        error_log("Sendmail process failed with code: " . $closeResult);
        return ['success' => false, 'error' => 'Sendmail process failed with code: ' . $closeResult . '. Check Sendmail configuration.'];
    }
    
    error_log("Email sent successfully to: " . $to);
    return ['success' => true, 'error' => ''];
}

// Example usage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $from = $_POST['from'] ?? '';
    
    if (empty($to) || empty($subject) || empty($message)) {
        echo "Error: Please fill in all required fields.";
    } else {
        $result = sendEmail($to, $subject, $message, $from);
        if ($result['success']) {
            echo "Email sent successfully!";
        } else {
            echo "Failed to send email: " . htmlspecialchars($result['error']);
            echo "<br>Please check the error logs for more details.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="email"], input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            height: 150px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h2>Send Email</h2>
    <form method="POST">
        <div class="form-group">
            <label for="to">To:</label>
            <input type="email" id="to" name="to" required>
        </div>
        
        <div class="form-group">
            <label for="from">From (optional):</label>
            <input type="email" id="from" name="from">
        </div>
        
        <div class="form-group">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>
        </div>
        
        <div class="form-group">
            <label for="message">Message:</label>
            <textarea id="message" name="message" required></textarea>
        </div>
        
        <button type="submit">Send Email</button>
    </form>
</body>
</html> 