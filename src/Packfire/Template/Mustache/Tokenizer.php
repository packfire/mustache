<?php

namespace Packfire\Template\Mustache;

class Tokenizer
{
    /**
     * The tag regular expression
     * @since 1.2.0
     */
    const TAG_REGEX = '`%s([%s]{0,1})(%s)%s`is';

    const TYPE_NORMAL = '';
    const TYPE_OPEN = '#';
    const TYPE_CLOSE = '/';
    const TYPE_UNESCAPE = '&';
    const TYPE_UNESCAPETRIPLE = '{';
    const TYPE_INVERT = '^';
    const TYPE_COMMENT = '!';
    const TYPE_PARTIAL1 = '>';
    const TYPE_PARTIAL2 = '<';
    const TYPE_CHANGETAG = '=';

    const TOKEN_TYPE_TEXT = 1;
    const TOKEN_TYPE_TAG = 2;
    const TOKEN_TYPE_SECTION = 3;
    const TOKEN_TYPE_NEWLINE = 4;

    const TOKEN_TYPE = 'type';
    const TOKEN_NAME = 'name';
    const TOKEN_TAG_TYPE = 'tagType';
    const TOKEN_OPEN_DELIMITER = 'open';
    const TOKEN_CLOSE_DELIMITER = 'close';
    const TOKEN_POSITION = 'position';
    const TOKEN_LINE = 'line';
    const TOKEN_COLUMN = 'column';
    const TOKEN_VALUE = 'value';
    const TOKEN_NODES = 'nodes';

    /**
     * The opening delimiter
     * @var string
     * @since 1.2.0
     */
    protected $openDelimiter = '{{';

    /**
     * The closing delimiter
     * @var string
     * @since 1.2.0
     */
    protected $closeDelimiter = '}}';

    /**
     * The current line number
     * @var integer
     * @since 1.2.0
     */
    protected $line = array();

    public function parse($text)
    {
        $tokens = array();
        $textLength = strlen($text);
        $position = 0;
        $this->line = 1;
        while ($position < $textLength) {
            $match = array();
            $newlinePosition = strpos($text, "\n", $position);
            if ($newlinePosition === false) {
                $newlinePosition = $textLength;
            }
            do {
                $hasTagMatch = preg_match(
                    $this->buildMatchingTag(),
                    substr($text, 0, $newlinePosition),
                    $match,
                    PREG_OFFSET_CAPTURE,
                    $position
                );
                if ($hasTagMatch) {
                    // there's a tag in between
                    $tagLength = strlen($match[0][0]);
                    $tagStart = $match[0][1];
                    $tagEnd = $tagStart + $tagLength;
                    if ($match[1][0] == self::TYPE_UNESCAPETRIPLE) {
                        $tagLength += 1;
                        $tagEnd += 1;
                    }

                    $subText = substr($text, $position, $tagStart - $position);
                    if (strlen($subText)) {
                        $tokens[] = array(
                            self::TOKEN_TYPE => self::TOKEN_TYPE_TEXT,
                            self::TOKEN_LINE => $this->line,
                            self::TOKEN_VALUE => $subText
                        );
                    }

                    $tokens[] = $this->buildTagToken($match, $position);
                    $position = $tagEnd;
                }
            } while ($hasTagMatch);

            $subText = substr($text, $position, $newlinePosition - $position);
            if (strlen($subText)) {
                $tokens[] = array(
                    self::TOKEN_TYPE => self::TOKEN_TYPE_TEXT,
                    self::TOKEN_LINE => $this->line,
                    self::TOKEN_VALUE => $subText
                );
            }
            $position = $newlinePosition;
            if (substr($text, $position, 1) === "\n") {
                $tokens[] = array(
                    self::TOKEN_TYPE => self::TOKEN_TYPE_NEWLINE,
                    self::TOKEN_VALUE => "\n"
                );
                ++$this->line;
                ++$position;
            }
        }
        $tokens = $this->processTokens($tokens);
        return $tokens;
    }

    protected function processTokens($tokens, $startIndex = 0, $closingTag = null)
    {
        $result = array();
        $count = count($tokens);
        for ($i = $startIndex; $i < $count; ++$i) {
            $token = $tokens[$i];
            if ($token[self::TOKEN_TYPE] == self::TOKEN_TYPE_TAG) {
                if ($token[self::TOKEN_TAG_TYPE] == self::TYPE_OPEN
                    || $token[self::TOKEN_TAG_TYPE] == self::TYPE_INVERT) {
                    $nodes = $this->processTokens($tokens, $i + 1, $token[self::TOKEN_NAME]);
                    $token[self::TOKEN_NODES] = $nodes;
                    $i += count($nodes) + 1;
                } elseif ($closingTag && $token[self::TOKEN_TAG_TYPE] == self::TYPE_CLOSE) {
                    break;
                }
            }
            array_push($result, $token);
        }
        return $result;
    }

    protected function buildTagToken($match, $position)
    {
        return array(
            self::TOKEN_TYPE => self::TOKEN_TYPE_TAG,
            self::TOKEN_TAG_TYPE => $match[1][0],
            self::TOKEN_NAME => $match[2][0],
            self::TOKEN_LINE => $this->line,
            self::TOKEN_OPEN_DELIMITER => $this->openDelimiter,
            self::TOKEN_CLOSE_DELIMITER => $this->closeDelimiter
        );
    }

    /**
     * Build the tag matching regular expression
     * @param string $name (optional) The tag name to match
     * @return string Returns the final regular expression
     * @since 1.2.0
     */
    private function buildMatchingTag($name = '.+?', $type = '^/&#={!><')
    {
        return sprintf(self::TAG_REGEX, preg_quote($this->openDelimiter), preg_quote($type), $name, preg_quote($this->closeDelimiter));
    }
}
