<?php

namespace App\Service;

class SearchNormalizer
{
    /**
     * Accent/diacritics stripping + lowercase (pour tokens).
     * Utile côté PHP (SQLite fallback). Sur Postgres/MySQL on applique au SQL.
     */
    public static function normalize(string $text): string
    {
        $text = trim($text);
        if ($text === '') return '';
        if (class_exists('Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_D);
            $text = preg_replace('/\p{Mn}+/u', '', $text); // retire diacritiques
        }
        $iconv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($iconv !== false) {
            $text = $iconv;
        }
        return mb_strtolower($text);
    }
}
