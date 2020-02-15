# Hello World
This is a sample plugin built for Mautic 2 on the [Integrations plugin](https://github.com/mautic-inc/plugin-integrations).

A version for Mautic 3 can be found [here](https://github.com/mautic-inc/plugin-helloworld/tree/mautic-3).

However, it can be used as an example in combination with the [Integrations plugin wiki](https://github.com/mautic-inc/plugin-integrations/wiki).

This plugin has examples for

* Plugin migrations
* OAuth2 client credentials client
* Configuration UI interfaces
* Syncing Mautic contacts and companies

## Mocked Responses
The following places have mocked code to simulate a working plugin:

- \MauticPlugin\HelloWorldBundle\Connection\Config::setIntegrationConfiguration()
- \MauticPlugin\HelloWorldBundle\Connection\Client::getClient()
- \MauticPlugin\HelloWorldBundle\Connection\MockedHandler