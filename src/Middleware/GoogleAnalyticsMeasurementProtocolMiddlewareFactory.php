<?php
declare(strict_types=1);

namespace AdvancedIdeasMechanics\MezzioGaMeasurementProtocol\Middleware;

use Psr\Container\ContainerInterface;

class GoogleAnalyticsMeasurementProtocolMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): GoogleAnalyticsMeasurementProtocolMiddleware
    {
        // Pull configs from your global config array
        $config = $container->has('config') ? $container->get('config') : [];
        $gaConfig = $config['google_analytics'] ?? [];

        return new GoogleAnalyticsMeasurementProtocolMiddleware(
            measurementId: $gaConfig['measurement_id'] ?? '',
            apiSecret: $gaConfig['api_secret'] ?? '',
            debug: $gaConfig['debug'] ?? false,
            cookieName: $gaConfig['cookie_name'] ?? '_ga_uid',
            pageTitle: $gaConfig['page_title'] ?? 'Mezzio GA MP'
        );
    }
}