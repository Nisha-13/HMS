<?php

namespace App\Services;

class PatientVerificationService
{
    /**
     * Common disposable / temporary email domains to reject.
     */
    protected static array $disposableEmailDomains = [
        'mailinator.com',
        'tempmail.com',
        '10minutemail.com',
        'guerrillamail.com',
        'throwawaymail.com',
        'yopmail.com',
        'trashmail.com',
        'getairmail.com',
        'dispostable.com',
        'sharklasers.com',
        'temp-mail.org',
        'fakemailgenerator.com',
        'emailondeck.com',
    ];

    /**
     * Valid Pakistani mobile network prefixes.
     */
    protected static array $validNetworkPrefixes = [
        // Jazz / Mobilink / Warid
        '0300', '0301', '0302', '0303', '0304', '0305', '0306', '0307', '0308', '0309',
        '0320', '0321', '0322', '0323', '0324', '0325',
        // Zong
        '0310', '0311', '0312', '0313', '0314', '0315', '0316', '0317', '0318',
        // Ufone
        '0330', '0331', '0332', '0333', '0334', '0335', '0336', '0337',
        // Telenor
        '0340', '0341', '0342', '0343', '0344', '0345', '0346', '0347', '0348', '0349',
        // Special Communication Organization (SCOM - AJK/GB)
        '0355',
    ];

    /**
     * Validate and normalize a Pakistani CNIC number.
     * Format: XXXXX-XXXXXXX-X (13 digits)
     */
    public static function validateCnic(?string $cnic): array
    {
        if (empty($cnic)) {
            return [
                'valid' => false,
                'error' => 'CNIC / National Identity Card number is required.',
                'normalized' => null,
            ];
        }

        // Clean any accidental whitespace
        $cleaned = trim($cnic);

        // Normalize if user provided 13 contiguous digits without hyphens
        if (preg_match('/^\d{13}$/', $cleaned)) {
            $cleaned = substr($cleaned, 0, 5) . '-' . substr($cleaned, 5, 7) . '-' . substr($cleaned, 12, 1);
        }

        // 1. Strict Format Check
        if (!preg_match('/^\d{5}-\d{7}-\d{1}$/', $cleaned)) {
            return [
                'valid' => false,
                'error' => 'Invalid CNIC format. Please enter a valid 13-digit National Identity Card number (e.g. 35201-1234567-1).',
                'normalized' => null,
            ];
        }

        $digitsOnly = str_replace('-', '', $cleaned);

        // 2. Province Code Check (First digit must be 1 to 7)
        $firstDigit = (int) $digitsOnly[0];
        if ($firstDigit < 1 || $firstDigit > 7) {
            return [
                'valid' => false,
                'error' => 'Invalid CNIC: The first digit (province territory code) must be between 1 and 7.',
                'normalized' => null,
            ];
        }

        // 3. Reject Dummy / Repetitive Sequences
        if (preg_match('/^(\d)\1{12}$/', $digitsOnly)) {
            return [
                'valid' => false,
                'error' => 'Invalid CNIC: Dummy or repetitive numbers are not permitted.',
                'normalized' => null,
            ];
        }

        $middle7 = substr($digitsOnly, 5, 7);
        if ($middle7 === '0000000' || $middle7 === '1111111' || $middle7 === '1234567') {
            return [
                'valid' => false,
                'error' => 'Invalid CNIC: Obvious test/placeholder sequence detected.',
                'normalized' => null,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'normalized' => $cleaned,
        ];
    }

    /**
     * Validate and normalize a Pakistani mobile phone number.
     * Format: 03XX-XXXXXXX (11 digits)
     */
    public static function validatePhone(?string $phone): array
    {
        if (empty($phone)) {
            return [
                'valid' => false,
                'error' => 'Patient phone number is required.',
                'normalized' => null,
            ];
        }

        // Strip non-digit characters except leading plus
        $raw = trim($phone);
        $digits = preg_replace('/[^\d]/', '', $raw);

        // Handle country code prefixes (+92 or 92)
        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }

        // Must be exactly 11 digits starting with 03
        if (strlen($digits) !== 11 || !str_starts_with($digits, '03')) {
            return [
                'valid' => false,
                'error' => 'Please enter a valid 11-digit Pakistani mobile number (e.g. 0300-1234567).',
                'normalized' => null,
            ];
        }

        $prefix = substr($digits, 0, 4);

        // Verify authorized telecom operator prefix
        if (!in_array($prefix, self::$validNetworkPrefixes, true)) {
            return [
                'valid' => false,
                'error' => "Invalid mobile operator code ({$prefix}). Please enter an active Jazz, Zong, Ufone, Telenor, or SCOM mobile number.",
                'normalized' => null,
            ];
        }

        // Anti-dummy filter
        $last7 = substr($digits, 4);
        if (
            preg_match('/^(\d)\1{6}$/', $last7) || // all 7 digits same like 0000000, 1111111
            $last7 === '1234567' ||
            $last7 === '7654321' ||
            $last7 === '2345678' ||
            $last7 === '3456789'
        ) {
            return [
                'valid' => false,
                'error' => 'Invalid phone number: Test or placeholder sequences are not allowed.',
                'normalized' => null,
            ];
        }

        $normalized = substr($digits, 0, 4) . '-' . substr($digits, 4);

        return [
            'valid' => true,
            'error' => null,
            'normalized' => $normalized,
        ];
    }

    /**
     * Validate an email address and reject disposable/throwaway email providers.
     */
    public static function validateEmail(?string $email): array
    {
        if (empty($email)) {
            // Optional field unless required by controller
            return ['valid' => true, 'error' => null];
        }

        $email = trim(strtolower($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => 'Please enter a valid email address.',
            ];
        }

        $parts = explode('@', $email);
        $domain = $parts[1] ?? '';

        if (in_array($domain, self::$disposableEmailDomains, true)) {
            return [
                'valid' => false,
                'error' => 'Disposable or temporary email services are not permitted. Please provide a standard personal or professional email address.',
            ];
        }

        return ['valid' => true, 'error' => null];
    }
}
