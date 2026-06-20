<?php
namespace AdvancedIdeasMechanics\MezzioGaMeasurementProtocol;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                // Point the middleware directly to its dedicated factory class string
                Middleware\GoogleAnalyticsMeasurementProtocolMiddleware::class => Middleware\GoogleAnalyticsMeasurementProtocolMiddlewareFactory::class,
            ],
        ];
    }
}