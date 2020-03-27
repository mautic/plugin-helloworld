<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Controller\Mocks;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller returns fake tokens to demonstrate the oauth2 flow.
 */
class TokenController extends Controller
{
    public function mockAction(): Response
    {
        return new JsonResponse(
            [
                'access_token'  => EncryptionHelper::generateKey(),
                'expires_in'    => 3600,
                'refresh_token' => EncryptionHelper::generateKey(),
            ]
        );
    }
}
