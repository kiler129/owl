<?php
declare(strict_types=1);

namespace noFlash\Owl\Http;

use React\Http\Response;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler;

class ThrowableResponseFactory
{
    /**
     * @var ExceptionHandler
     */
    private $exceptionHandler;

    public function __construct(ExceptionHandler $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
    }

    public function createFromThrowable(\Throwable $t): Response
    {
        if (!$t instanceof FlattenException) {
            $t = FlattenException::create($t);
        }

        $body = $this->decorate($this->exceptionHandler->getContent($t), $this->exceptionHandler->getStylesheet($t));

        return new Response(500, ['Content-Type' =>  'text/html'], $body);
    }

    /**
     * Wraps exception into valid HTML structure.
     *
     * This method is almost 1:1 copy of private \Symfony\Component\Debug\ExceptionHandler::decorate
     *
     * @param string $content
     * @param string $css
     *
     * @return string
     */
    private function decorate(string $content, string $css): string
    {
        return <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta name="robots" content="noindex,nofollow" />
        <style>$css</style>
    </head>
    <body>
        $content
    </body>
</html>
EOF;
    }
}
