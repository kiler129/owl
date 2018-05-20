<?php
declare(strict_types=1);

namespace noFlash\Owl\Http;

use noFlash\Owl\Kernel;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\Response;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This serves as a front controller for the application ran via React server. Its general role is to work like
 * an index.php file in normal environment.
 */
class FrontController
{
    /**
     * @var Kernel HTTP application kernel
     */
    private $kernel;

    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ThrowableResponseFactory
     */
    private $throwableResponseFactory;

    public function __construct(KernelInterface $kernel, HttpFoundationFactory $httpFoundationFactory, LoggerInterface $logger, ThrowableResponseFactory $throwableResponseFactory)
    {
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->logger = $logger;
        $this->throwableResponseFactory = $throwableResponseFactory;
        $this->kernel = $kernel;
    }

    public function handleHttpRequest(ServerRequestInterface $request)
    {
        try {
            $this->kernel->reboot(null);

            $symfonyRequest = $this->httpFoundationFactory->createRequest($request);

            $symfonyResponse = $this->kernel->handle($symfonyRequest);

            //Theoretically this should be executed after response is passed, but I don't see such option in ReactPHP
            $this->kernel->terminate($symfonyRequest, $symfonyResponse);

            return new Response(
                $symfonyResponse->getStatusCode(),
                $symfonyResponse->headers->all(),
                $symfonyResponse->getContent()
            );

        } catch (\Throwable $t) {
            $this->logger->error(sprintf('Request failed due to %s: %s', get_class($t), $t->getMessage()));

            return $this->throwableResponseFactory->createFromThrowable($t);
        }
    }
}
