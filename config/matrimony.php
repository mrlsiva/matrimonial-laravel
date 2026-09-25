<?php

return [
    'profile_code_prefix' => env('PROFILE_CODE_PREFIX', 'KM'),
    'otp_expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
    'otp_max_attempts' => 5,
    'sms_driver' => env('SMS_DRIVER', 'log'),
    'max_photos' => 6,
    'photo_max_kb' => 5120,
    'free_daily_interest_limit' => 5,
    'support_email' => env('SUPPORT_EMAIL', 'support@kalyanmatrimony.test'),
    'support_phone' => env('SUPPORT_PHONE', '+91 98765 43210'),

    'options' => [
        'marital_status' => ['never_married' => 'Never Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed', 'awaiting_divorce' => 'Awaiting Divorce'],
        'created_by' => ['self' => 'Self', 'parent' => 'Parent', 'sibling' => 'Sibling', 'relative' => 'Relative', 'friend' => 'Friend'],
        'mother_tongue' => ['Tamil', 'Telugu', 'Kannada', 'Malayalam', 'Hindi', 'Marathi', 'Bengali', 'Gujarati', 'Punjabi', 'Urdu', 'Odia', 'English', 'Other'],
        'complexion' => ['Very Fair', 'Fair', 'Wheatish', 'Wheatish Brown', 'Dark'],
        'body_type' => ['Slim', 'Average', 'Athletic', 'Heavy'],
        'physical_status' => ['normal' => 'Normal', 'physically_challenged' => 'Physically Challenged'],
        'diet' => ['Vegetarian', 'Non-Vegetarian', 'Eggetarian', 'Vegan'],
        'habits' => ['no' => 'No', 'occasionally' => 'Occasionally', 'yes' => 'Yes'],
        'family_type' => ['Joint', 'Nuclear'],
        'family_status' => ['Middle Class', 'Upper Middle Class', 'Rich', 'Affluent'],
        'employed_in' => ['Private', 'Government', 'Business', 'Self Employed', 'Defence', 'Not Working'],
        'annual_income' => ['0-3' => 'Below ₹3 Lakh', '3-5' => '₹3 - 5 Lakh', '5-10' => '₹5 - 10 Lakh', '10-20' => '₹10 - 20 Lakh', '20-50' => '₹20 - 50 Lakh', '50+' => 'Above ₹50 Lakh'],
        'star' => ['Ashwini', 'Bharani', 'Krittika', 'Rohini', 'Mrigashira', 'Ardra', 'Punarvasu', 'Pushya', 'Ashlesha', 'Magha', 'Purva Phalguni', 'Uttara Phalguni', 'Hasta', 'Chitra', 'Swati', 'Vishakha', 'Anuradha', 'Jyeshtha', 'Mula', 'Purva Ashadha', 'Uttara Ashadha', 'Shravana', 'Dhanishta', 'Shatabhisha', 'Purva Bhadrapada', 'Uttara Bhadrapada', 'Revati'],
        'rasi' => ['Mesha (Aries)', 'Vrishabha (Taurus)', 'Mithuna (Gemini)', 'Karka (Cancer)', 'Simha (Leo)', 'Kanya (Virgo)', 'Tula (Libra)', 'Vrischika (Scorpio)', 'Dhanu (Sagittarius)', 'Makara (Capricorn)', 'Kumbha (Aquarius)', 'Meena (Pisces)'],
        'dosham' => ['no' => 'No', 'yes' => 'Yes', 'dont_know' => "Don't Know"],
    ],
];
