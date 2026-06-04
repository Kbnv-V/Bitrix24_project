<?php

class CorrectionData
{
    public static function correctionPhones($phone)
    {
        $pattern = '/\+?\s*\d?[\s\-]*\(?\d{2,5}\)?(?:[\s\-]*\d{2,4}){2,5}(?:\s*\(?\s*доб\.?\s*:?\s*\d+\s*\)?)?/iu';
        preg_match_all($pattern, $phone, $matches);

        $normPhone = [];

        foreach($matches[0] as $value)
        {
            $value = trim($value);
            $normPhone[] = $value;
        }

        return $normPhone;
    }

    public static function correctionEmails($email)
    {
        $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        
        preg_match_all($pattern, $email, $matches);
        
        return $matches[0];
    }
}