<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SaudiPhoneNumber implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true; // Allow nullable
        }

        // Remove any spaces, dashes, or other characters
        $phone = preg_replace('/[^0-9+]/', '', $value);

        // Check if it already has country code
        if (strpos($phone, '+966') === 0) {
            // Remove +966 and check if remaining starts with 5
            $phoneWithoutCode = substr($phone, 4);
            return strpos($phoneWithoutCode, '5') === 0 && strlen($phoneWithoutCode) === 9;
        } elseif (strpos($phone, '966') === 0) {
            // Remove 966 and check if remaining starts with 5
            $phoneWithoutCode = substr($phone, 3);
            return strpos($phoneWithoutCode, '5') === 0 && strlen($phoneWithoutCode) === 9;
        } elseif (strpos($phone, '05') === 0) {
            // Local format: 05XXXXXXXXX (10 digits)
            if (strlen($phone) !== 10) {
                return false;
            }
            // Check if second digit is 5
            return substr($phone, 1, 1) === '5';
        } elseif (strpos($phone, '5') === 0) {
            // Format without leading zero: 5XXXXXXXXX (9 digits)
            return strlen($phone) === 9;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid Saudi phone number starting with 5 or 05.';
    }

    /**
     * Normalize phone number to +966 format
     */
    public static function normalize(string $phone): string
    {
        if (empty($phone)) {
            return $phone;
        }

        // Remove any spaces, dashes, or other characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If already in +966 format, return as is
        if (strpos($phone, '+966') === 0) {
            return $phone;
        }

        // If starts with 966, add +
        if (strpos($phone, '966') === 0) {
            return '+' . $phone;
        }

        // If starts with 05, remove 0 and add +966
        if (strpos($phone, '05') === 0) {
            return '+966' . substr($phone, 1);
        }

        // If starts with 5, add +966
        if (strpos($phone, '5') === 0) {
            return '+966' . $phone;
        }

        // If none of the above, assume it's already in correct format or return as is
        return $phone;
    }
}
