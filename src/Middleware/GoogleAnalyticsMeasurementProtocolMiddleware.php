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

        // Emit HTTP Status Code Line
        header(sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true, $response->getStatusCode());

        // Emit Headers
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // Clean out any nested output buffers safely
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        // Print the final HTML payload to the browser
        echo $response->getBody()->__toString();

        // Check environment behavior
        if (function_exists('fastcgi_finish_request')) {
            // Perfect FPM optimization: kill the connection immediately
            fastcgi_finish_request();
        } else {
            // mod_php (Apache) fallback: force the web server to push out the echo data
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1'); // Disable apache compression so it flushes instantly
            }
            header('Content-Length: ' . $response->getBody()->getSize());
            header('Connection: close');
            flush();
        }

        // 5. BACKGROUND WORK: Trigger GA4 over the network
        try {
            $analytics = Analytics::new($this->measurementId, $this->apiSecret, $this->debug);

            $pageView = PageView::new()
                ->setPageLocation((string) $request->getUri())
                ->setPageTitle($this->pageTitle . ': ' . $request->getUri()->getHost());

            $analytics->setClientId($clientId)
                ->addEvent($pageView)
                ->post();
        } catch (\Throwable $e) {
            // Fail silently. The user already has their page, so network drops won't break things.
        }

        // 6. Return a blank placeholder response.
        // Because we shortcutted emission, Mezzio's outer runner won't conflict.
        return new Response();
    }
}