<?php


namespace MauticPlugin\HelloWorldBundle\Controller\Mocks;


use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\HelloWorldBundle\Integration\Config;
use MauticPlugin\HelloWorldBundle\Integration\HelloWorldIntegration;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * This is a mocked example of an API endpoint that does something super simple such as returning the details of the authorized session or to
 * fetch a simple list that doesn't require a lot of heavy lifting for the 3rd party service. The idea is to cause the Guzzle client to execute
 * a call to teh API in order to trigger the middleware process of exchanging the auth code for an access token from the authenticating service.
 */
class UserController extends Controller
{
    /**
     * @var Config
     */
    private $config;

    /**
     * UserController constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function mockAction(Request $request): Response
    {
        $bearerHeader = $request->headers->get('authorization');
        preg_match('#Bearer\s(.*)#', $bearerHeader, $match);
        $foundToken = $match[1] ?? null;

        $apiKeys       = $this->config->getApiKeys();
        $expectedToken = $apiKeys['access_token'] ?? null;

        if ($foundToken && $foundToken === $expectedToken) {
            return new JsonResponse(
                [
                    'logged_in' => 1,
                ]
            );
        }

        return new Response('access token invalid', 503);
    }
}