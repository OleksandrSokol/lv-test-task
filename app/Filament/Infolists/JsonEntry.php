<?php

namespace App\Filament\Infolists;

use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * A JSON attribute (array cast or JSON text) shown pretty-printed and highlighted.
 *
 * Styles live in resources/css/filament/json.css: Filament's prebuilt CSS has
 * no `whitespace-pre-*` utilities and no syntax colours.
 */
class JsonEntry
{
    private const TOKEN = '/"(?:\\\\.|[^"\\\\])*"|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|\btrue\b|\bfalse\b|\bnull\b/';

    public static function make(string $name): TextEntry
    {
        return TextEntry::make($name)
            ->key($name)
            // An array state would be rendered as a list of values, so the state is the JSON text.
            ->state(fn (Model $record) => self::pretty($record->getAttribute($name)))
            ->formatStateUsing(fn (string $state) => new HtmlString('<pre class="json-view">' . self::highlight($state) . '</pre>'))
            ->fontFamily(FontFamily::Mono);
    }

    private static function pretty(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Text that is not valid JSON is shown as is.
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $value;
            }

            $value = $decoded;
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Escaped HTML with tokens wrapped in spans; anything that is not JSON is only escaped.
     */
    private static function highlight(string $json): string
    {
        json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return e($json);
        }

        return preg_replace_callback(self::TOKEN, function (array $match) use ($json): string {
            [$token, $offset] = $match[0];

            $class = match (true) {
                $token[0] === '"' && preg_match('/^\s*:/', substr($json, $offset + strlen($token))) === 1 => 'json-key',
                $token[0] === '"'                                                                         => 'json-string',
                $token === 'true', $token === 'false'                                                     => 'json-bool',
                $token === 'null'                                                                         => 'json-null',
                default                                                                                   => 'json-number',
            };

            return '<span class="' . $class . '">' . e($token) . '</span>';
        }, $json, flags: PREG_OFFSET_CAPTURE);
    }
}
