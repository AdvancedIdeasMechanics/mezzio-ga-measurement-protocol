# Mezzio Google Analytics Measurement Protocol #
Mezzio Middleware for Google Analytics Measurement Protocol

### Composer ###

`composer require advancedideasmechanics/mezzio-ga-measurement-protocol`

#### Use ####

For pipeline.php Middleware Use.

Recommend placing between $app->pipe(RouteMiddleware::class); and $app->pipe(ImplicitHeadMiddleware::class);

`$app->pipe(AdvancedIdeasMechanics\MezzioGaMeasurementProtocol\Middleware\GoogleAnalyticsMeasurementProtocolMiddleware::class);`

For route.php Middleware use.

`use AdvancedIdeasMechanics\MezzioGaMeasurementProtocol\Middleware\GoogleAnalyticsMeasurementProtocolMiddleware::class;`

`$app->get('/', [GoogleAnalyticsMeasurementProtocolMiddleware:class, App\Handler\HomePageHandler::class], 'home');`