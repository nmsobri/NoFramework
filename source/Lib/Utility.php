<?php declare( strict_types=1 );

namespace Aemulus\Lib;

use Psr\Http\Message\ServerRequestInterface;

class Utility {

    public static function array_keys_exists( array $keys, $arr ) {
        if ( !is_array( $arr ) || is_null( $arr ) ) {
            return false;
        }
        return !array_diff_key( array_flip( $keys ), $arr );
    }


    public static function getPathInfo( ServerRequestInterface $request ) {
        return self::preparePathInfo( $request );
    }


    private static function preparePathInfo( ServerRequestInterface $request ) {

        if ( null === ( $requestUri = $request->getUri()->getPath() ) ) {
            return '/';
        }

        # Remove the query string from REQUEST_URI
        if ( false !== $pos = strpos( $requestUri, '?' ) ) {
            $requestUri = substr( $requestUri, 0, $pos );
        }
        if ( '' !== $requestUri && '/' !== $requestUri[0] ) {
            $requestUri = '/' . $requestUri;
        }

        if ( null === ( $baseUrl = self::getBaseUrl( $request ) ) ) {
            return $requestUri;
        }

        $pathInfo = substr( $requestUri, \strlen( $baseUrl ) );
        if ( false === $pathInfo || '' === $pathInfo ) {
            # If substr() returns false then PATH_INFO is set to an empty string
            return '/';
        }

        return (string)$pathInfo;
    }


    public static function getBaseUrl( ServerRequestInterface $request ) {
        return self::prepareBaseUrl( $request );
    }


    private static function prepareBaseUrl( ServerRequestInterface $request ) {
        $SERVER = $request->getServerParams();
        $SCRIPT_FILENAME = $SERVER['SCRIPT_FILENAME'] ?? null;
        $SCRIPT_NAME = $SERVER['SCRIPT_NAME'] ?? null;
        $PHP_SELF = $SERVER['PHP_SELF'] ?? null;
        $ORIG_SCRIPT_NAME = $SERVER['ORIG_SCRIPT_NAME'] ?? null;
        $filename = basename( $SCRIPT_FILENAME );

        if ( basename( $SCRIPT_NAME ) === $filename ) {
            $baseUrl = $SCRIPT_NAME;
        } elseif ( basename( $PHP_SELF ) === $filename ) {
            $baseUrl = $PHP_SELF;
        } elseif ( basename( $ORIG_SCRIPT_NAME ) === $filename ) {
            $baseUrl = $ORIG_SCRIPT_NAME; // 1and1 shared hosting compatibility
        } else {
            # Backtrack up the script_filename to find the portion matching
            # php_self
            $path = $PHP_SELF ?: '';
            $file = $SCRIPT_FILENAME ?: '';
            $segs = explode( '/', trim( $file, '/' ) );
            $segs = array_reverse( $segs );
            $index = 0;
            $last = \count( $segs );
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
            } while ( $last > $index && ( false !== $pos = strpos( $path, $baseUrl ) ) && 0 != $pos );
        }

        # Does the baseUrl have anything in common with the request_uri?
        $requestUri = $request->getUri()->getPath();
        if ( '' !== $requestUri && '/' !== $requestUri[0] ) {
            $requestUri = '/' . $requestUri;
        }

        if ( $baseUrl && false !== $prefix = self::getUrlencodedPrefix( $requestUri, $baseUrl ) ) {
            # Full $baseUrl matches
            return $prefix;
        }

        if ( $baseUrl && false !== $prefix = self::getUrlencodedPrefix( $requestUri, rtrim( \dirname( $baseUrl ), '/' . \DIRECTORY_SEPARATOR ) . '/' ) ) {
            # Directory portion of $baseUrl matches
            return rtrim( $prefix, '/' . \DIRECTORY_SEPARATOR );
        }

        $truncatedRequestUri = $requestUri;
        if ( false !== $pos = strpos( $requestUri, '?' ) ) {
            $truncatedRequestUri = substr( $requestUri, 0, $pos );
        }

        $basename = basename( $baseUrl );
        if ( empty( $basename ) || !strpos( rawurldecode( $truncatedRequestUri ), $basename ) ) {
            # No match whatsoever; set it blank
            return '';
        }

        # If using mod_rewrite or ISAPI_Rewrite strip the script filename
        # out of baseUrl. $pos !== 0 makes sure it is not matching a value
        # from PATH_INFO or QUERY_STRING
        if ( \strlen( $requestUri ) >= \strlen( $baseUrl ) && ( false !== $pos = strpos( $requestUri, $baseUrl ) ) && 0 !== $pos ) {
            $baseUrl = substr( $requestUri, 0, $pos + \strlen( $baseUrl ) );
        }

        return rtrim( $baseUrl, '/' . \DIRECTORY_SEPARATOR );
    }


    private static function getUrlencodedPrefix( string $string, string $prefix ) {
        if ( 0 !== strpos( rawurldecode( $string ), $prefix ) ) {
            return false;
        }

        $len = \strlen( $prefix );

        if ( preg_match( sprintf( '#^(%%[[:xdigit:]]{2}|.){%d}#', $len ), $string, $match ) ) {
            return $match[0];
        }

        return false;
    }
}