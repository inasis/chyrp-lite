<?php
    /**
     * File: error
     * Functions for handling and reporting errors.
     */

    ini_set("display_errors", false);
    ini_set("error_log", MAIN_DIR.DIR."error_log.txt");

    # Set the appropriate error reporting level.
    if (DEBUG)
        error_reporting(E_ALL | E_STRICT);
    else
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);

    # Set the error and exception handlers.
    set_error_handler("error_composer");
    set_exception_handler("exception_composer");

    /**
     * Function: error_composer
     * Composes a message for the error() function to display.
     */
    function error_composer($errno, $message, $file, $line) {
        # Test for suppressed errors and excluded error levels.
        if (!(error_reporting() & $errno))
            return true;

        $normalized = str_replace(
            array("\t", "\n", "\r", "\0", "\x0B"),
            " ",
            $message
        );

        if (DEBUG)
            error_log(
                "ERROR: ".$errno." ".strip_tags($normalized).
                " (".$file." on line ".$line.")"
            );

        error(body:$message, backtrace:debug_backtrace());
    }

    /**
     * Function: exception_composer
     * Composes a message for the error() function to display.
     */
    function exception_composer($e) {
        $errno = $e->getCode();
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $normalized = str_replace(
            array("\t", "\n", "\r", "\0", "\x0B"),
            " ",
            $message
        );

        if (DEBUG)
            error_log(
                "ERROR: ".$errno." ".strip_tags($normalized).
                " (".$file." on line ".$line.")"
            );

        error(body:$message, backtrace:$e->getTrace());
    }

    /**
     * Function: error
     * Displays an error message via direct call or handler.
     *
     * Parameters:
     *     $title - The title for the error dialog.
     *     $body - The message for the error dialog.
     *     $backtrace - The trace of the error.
     *     $code - Numeric HTTP status code to set.
     */
    function error($title = "", $body = "", $backtrace = array(), $code = 500)/*: never*/{
        # Discard any additional output buffers.
        while (OB_BASE_LEVEL < ob_get_level())
            ob_end_clean();

        # Clean the output buffer before we begin.
        if (ob_get_contents() !== false)
            ob_clean();

        # Attempt to set headers to sane values and send a status code.
        if (!headers_sent()) {
            header("Content-Type: text/html; charset=UTF-8");
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Mon, 03 Jun 1991 05:30:00 GMT");

            # Resend the content encoding header if transparent compression is on.
            if (CAN_USE_ZLIB and ini_get("zlib.output_compression"))
                header("Content-Encoding: ".(HTTP_ACCEPT_GZIP ? "gzip" : "deflate"));

            switch ($code) {
                case 400:
                    header($_SERVER['SERVER_PROTOCOL']." 400 Bad Request");
                    break;
                case 401:
                    header($_SERVER['SERVER_PROTOCOL']." 401 Unauthorized");
                    break;
                case 403:
                    header($_SERVER['SERVER_PROTOCOL']." 403 Forbidden");
                    break;
                case 404:
                    header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
                    break;
                case 405:
                    header($_SERVER['SERVER_PROTOCOL']." 405 Method Not Allowed");
                    break;
                case 409:
                    header($_SERVER['SERVER_PROTOCOL']." 409 Conflict");
                    break;
                case 410:
                    header($_SERVER['SERVER_PROTOCOL']." 410 Gone");
                    break;
                case 413:
                    header($_SERVER['SERVER_PROTOCOL']." 413 Payload Too Large");
                    break;
                case 422:
                    header($_SERVER['SERVER_PROTOCOL']." 422 Unprocessable Entity");
                    break;
                case 501:
                    header($_SERVER['SERVER_PROTOCOL']." 501 Not Implemented");
                    break;
                case 502:
                    header($_SERVER['SERVER_PROTOCOL']." 502 Bad Gateway");
                    break;
                case 503:
                    header($_SERVER['SERVER_PROTOCOL']." 503 Service Unavailable");
                    break;
                case 504:
                    header($_SERVER['SERVER_PROTOCOL']." 504 Gateway Timeout");
                    break;
                default:
                    header($_SERVER['SERVER_PROTOCOL']." 500 Internal Server Error");
            }
        }

        # Report in plain text if desirable or necessary because of a deep error.
        if (
            AJAX or
            !function_exists("__") or
            !function_exists("_f") or
            !function_exists("fallback") or
            !function_exists("fix") or
            !function_exists("sanitize_html") or
            !function_exists("logged_in") or
            !file_exists(INCLUDES_DIR.DIR."config.json.php") or
            !class_exists("Config") or
            !method_exists("Config", "current") or
            !isset(Config::current()->locale) or
            !isset(Config::current()->chyrp_url)
        )
            exit("ERROR: ".strip_tags($body));

        # We need this for the pretty error page.
        $chyrp_url = fix(Config::current()->chyrp_url, true);

        # Set fallbacks.
        fallback($title, __("Error"));
        fallback($body, __("An unspecified error has occurred."));
        fallback($backtrace, array());

        # Redact and escape the backtrace for display.
        foreach ($backtrace as $index => &$trace) {
            if (!isset($trace["file"]) or !isset($trace["line"]))
                unset($backtrace[$index]);
            else
                $trace["file"] = fix(
                    str_replace(MAIN_DIR.DIR, "", $trace["file"]),
                    false,
                    true
                );
        }

        #---------------------------------------------
        # Output Starts
        #---------------------------------------------
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo strip_tags($title); ?></title>
        <meta name="viewport" content="width = 800">
        <style type="text/css">
            @font-face {
                font-family: 'Open Sans webfont';
                src: url('<?php echo $chyrp_url; ?>/fonts/OpenSans-Regular.woff') format('woff');
                font-weight: normal;
                font-style: normal;
            }
            @font-face {
                font-family: 'Open Sans webfont';
                src: url('<?php echo $chyrp_url; ?>/fonts/OpenSans-Bold.woff') format('woff');
                font-weight: bold;
                font-style: normal;
            }
            @font-face {
                font-family: 'Open Sans webfont';
                src: url('<?php echo $chyrp_url; ?>/fonts/OpenSans-Italic.woff') format('woff');
                font-weight: normal;
                font-style: italic;
            }
            @font-face {
                font-family: 'Open Sans webfont';
                src: url('<?php echo $chyrp_url; ?>/fonts/OpenSans-BoldItalic.woff') format('woff');
                font-weight: bold;
                font-style: italic;
            }
            @font-face {
                font-family: 'Cousine webfont';
                src: url('<?php echo $chyrp_url; ?>/fonts/Cousine-Regular.woff') format('woff');
                font-weight: normal;
                font-style: normal;
            }
            @font-face {
                font-family: 'Cousine webfont';
                src: url('<?php echo $chyrp_url; ?>/fonts/Cousine-Bold.woff') format('woff');
                font-weight: bold;
                font-style: normal;
            }
            @font-face {
                font-family: 'Cousine webfont';
                src: url('<?php echo $chyrp_url; ?>/fonts/Cousine-Italic.woff') format('woff');
                font-weight: normal;
                font-style: italic;
            }
            @font-face {
                font-family: 'Cousine webfont';
                src: url('<?php echo $chyrp_url; ?>/fonts/Cousine-BoldItalic.woff') format('woff');
                font-weight: bold;
                font-style: italic;
            }
            *::selection {
                color: #ffffff;
                background-color: #ff7f00;
            }
            html {
                font-size: 16px;
            }
            html, body, ul, ol, li,
            h1, h2, h3, h4, h5, h6,
            form, fieldset, a, p, pre {
                margin: 0em;
                padding: 0em;
                border: 0em;
            }
            body {
                font-size: 1rem;
                font-family: "Open Sans webfont", sans-serif;
                line-height: 1.5;
                color: #1f1f23;
                background: #efefef;
                margin: 2rem;
            }
            h1 {
                font-size: 2em;
                font-weight: bold;
                margin: 1rem 0rem;
                text-align: center;
            }
            h2 {
                font-size: 1.5em;
                font-weight: bold;
                text-align: center;
                margin: 1rem 0rem;
            }
            h3 {
                font-size: 1em;
                font-weight: bold;
                margin: 1rem 0rem;
                border-bottom: 1px solid #cfcfcf;
            }
            p {
                margin-bottom: 1rem;
            }
            p:empty {
                margin-bottom: 0rem;
            }
            pre {
                font-family: "Cousine webfont", monospace;
                font-size: 0.85em;
                background-color: #efefef;
                margin: 1rem 0rem;
                padding: 1rem;
                overflow-x: auto;
            }
            code {
                font-family: "Cousine webfont", monospace;
                font-size: 0.85em;
                background-color: #efefef;
                padding: 0px 2px;
                border: 1px solid #cfcfcf;
                vertical-align: bottom;
            }
            pre > code {
                font-size: 0.85rem;
                display: block;
                border: none;
                padding: 0px;
            }
            strong {
                font-weight: normal;
                color: #c11600;
            }
            ul, ol {
                margin: 0rem 0rem 2rem 2rem;
                list-style-position: outside;
            }
            ol.backtrace {
                margin-top: 0.5rem;
            }
            a:link,
            a:visited {
                color: #1f1f23;
                text-decoration: underline;
                text-underline-offset: 0.125em;
            }
            a:focus {
                outline: #ff7f00 dashed 2px;
                outline-offset: 1px;
            }
            a:hover,
            a:focus,
            a:active {
                color: #1e57ba;
                text-decoration: underline;
                text-underline-offset: 0.125em;
            }
            a.big,
            button {
                box-sizing: border-box;
                display: block;
                clear: both;
                font: inherit;
                font-size: 1.25em;
                text-align: center;
                color: #1f1f23;
                text-decoration: none;
                line-height: 1.5;
                margin: 1rem 0rem;
                padding: 0.4em 0.6em;
                background-color: #f2fbff;
                border: 2px solid #b8cdd9;
                border-radius: 0.3em;
                cursor: pointer;
            }
            button {
                width: 100%;
            }
            a.big:hover,
            button:hover,
            a.big:focus,
            button:focus,
            a.big:active,
            button:active {
                border-color: #1e57ba;
                outline: none;
            }
            hr {
                border: none;
                clear: both;
                border-top: 1px solid #cfcfcf;
                margin: 2rem 0rem;
            }
            aside {
                margin-bottom: 1rem;
                padding: 0.5rem 1rem;
                border: 1px solid #e5d7a1;
                border-radius: 0.25em;
                background-color: #fffecd;
            }
            .window {
                width: 30rem;
                background: #ffffff;
                padding: 2rem;
                margin: 0rem auto 0rem auto;
                border-radius: 2rem;
            }
            .window > *:first-child {
                margin-top: 0rem;
            }
            .window > *:last-child {
                margin-bottom: 0rem;
            }
            @media (prefers-color-scheme: dark) {
                body {
                    color: #ffffff;
                    background-color: #1f1f23;
                }
                .window {
                    color: #1f1f23;
                    background-color: #dfdfdf;
                }
                hr {
                    border-color: #afafaf;
                }
                aside {
                    border-color: #afafaf;
                }
                pre {
                    background-color: #cfcfcf;
                }
                code {
                    background-color: #cfcfcf;
                    border-color: #afafaf;
                }
            }
        </style>
    </head>
    <body>
        <div class="window">
            <h1><?php echo sanitize_html($title); ?></h1>
            <div role="alert" class="message">
                <?php echo sanitize_html($body); ?>
            <?php if (!empty($backtrace) and DEBUG): ?>
                <h3><?php echo __("Backtrace"); ?></h3>
                <ol class="backtrace">
                <?php foreach ($backtrace as $trace): ?>
                    <li><code><?php echo _f("%s on line %d", array($trace["file"], (int) $trace["line"])); ?></code></li>
                <?php endforeach; ?>
                </ol>
            <?php endif; ?>
            <?php if (!logged_in() and ADMIN and $code == 403): ?>
                <a href="<?php echo $chyrp_url.'/admin/?action=login'; ?>" class="big login"><?php echo __("Log in"); ?></a>
            <?php endif; ?>
            </div>
        </div>
    </body>
</html>
<?php
        #---------------------------------------------
        # Output Ends
        #---------------------------------------------

        # Terminate execution.
        exit;
    }
