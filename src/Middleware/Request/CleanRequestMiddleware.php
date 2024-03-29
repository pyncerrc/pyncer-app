<?php
namespace Pyncer\App\Middleware\Request;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Pyncer\Exception\Exception;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use StdClass;

use array_keys;
use array_values;
use in_array;
use is_iterable;
use is_string;
use ord;
use preg_replace;
use strlen;

class CleanRequestMiddleware implements MiddlewareInterface
{
    private bool $stripMaliciousUtf8Characters;
    private bool $replaceBadUtf8Characters;
    private string $replaceString;

    protected const BAD_UTF8_CHARACTERS = [
        "\xcc\xb7"     => '', // COMBINING SHORT SOLIDUS OVERLAY      0337
        "\xcc\xb8"     => '', // COMBINING LONG SOLIDUS OVERLAY       0338
        "\xe1\x85\x9F" => '', // HANGUL CHOSEONG FILLER               115F
        "\xe1\x85\xA0" => '', // HANGUL JUNGSEONG FILLER              1160
        "\xe2\x80\x8b" => '', // ZERO WIDTH SPACE                     200B
        "\xe2\x80\x8c" => '', // ZERO WIDTH NON-JOINER                200C
        "\xe2\x80\x8d" => '', // ZERO WIDTH JOINER                    200D
        "\xe2\x80\x8e" => '', // LEFT-TO-RIGHT MARK                   200E
        "\xe2\x80\x8f" => '', // RIGHT-TO-LEFT MARK                   200F
        "\xe2\x80\xaa" => '', // LEFT-TO-RIGHT EMBEDDING              202A
        "\xe2\x80\xab" => '', // RIGHT-TO-LEFT EMBEDDING              202B
        "\xe2\x80\xac" => '', // POP DIRECTIONAL FORMATTING           202C
        "\xe2\x80\xad" => '', // LEFT-TO-RIGHT OVERRIDE               202D
        "\xe2\x80\xae" => '', // RIGHT-TO-LEFT OVERRIDE               202E
        "\xe2\x80\xaf" => '', // NARROW NO-BREAK SPACE                202F
        "\xe2\x81\x9f" => '', // MEDIUM MATHEMATICAL SPACE            205F
        "\xe2\x81\xa0" => '', // WORD JOINER                          2060
        "\xe3\x85\xa4" => '', // HANGUL FILLER                        3164
        "\xef\xbb\xbf" => '', // ZERO WIDTH NO-BREAK SPACE            FEFF
        "\xef\xbe\xa0" => '', // HALFWIDTH HANGUL FILLER              FFA0
        "\xef\xbf\xb9" => '', // INTERLINEAR ANNOTATION ANCHOR        FFF9
        "\xef\xbf\xba" => '', // INTERLINEAR ANNOTATION SEPARATOR     FFFA
        "\xef\xbf\xbb" => '', // INTERLINEAR ANNOTATION TERMINATOR    FFFB
        "\xef\xbf\xbc" => '', // OBJECT REPLACEMENT CHARACTER         FFFC
        "\xef\xbf\xbd" => '', // REPLACEMENT CHARACTER                FFFD
        "\xe2\x80\x80" => ' ', // EN QUAD                             2000
        "\xe2\x80\x81" => ' ', // EM QUAD                             2001
        "\xe2\x80\x82" => ' ', // EN SPACE                            2002
        "\xe2\x80\x83" => ' ', // EM SPACE                            2003
        "\xe2\x80\x84" => ' ', // THREE-PER-EM SPACE                  2004
        "\xe2\x80\x85" => ' ', // FOUR-PER-EM SPACE                   2005
        "\xe2\x80\x86" => ' ', // SIX-PER-EM SPACE                    2006
        "\xe2\x80\x87" => ' ', // FIGURE SPACE                        2007
        "\xe2\x80\x88" => ' ', // PUNCTUATION SPACE                   2008
        "\xe2\x80\x89" => ' ', // THIN SPACE                          2009
        "\xe2\x80\x8a" => ' ', // HAIR SPACE                          200A
        "\xE3\x80\x80" => ' ', // IDEOGRAPHIC SPACE                   3000
    ];

    public function __construct(
        bool $stripMaliciousUtf8Characters = false,
        bool $replaceBadUtf8Characters = false,
        string $replaceString = ''
    ) {
        $this->setStripMaliciousUtf8Characters($stripMaliciousUtf8Characters);
        $this->setReplaceBadUtf8Characters($replaceBadUtf8Characters);
        $this->setReplaceString($replaceString);
    }

