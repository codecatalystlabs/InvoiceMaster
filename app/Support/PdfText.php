<?php

namespace App\Support;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Element\ElementArray;
use Smalot\PdfParser\Element\ElementMissing;
use Smalot\PdfParser\Parser as PdfParser;
use Smalot\PdfParser\XObject\Form;

class PdfText
{
    public static function extract(string $path): string
    {
        $bytes = @file_get_contents($path) ?: '';
        $candidates = [
            self::viaSmalot($path),
            self::viaPageStreams($path),
            self::viaRawStreams($bytes),
            self::viaPdftotext($path),
        ];

        foreach ($candidates as $text) {
            $text = self::normalize($text);
            if (self::looksLikeStatement($text)) {
                return $text;
            }
        }

        return self::normalize(implode("\n", array_filter($candidates)));
    }

    public static function stringsFromContent(string $content): string
    {
        $parts = [];

        if (preg_match_all('/\(((?:\\\\.|[^\\\\)])*)\)/', $content, $m)) {
            foreach ($m[1] as $raw) {
                $s = self::unescapePdfString($raw);
                if (self::isUsefulText($s)) {
                    $parts[] = $s;
                }
            }
        }

        if (preg_match_all('/<([0-9A-Fa-f\s]+)>/', $content, $h)) {
            foreach ($h[1] as $hex) {
                $hex = preg_replace('/\s+/', '', $hex) ?? '';
                if ($hex === '' || strlen($hex) % 2 !== 0) {
                    continue;
                }
                $bin = @hex2bin($hex);
                if ($bin === false || $bin === '') {
                    continue;
                }
                foreach (self::decodeHexBytes($bin) as $s) {
                    if (self::isUsefulText($s)) {
                        $parts[] = $s;
                    }
                }
            }
        }

        return trim(implode(' ', $parts));
    }

    protected static function viaSmalot(string $path): string
    {
        try {
            $config = new Config;
            $config->setIgnoreEncryption(true);
            $config->setRetainImageContent(false);
            $pdf = (new PdfParser([], $config))->parseFile($path);

            return trim($pdf->getText());
        } catch (\Throwable) {
            return '';
        }
    }

    protected static function viaPageStreams(string $path): string
    {
        try {
            $config = new Config;
            $config->setIgnoreEncryption(true);
            $config->setRetainImageContent(false);
            $pdf = (new PdfParser([], $config))->parseFile($path);
            $chunks = [];
            foreach ($pdf->getPages() as $page) {
                $chunks[] = self::stringsFromContent(self::pageContent($page));
                foreach ($page->getXObjects() as $object) {
                    if ($object instanceof Form) {
                        $chunks[] = self::stringsFromContent((string) $object->getContent());
                    }
                }
            }

            return implode("\n", array_filter($chunks));
        } catch (\Throwable) {
            return '';
        }
    }

    protected static function pageContent($page): string
    {
        $contents = $page->get('Contents');
        if (! $contents || $contents instanceof ElementMissing) {
            return (string) $page->getContent();
        }
        if ($contents instanceof ElementArray) {
            $raw = '';
            foreach ($contents->getContent() as $part) {
                $raw .= (method_exists($part, 'getContent') ? (string) $part->getContent() : '')."\n";
            }

            return $raw;
        }
        if (method_exists($contents, 'getContent')) {
            return (string) $contents->getContent();
        }

        return (string) $page->getContent();
    }

    protected static function viaRawStreams(string $bytes): string
    {
        if ($bytes === '' || ! str_contains($bytes, 'stream')) {
            return '';
        }

        $chunks = [];
        if (preg_match_all('/stream\r?\n(.*?)endstream/s', $bytes, $matches)) {
            foreach ($matches[1] as $data) {
                $data = self::inflate(rtrim($data, "\r\n"));
                if ($data === '' || strlen($data) > 4_000_000) {
                    continue;
                }
                if (! preg_match('/Tj|TJ|Tm|Td|BT|\(|</', $data)) {
                    continue;
                }
                $chunk = self::stringsFromContent($data);
                if ($chunk !== '') {
                    $chunks[] = $chunk;
                }
            }
        }

        return implode("\n", $chunks);
    }

