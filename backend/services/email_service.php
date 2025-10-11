<?php
require_once __DIR__ . '/../config/env.php';

class EmailService {
    private $apiKey;
    private $fromEmail;
    private $fromName;

    public function __construct() {
        $this->apiKey = env('SENDGRID_API_KEY');
        $this->fromEmail = env('SENDGRID_FROM_EMAIL', 'noreply@yourdomain.com');
        $this->fromName = env('SENDGRID_FROM_NAME', 'Smart Attendance System');
    }

    public function sendOTP($toEmail, $toName, $otp) {
        if (!$this->apiKey) {
            return ['success' => false, 'message' => 'SendGrid API key not configured'];
        }

        $emailData = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $toEmail,
                            'name' => $toName
                        ]
                    ],
                    'subject' => 'Your OTP for Password Recovery'
                ]
            ],
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $this->getOTPEmailTemplate($otp, $toName)
                ]
            ]
        ];

        return $this->sendEmail($emailData);
    }

    public function sendWelcomeEmail($toEmail, $toName) {
        if (!$this->apiKey) {
            return ['success' => false, 'message' => 'SendGrid API key not configured'];
        }

        $emailData = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $toEmail,
                            'name' => $toName
                        ]
                    ],
                    'subject' => 'Welcome to Smart Attendance System'
                ]
            ],
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $this->getWelcomeEmailTemplate($toName)
                ]
            ]
        ];

        return $this->sendEmail($emailData);
    }

    private function sendEmail($emailData) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
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
            return ['success' => false, 'message' => 'Failed to send email. HTTP Status: ' . $httpCode, 'response' => $response];
        }
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
