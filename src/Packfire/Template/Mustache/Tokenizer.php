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

    /**
     * The tokens result
     * @var array
     * @since 1.2.0
     */
    protected $tokens = array();

    public function parse($text)
    {
        $textLength = strlen($text);
        $position = 0;
        $this->line = 1;
        while ($position < $textLength) {
            $match = array();
            $newlinePosition = strpos($text, "\n", $position);
            if ($newlinePosition === false) {
                $newlinePosition = $textLength;
            }
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

                $subText = substr($text, $position, $tagStart - $position);
                if (strlen($subText)) {
                    $this->tokens[] = array(
                        self::TOKEN_TYPE => self::TOKEN_TYPE_TEXT,
                        self::TOKEN_VALUE => $subText
                    );
                }
                $this->tokens[] = $this->buildTagToken($match, $position);

                $subText = substr($text, $tagEnd, $newlinePosition - $tagEnd);
                if (strlen($subText)) {
                    $this->tokens[] = array(
                        self::TOKEN_TYPE => self::TOKEN_TYPE_TEXT,
                        self::TOKEN_VALUE => $subText
                    );
                }
            } else {
                $subText = substr($text, $position, $newlinePosition - $position);
                if (strlen($subText)) {
                    $this->tokens[] = array(
                        self::TOKEN_TYPE => self::TOKEN_TYPE_TEXT,
                        self::TOKEN_VALUE => $subText
                    );
                }
            }
            $position = $newlinePosition;
            if (substr($text, $position, 1) === "\n") {
                $this->tokens[] = array(
                    self::TOKEN_TYPE => self::TOKEN_TYPE_NEWLINE,
                    self::TOKEN_VALUE => "\n"
                );
                ++$this->line;
                ++$position;
            }
        }
        return $this->tokens;
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
    private function buildMatchingTag($name = '.+?', $type = '^&#={!><')
    {
        return sprintf(self::TAG_REGEX, preg_quote($this->openDelimiter), preg_quote($type), $name, preg_quote($this->closeDelimiter));
    }
}