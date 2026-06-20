<?php
declare(strict_types=1);

namespace AdvancedIdeasMechanics\MezzioGaMeasurementProtocol\Middleware;

use Psr\Container\ContainerInterface;
class MezzioGaMeasurementProtocolFactory
{
    public function __invoke(ContainerInterface $container): MezzioGaMeasurementProtocolMiddleware
    {
        // Pull configs from your global config array
        $config = $container->has('config') ? $container->get('config') : [];
        $gaConfig = $config['google_analytics'] ?? [];

        return new MezzioGaMeasurementProtocolMiddleware(
            measurementId: $gaConfig['measurement_id'] ?? '',
            apiSecret: $gaConfig['api_secret'] ?? '',
            debug: $gaConfig['debug'] ?? false,
            pageTitle: $gaConfig['page_title'] ?? 'Mezzio GA MP'
        );
    }
}