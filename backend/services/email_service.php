<?php
require_once __DIR__ . '/../config/env.php';

class EmailService {
    private $apiKey;
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpSecurity;
    private $smtpUsername;
    private $smtpPassword;

    public function __construct() {
        $this->apiKey = env('BREVO_API_KEY');
        $this->fromEmail = env('BREVO_FROM_EMAIL', 'noreply@yourdomain.com');
        $this->fromName = env('BREVO_FROM_NAME', 'Smart Attendance System');
        // SMTP (fallback)
        $this->smtpHost = env('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
        $this->smtpPort = (int)env('BREVO_SMTP_PORT', 587);
        $this->smtpSecurity = strtolower(env('BREVO_SMTP_SECURITY', 'tls'));
        $this->smtpUsername = env('BREVO_SMTP_USERNAME');
        $this->smtpPassword = env('BREVO_SMTP_PASSWORD');
    }

    public function sendOTP($toEmail, $toName, $otp) {

        $payload = [
            'sender' => [
                'email' => $this->fromEmail,
                'name'  => $this->fromName,
            ],
            'to' => [[
                'email' => $toEmail,
                'name'  => $toName,
            ]],
            'subject' => 'Your OTP for Password Recovery',
            'htmlContent' => $this->getOTPEmailTemplate($otp, $toName),
        ];

        // Try Brevo REST API first
        if ($this->apiKey) {
            $apiResult = $this->sendEmailBrevo($payload);
            if ($apiResult['success'] === true) {
                return $apiResult;
            }
            // If unauthorized or forbidden, fall back to SMTP if configured
            if (isset($apiResult['http']) && in_array($apiResult['http'], [401, 403])) {
                $smtpResult = $this->trySMTP($toEmail, $toName, 'Your OTP for Password Recovery', $this->getOTPEmailTemplate($otp, $toName));
                if ($smtpResult['success'] === true) return $smtpResult;
                return $smtpResult; // return SMTP error details if fallback also fails
            }
            return $apiResult;
        }
        // No API key -> try SMTP
        return $this->trySMTP($toEmail, $toName, 'Your OTP for Password Recovery', $this->getOTPEmailTemplate($otp, $toName));
    }

    public function sendWelcomeEmail($toEmail, $toName) {

        $payload = [
            'sender' => [
                'email' => $this->fromEmail,
                'name'  => $this->fromName,
            ],
            'to' => [[
                'email' => $toEmail,
                'name'  => $toName,
            ]],
            'subject' => 'Welcome to Smart Attendance System',
            'htmlContent' => $this->getWelcomeEmailTemplate($toName),
        ];

        if ($this->apiKey) {
            $apiResult = $this->sendEmailBrevo($payload);
            if ($apiResult['success'] === true) {
                return $apiResult;
            }
            if (isset($apiResult['http']) && in_array($apiResult['http'], [401, 403])) {
                $smtpResult = $this->trySMTP($toEmail, $toName, 'Welcome to Smart Attendance System', $this->getWelcomeEmailTemplate($toName));
                if ($smtpResult['success'] === true) return $smtpResult;
                return $smtpResult;
            }
            return $apiResult;
        }
        return $this->trySMTP($toEmail, $toName, 'Welcome to Smart Attendance System', $this->getWelcomeEmailTemplate($toName));
    }

    private function sendEmailBrevo($emailData) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $this->apiKey,
            'accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // It's better to point to a certificate bundle than to disable verification
        // but for local development, this is a common workaround.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'message' => 'cURL Error: ' . $error_msg];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'Email sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email. HTTP Status: ' . $httpCode, 'http' => $httpCode, 'response' => $response];
        }
    }

    private function trySMTP($toEmail, $toName, $subject, $html) {
        if (!$this->smtpHost || !$this->smtpPort || !$this->smtpUsername || !$this->smtpPassword) {
            return ['success' => false, 'message' => 'SMTP configuration missing'];
        }

        return $this->sendEmailSMTP($toEmail, $toName, $subject, $html);
    }

    private function sendEmailSMTP($toEmail, $toName, $subject, $html) {
        $timeout = 30;
        $errno = 0; $errstr = '';
        $host = $this->smtpHost;
        if ($this->smtpSecurity === 'ssl') {
            $host = 'ssl://' . $host;
        }
        $fp = fsockopen($host, $this->smtpPort, $errno, $errstr, $timeout);
        if (!$fp) {
            return ['success' => false, 'message' => 'SMTP connect failed: ' . $errstr];
        }

        $read = function() use ($fp) {
            $data = '';
            while ($str = fgets($fp, 515)) {
                $data .= $str;
                if (isset($str[3]) && $str[3] === ' ') break;
            }
            return $data;
        };
        $write = function($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

        $resp = $read();
        if (strpos($resp, '220') !== 0) return ['success' => false, 'message' => 'SMTP banner error: ' . trim($resp)];

        $hostName = gethostname() ?: 'localhost';
        $write('EHLO ' . $hostName);
        $resp = $read();
        if (strpos($resp, '250') !== 0) return ['success' => false, 'message' => 'EHLO failed: ' . trim($resp)];

        if ($this->smtpSecurity === 'tls') {
            $write('STARTTLS');
            $resp = $read();
            if (strpos($resp, '220') !== 0) return ['success' => false, 'message' => 'STARTTLS failed: ' . trim($resp)];
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                return ['success' => false, 'message' => 'TLS handshake failed'];
            }
            $write('EHLO ' . $hostName);
            $resp = $read();
            if (strpos($resp, '250') !== 0) return ['success' => false, 'message' => 'EHLO after TLS failed: ' . trim($resp)];
        }

        // AUTH LOGIN with fallback to PLAIN
        $write('AUTH LOGIN');
        $resp = $read();
        if (strpos($resp, '334') !== 0) {
            $plain = base64_encode("\0" . $this->smtpUsername . "\0" . $this->smtpPassword);
            $write('AUTH PLAIN ' . $plain);
            $resp = $read();
            if (strpos($resp, '235') !== 0) return ['success' => false, 'message' => 'AUTH failed: ' . trim($resp)];
        } else {
            $write(base64_encode($this->smtpUsername));
            $resp = $read();
            if (strpos($resp, '334') !== 0) return ['success' => false, 'message' => 'Username not accepted: ' . trim($resp)];
            $write(base64_encode($this->smtpPassword));
            $resp = $read();
            if (strpos($resp, '235') !== 0) {
                $plain = base64_encode("\0" . $this->smtpUsername . "\0" . $this->smtpPassword);
                $write('AUTH PLAIN ' . $plain);
                $resp = $read();
                if (strpos($resp, '235') !== 0) return ['success' => false, 'message' => 'Password not accepted: ' . trim($resp)];
            }
        }

        $from = $this->fromEmail ?: $this->smtpUsername;
        $write('MAIL FROM: <' . $from . '>');
        $resp = $read();
        if (strpos($resp, '250') !== 0) return ['success' => false, 'message' => 'MAIL FROM failed: ' . trim($resp)];

        $write('RCPT TO: <' . $toEmail . '>');
        $resp = $read();
        if (strpos($resp, '250') !== 0 && strpos($resp, '251') !== 0) return ['success' => false, 'message' => 'RCPT TO failed: ' . trim($resp)];

        $write('DATA');
        $resp = $read();
        if (strpos($resp, '354') !== 0) return ['success' => false, 'message' => 'DATA not accepted: ' . trim($resp)];

        $headers = [];
        $headers[] = 'From: ' . $this->encodeAddress($from, $this->fromName);
        $headers[] = 'To: ' . $this->encodeAddress($toEmail, $toName);
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';

        $body = rtrim(chunk_split(base64_encode($html)));
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        $write($message);
        $resp = $read();
        if (strpos($resp, '250') !== 0) return ['success' => false, 'message' => 'Message not accepted: ' . trim($resp)];

        $write('QUIT');
        fclose($fp);
        return ['success' => true, 'message' => 'Email sent successfully'];
    }

    private function encodeHeader($str) {
        // Encode non-ASCII in headers
        if (preg_match('/[^\x20-\x7E]/', $str)) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }

    private function encodeAddress($email, $name = null) {
        if ($name && trim($name) !== '') {
            return $this->encodeHeader($name) . ' <' . $email . '>';
        }
        return '<' . $email . '>';
    }

    private function getOTPEmailTemplate($otp, $name) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>OTP Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #00bcd4, #e0f7fa); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #fff; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .otp-box { background: #f8f9fa; border: 2px dashed #00bcd4; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #00bcd4; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='color: white; margin: 0;'>Smart Attendance System</h1>
                    <p style='color: white; margin: 10px 0 0 0;'>Password Recovery</p>
                </div>
                <div class='content'>
                    <h2>Hello {$name},</h2>
                    <p>You requested to reset your password. Please use the following OTP (One-Time Password) to proceed:</p>
                    
                    <div class='otp-box'>
                        <p style='margin: 0; font-size: 16px;'>Your OTP Code:</p>
                        <div class='otp-code'>{$otp}</div>
                    </div>
                    
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This OTP is valid for 10 minutes only</li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this, please ignore this email</li>
                    </ul>
                    
                    <p>If you have any questions, please contact our support team.</p>
                    
                    <p>Best regards,<br>Smart Attendance System Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getWelcomeEmailTemplate($name) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to Smart Attendance System</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #00bcd4, #e0f7fa); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #fff; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .feature-box { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #00bcd4; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='color: white; margin: 0;'>Welcome to Smart Attendance System!</h1>
                    <p style='color: white; margin: 10px 0 0 0;'>Your account has been created successfully</p>
                </div>
                <div class='content'>
                    <h2>Hello {$name},</h2>
                    <p>Welcome to the Smart Attendance System! We're excited to have you on board.</p>
                    
                    <h3>What you can do:</h3>
                    <div class='feature-box'>
                        <strong>📱 QR Code Scanning</strong><br>
                        Mark your attendance quickly using QR code scanner
                    </div>
                    <div class='feature-box'>
                        <strong>📊 Attendance Reports</strong><br>
                        View detailed reports and analytics of your attendance
                    </div>
                    <div class='feature-box'>
                        <strong>📈 Performance Tracking</strong><br>
                        Track your attendance percentage and performance
                    </div>
                    <div class='feature-box'>
                        <strong>👤 Profile Management</strong><br>
                        Manage your profile and account settings
                    </div>
                    
                    <p>To get started, simply log in to your account and explore the features.</p>
                    
                    <p>If you have any questions or need assistance, feel free to contact our support team.</p>
                    
                    <p>Best regards,<br>Smart Attendance System Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
