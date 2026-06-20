<?php

declare(strict_types=1);

namespace AdvancedIdeasMechanics\MezzioGaMeasurementProtocol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use AlexWestergaard\PhpGa4\Analytics;
use AlexWestergaard\PhpGa4\Event\PageView;

class GoogleAnalyticsMeasurementProtocolMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $measurementId,
        private string $apiSecret,
        private bool $debug = false,
        private string $cookieName,
        private string $pageTitle
    ) {}
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check for the tracking cookie before handling the response
        $cookies = $request->getCookieParams();
        $cookieName = $this->cookieName;
        $setCookie = false;

        if (isset($cookies[$cookieName])) {
            $clientId = $cookies[$cookieName];
        } else {
            $clientId = bin2hex(random_bytes(16));
            $setCookie = true;
        }

        // Let Mezzio execute your template handler and build the HTML response
        $response = $handler->handle($request);

        if ($setCookie) {
            $response = $response->withAddedHeader(
                'Set-Cookie', sprintf('%s=%s; Path=/; Max-Age=31536000; HttpOnly; SameSite=Lax', $cookieName, $clientId)
            );
        }

        // work disconnected from the browser, requires fpm/Nginx
        if (function_exists('fastcgi_finish_request')) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            fastcgi_finish_request();
        }

        // Run in the background, to not freeze for the client.
        try {
            $analytics = Analytics::new($this->measurementId, $this->apiSecret, $this->debug);

            $pageView = PageView::new()
                ->setPageLocation((string) $request->getUri())
                ->setPageTitle($this->pageTitle .': ' . $request->getUri()->getHost());

            $analytics->setClientId($clientId)
                ->addEvent($pageView)
                ->post();

        } catch (\Throwable $e) {

        }

        return $response;
    }
}