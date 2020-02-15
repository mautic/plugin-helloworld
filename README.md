# Hello World
This is a sample plugin built for Mautic 2 (PHP 7.2 required) on the [Integrations plugin](https://github.com/mautic-inc/plugin-integrations).

A version for Mautic 3 can be found [here](https://github.com/mautic-inc/plugin-helloworld/tree/mautic-3).

This can be used as an example in combination with the [Integrations plugin wiki](https://github.com/mautic-inc/plugin-integrations/wiki). Note that the Integrations plugin does not work out of the box with Mautic 2 but requires additional installation steps. See the wiki for more information.

This plugin has examples for:
* Plugin migrations
* OAuth2 client credentials client
* Configuration UI interfaces
* Syncing Mautic contacts and companies

## Mocked Responses
The following places have mocked code to simulate a working plugin:

- \MauticPlugin\HelloWorldBundle\Connection\Config::setIntegrationConfiguration()
- \MauticPlugin\HelloWorldBundle\Connection\Client::getClient()
- \MauticPlugin\HelloWorldBundle\Connection\MockedHandler

## Code Excpectations and Standards
This plugin also tries to exemplify code standards and expectations. 
* Code should be clean (see [https://github.com/jupeter/clean-code-php](https://github.com/jupeter/clean-code-php))
* Code should be well covered with unit and/or functional tests
* Code should meet CS standards (includes `symplify/easy-coding-standard` which can be ran with `composer fixcs`)
* Code should pass phpstan standards (run `composer phpstan`)