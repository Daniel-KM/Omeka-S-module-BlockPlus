<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper to check if a string is a well-formed xml.
 *
 * @see \DataTypeRdf\DataType\Xml::isWellFormed()
 *
 * @deprecated Use \Common\View\Helper\IsXml
 */
class IsXml extends AbstractHelper
{
    /**
     * Check if a string is a well-formed xml. Don't check validity or security.
     *
     * Support strings without a root tag, according to the w3c spec for the
     * lexical space of the data type rdf:XMLLiteral, but the string must be a
     * well-balanced and self-contained content.
     * @see https://www.w3.org/TR/rdf11-concepts/#section-XMLLiteral
     *
     * For html fragment, the lexical space is larger (any unicode string), so
     * this method does more checks than needed.
     * @see https://www.w3.org/TR/rdf11-concepts/#section-html
     */
    public function __invoke($string): bool
    {
        if (!$string) {
            return false;
        }

        // Skip non scalar, except stringable object.
        if (!is_scalar($string)
            && !(is_object($string) && method_exists($string, '__toString'))
        ) {
            return false;
        }

        $string = trim((string) $string);

        // Do some quick checks.
        if (!$string
            || mb_substr($string, 0, 1) !== '<'
            || mb_substr($string, -1) !== '>'
            // TODO Is it really a quick check to use strip_tags before simplexml?
            || $string === strip_tags($string)
        ) {
            return false;
        }

        // With CodeMirror, the root node is not required, so append one.
        // Anyway, it is required by the specification for xml fragment.
        // False is already returned above for simple strings.
        if (mb_substr($string, 0, 5) !== '<?xml') {
            $string = '<root>' . $string . '</root>';
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $simpleXml = simplexml_load_string(
            $string,
            'SimpleXMLElement',
            LIBXML_COMPACT | LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        return $simpleXml !== false
            && !count(libxml_get_errors());
    }
}