    public function getStripMaliciousUtf8Characters(): bool
    {
        return $this->stripMaliciousUtf8Characters;
    }
    public function setStripMaliciousUtf8Characters(bool $value): static
    {
        $this->stripMaliciousUtf8Characters = $value;
        return $this;
    }

    public function getReplaceBadUtf8Characters(): bool
    {
        return $this->replaceBadUtf8Characters;
    }
    public function setReplaceBadUtf8Characters(bool $value): static
    {
        $this->replaceBadUtf8Characters = $value;
        return $this;
    }

    public function getReplaceString(): string
    {
        return $this->replaceString;
    }
    public function setReplaceString(string $value): static
    {
        $this->replaceString = $value;
        return $this;
    }

    public function __invoke(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ): PsrResponseInterface
    {
        if (!$this->getReplaceBadUtf8Characters() &&
            !$this->getStripMaliciousUtf8Characters()
        ) {
            return $handler->next($request, $response);
        }

        $request = $request->withCookieParams(
            $this->cleanData($request->getCookieParams())
        );

        $request = $request->withQueryParams(
            $this->cleanData($request->getQueryParams())
        );

        // TODO: Determine if it makes sense to check content type
        // $contentType = $request->getHeaderLine('Content-Type');
        if (in_array($request->getMethod(), ['PATCH', 'POST', 'PUT'])) {
            $parsedBody = $request->getParsedBody();

            if (is_array($parsedBody)) {
                $request = $request->withParsedBody(
                    $this->cleanData($parsedBody)
                );
            } elseif ($parsedBody instanceof StdClass) {
                $parsedBody = $this->cleanData((array)$parsedBody);

                $request = $request->withParsedBody(
                    (object)$parsedBody
                );
            }
        }

        return $handler->next($request, $response);
    }

    private function cleanData(array $value): array
    {
        if ($this->getReplaceBadUtf8Characters()) {
            $value = $this->replaceBadCharacters(
                $value,
                $this->getReplaceString()
            );
        }

        if ($this->getStripMaliciousUtf8Characters()) {
            $value = $this->stripMaliciousCharacters($value);
        }

        return $value;
    }

    private function replaceBadCharacters(
        string|array $data,
        string $replace
    ): string|array
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value) || is_array($value)) {
                    $data[$key] = $this->replaceBadCharacters($value, $replace);
                } else {
                    $data[$key] = $value;
                }
            }

            return $data;
        }

        $result = '';

        $len = strlen($data);
        for ($i = 0; $i < $len;) {
            $char = $data[$i++];
            $byte = ord($char);

            if ($byte < 0x80) {
                $bytes = 0; // 1-bytes (00000000 to 01111111)
            } elseif ($byte < 0xC0) { // 1-bytes (10000000 to 10111111)
                $result .= $replace;
                continue;
            } elseif ($byte < 0xE0) {
                $bytes = 1; // 2-bytes (11000000 to 11011111)
            } elseif ($byte < 0xF0) {
                $bytes = 2; // 3-bytes (11100000 to 11101111)
            } elseif ($byte < 0xF8) {
                $bytes = 3; // 4-bytes (11110000 to 11110111)
            } elseif ($byte < 0xFC) {
                $bytes = 4; // 5-bytes (11111000 to 11111011)
            } elseif ($byte < 0xFE) {
                $bytes = 5; // 6-bytes (11111100 to 11111101)
            } else { // Invalid
                $result .= $replace;
                continue;
            }

            // Ensure enough enough characters
            if ($i + $bytes > $len) {
                $result .= $replace;
                continue;
            }

            // Multi-byte character
            for ($j = 0; $j < $bytes; $j++) {
                $byte = $data[$i + $j];

                $char .= $byte;
                $byte = ord($byte);

                // Must be between 10000000 and 10111111
                if ($byte < 0x80 || $byte > 0xBF) {
                    $result .= $replace;
                    continue 2;
                }
            }

            $i += $bytes;
            $result .= $char;
        }

        return $result;
    }

    private function stripMaliciousCharacters(string|array $data): string|array
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value) || is_array($value)) {
                    $data[$key] = $this->stripMaliciousCharacters($value);
                } else {
                    $data[$key] = $value;
                }
            }

            return $data;
        }

        // Remove control characters
        $data = preg_replace('%[\x00-\x08\x0b-\x0c\x0e-\x1f]%', '', $data);
        $data = strval($data);

        // Replace some 'bad' characters
        $data = str_replace(
            array_keys(static::BAD_UTF8_CHARACTERS),
            array_values(static::BAD_UTF8_CHARACTERS),
            $data
        );

        return $data;
    }
}
