<?php

namespace Eunoia\Theme;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials avatar rendered locally as an SVG data URI (replaces ui-avatars.com, finding F14 / E1-02).
 * Works offline and sends no personal names to a third party.
 */
class LocalAvatarProvider implements AvatarProvider
{
    /**
     * Background colours (token 600 shades); white text has ≥ 4.5:1 contrast on each.
     *
     * @var array<int, string>
     */
    public const BACKGROUNDS = [Tokens::PRIMARY, Tokens::ACCENT, Tokens::INFO, Tokens::SUCCESS, Tokens::DANGER, '#44403C'];

    public function get(Model|Authenticatable $record): string
    {
        return static::forName((string) Filament::getNameForDefaultAvatar($record));
    }

    public static function forName(string $name): string
    {
        $initials = static::initials($name);
        $background = static::BACKGROUNDS[crc32($name) % count(static::BACKGROUNDS)];
        $text = htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="'.$background.'"/>'
            .'<text x="50%" y="50%" dy=".1em" fill="#FFFFFF" font-family="IBM Plex Sans Arabic, Inter, sans-serif" font-size="26" font-weight="600" text-anchor="middle" dominant-baseline="middle">'.$text.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * First letter of the first two words, skipping leading punctuation ("[SYSTEM] Admin" → "A").
     */
    public static function initials(string $name): string
    {
        $letters = collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->map(fn (string $word): string => (string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $word))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->join(Arabic::isArabic($name) ? ' ' : '');

        return $letters !== '' ? $letters : '?';
    }
}
