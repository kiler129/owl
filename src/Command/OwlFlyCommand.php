<?php

namespace noFlash\Owl\Command;

use noFlash\Owl\Http\FrontController;
use noFlash\Owl\WebSocket\ChannelController;
use Psr\Log\LoggerInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as Reactor;
use React\Http\Server as ReactHttp;
use Ratchet\Http\HttpServer as RatchetHttp;


class OwlFlyCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected static $defaultName = 'owl:fly';

    /**
     * @var FrontController
     */
    private $httpHandler;

    /**
     * @var ChannelController
     */
    private $wsHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(FrontController $httpHandler, ChannelController $wsHandler, LoggerInterface $logger)
    {
        $this->httpHandler = $httpHandler;
        $this->wsHandler = $wsHandler;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Starts the application server')
            ->addArgument('http-address', InputArgument::OPTIONAL, 'IP where HTTP server is listening', '0.0.0.0')
            ->addArgument('http-port', InputArgument::OPTIONAL, 'Port where HTTP server is listening', 8888)
            ->addArgument('ws-address', InputArgument::OPTIONAL, 'IP where HTTP server is listening', '0.0.0.0')
            ->addArgument('ws-port', InputArgument::OPTIONAL, 'Port where WebSocket server is listening', 9999)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->logger->info('Starting server...');

        $loop = LoopFactory::create();
        $this->startHttp($input, $loop);
        $this->startWebSocket($input, $loop);

        $loop->run();
    }

    private function startHttp(InputInterface $input, LoopInterface $loop)
    {
        $this->logger->debug('Bootstrapping HTTP started');

        //$this->httpHandler->bootstrap();

        //@todo Add validation
        //@todo Add IPv6 support
        $httpListen = sprintf('%s:%s', $input->getArgument('http-address'), $input->getArgument('http-port'));

        $httpSock = new Reactor($httpListen, $loop);
        $httpServer = new ReactHttp([$this->httpHandler, 'handleHttpRequest']);

        $httpServer->listen($httpSock);

        $this->logger->notice('HTTP server running at ' . $httpSock->getAddress());
    }

    private function startWebSocket(InputInterface $input, LoopInterface $loop)
    {
        $this->logger->debug('Bootstrapping WS started');

        $wsListen = sprintf('%s:%s', $input->getArgument('ws-address'), $input->getArgument('ws-port'));

        $wsSock = new Reactor($wsListen, $loop);
        $wsHandler = new RatchetHttp(new WsServer($this->wsHandler));
        $ioServer = new IoServer($wsHandler, $wsSock, $loop);

        $this->logger->notice('WS server running at ' . $wsSock->getAddress());
    }
}
