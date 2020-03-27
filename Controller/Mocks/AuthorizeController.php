<?php


namespace MauticPlugin\HelloWorldBundle\Controller\Mocks;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller returns a fake code to demonstrate the OAuth2 authorization code grant flow
 */
class AuthorizeController extends Controller
{
    public function mockAction(Request $request): Response
    {
        $redirectUri = $request->get('redirect_uri');
        $code        = EncryptionHelper::generateKey();
        $state       = $request->get('state');

        return new RedirectResponse(sprintf('%s?code=%s&state=%s', $redirectUri, $code, $state));
    }
}