    protected static function inflate(string $data): string
    {
        $decoded = @gzuncompress($data);
        if (is_string($decoded) && $decoded !== '') {
            return $decoded;
        }
        $decoded = @gzinflate($data);
        if (is_string($decoded) && $decoded !== '') {
            return $decoded;
        }
        if (strlen($data) > 2) {
            $decoded = @gzinflate(substr($data, 2));
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $data;
    }

    protected static function viaPdftotext(string $path): string
    {
        $bin = self::pdftotextBinary();
        if ($bin === null) {
            return '';
        }
        $out = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('stmt_', true).'.txt';
        $cmd = escapeshellarg($bin).' -layout -enc UTF-8 '.escapeshellarg($path).' '.escapeshellarg($out).' 2>NUL';
        exec($cmd, $ignored, $code);
        if ($code !== 0 || ! is_file($out)) {
            return '';
        }
        $text = (string) file_get_contents($out);
        @unlink($out);

        return $text;
    }

    protected static function pdftotextBinary(): ?string
    {
        $configured = env('PDFTOTEXT_PATH');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }
        foreach (['pdftotext', 'pdftotext.exe'] as $name) {
            $found = trim((string) shell_exec('where '.escapeshellarg($name).' 2>NUL'));
            if ($found !== '') {
                return strtok($found, "\n");
            }
        }

        return null;
    }

    protected static function unescapePdfString(string $value): string
    {
        $value = str_replace(
            ['\\\\', '\\n', '\\r', '\\t', '\\b', '\\f', '\\(', '\\)'],
            ['\\', "\n", "\n", "\t", '', '', '(', ')'],
            $value
        );

        return preg_replace_callback('/\\\\([0-7]{1,3})/', fn ($m) => chr(octdec($m[1])), $value) ?? $value;
    }

    protected static function decodeHexBytes(string $bin): array
    {
        $out = [];
        if (str_starts_with($bin, "\xFE\xFF") || str_starts_with($bin, "\xFF\xFE")) {
            $converted = @mb_convert_encoding($bin, 'UTF-8', 'UTF-16');
            if (is_string($converted) && $converted !== '') {
                $out[] = $converted;
            }

            return $out;
        }
        if (preg_match('/^[\x20-\x7E\r\n\t]+$/', $bin)) {
            $out[] = $bin;

            return $out;
        }
        if (strlen($bin) >= 4 && strlen($bin) % 2 === 0) {
            $utf16 = @mb_convert_encoding($bin, 'UTF-8', 'UTF-16BE');
            if (is_string($utf16) && preg_match('/[\p{L}\d\/,.\-]/u', $utf16) && ! preg_match('/\p{C}/u', str_replace(["\n", "\t"], '', $utf16))) {
                $out[] = $utf16;
            }
        }

        return $out;
    }

    protected static function isUsefulText(string $s): bool
    {
        $s = trim($s);
        if ($s === '') {
            return false;
        }

        return (bool) preg_match('/[\p{L}\d]/u', $s);
    }

    protected static function looksLikeStatement(string $text): bool
    {
        return (bool) preg_match('/\d{2}\/\d{2}\/\d{4}/', $text)
            && (bool) preg_match('/\d{1,3}(?:,\d{3})+\.\d{2}|\d+\.\d{2}/', $text);
    }

    public static function normalize(string $text): string
    {
        if (str_starts_with($text, "\xFF\xFE") || str_starts_with($text, "\xFE\xFF")) {
            $text = (string) mb_convert_encoding($text, 'UTF-8', 'UTF-16');
        }
        if (substr_count($text, "\0") > 5) {
            $stripped = str_replace("\0", '', $text);
            if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $stripped)) {
                $text = $stripped;
            }
        }
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[^\S\n]+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
