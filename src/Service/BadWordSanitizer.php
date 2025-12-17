<?php

namespace App\Service;

class BadWordSanitizer
{
    private array $badWords = [
        'merde',
        'connard',
        'salope',
        'pute',
        'con',
        'conne',
        'fuck',
        'shit',
        'bitch',
        'asshole',
        'bastard',
        'cunt',
        'dick',
        'iditot',
        'stupid',
        'idiot',
        
    ];

    public function sanitize(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        file_put_contents(
            'badword_debug.txt',
            "Sanitizing: {$text}" . PHP_EOL,
            FILE_APPEND
        );

        foreach ($this->badWords as $word) {
            // unicode-aware whole-word match
            $pattern = '/(?<!\p{L})' . preg_quote($word, '/') . '(?!\p{L})/iu';
            $replacement = str_repeat('*', mb_strlen($word));

            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        file_put_contents(
            'badword_debug.txt',
            "Result: {$text}" . PHP_EOL,
            FILE_APPEND
        );

        return $text;
    }
}


