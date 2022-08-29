<?php

namespace iCoordinator;

use Carbon\Carbon;
use iCoordinator\Config\Route\AuthRouteConfig;
use iCoordinator\PHPUnit\DbUnit\DataSet\ArrayDataSet;
use iCoordinator\PHPUnit\TestCase;

class OAuthTest extends TestCase
{
    const USERNAME = 'test@user.com';
    const PASSWORD = 'password';
    const PUBLIC_CLIENT_ID = 'public_test';
    const PUBLIC_CLIENT_SECRET = '';
    const PRIVATE_CLIENT_ID = 'private_test';
    const PRIVATE_CLIENT_SECRET = 'private_test';
    const REDIRECT_URI = 'http://fake.dev';
    const USER_ID = 1;
    const ACCESS_TOKEN = 'efd72a957361561d7d2a45baad74ed04458d08cf';
    const REFRESH_TOKEN = 'eced5950327b20a5a7f34d496fe44b948f1c3682';
    const AUTHORIZATION_CODE = '57cd1465fd13857060698e82d8c194d1152bbbc6';
    const SCOPE = 'test';

    private static $passwordHash;

    public function setUp(): void
    {
        if (self::$passwordHash === null) {
            self::$passwordHash = password_hash(self::PASSWORD, PASSWORD_DEFAULT);
        }
        parent::setUp();
    }

    public function testGrantTypeClientCredentials()
    {
        $resposne = $this->post(
            $this->urlFor(AuthRouteConfig::ROUTE_AUTH_TOKEN),
            array(
                'grant_type' => 'client_credentials',
                'client_id' => self::PRIVATE_CLIENT_ID,
                'client_secret' => self::PRIVATE_CLIENT_SECRET,
                'scope' => self::SCOPE
            )
        );

        $this->assertEquals(200, $resposne->getStatusCode());
    }

    public function testGrantTypePassword()
    {
        $response = $this->post(
            $this->urlFor(AuthRouteConfig::ROUTE_AUTH_TOKEN),
            array(
                'grant_type' => 'password',
                'client_id' => self::PUBLIC_CLIENT_ID,
                'username' => self::USERNAME,
                'password' => self::PASSWORD
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGrantTypeRefreshToken()
    {
        $response = $this->post($this->urlFor(AuthRouteConfig::ROUTE_AUTH_TOKEN),
            array(
                'grant_type' => 'refresh_token',
                'client_id' => self::PUBLIC_CLIENT_ID,
                'refresh_token' => self::REFRESH_TOKEN
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGrantTypeAuthorizationCode1()
    {
        $response = $this->get(
            $this->urlFor(AuthRouteConfig::ROUTE_AUTH_AUTHORIZE, array(), array(
                'response_type' => 'code',
                'client_id' => self::PUBLIC_CLIENT_ID,
                'redirect_uri' => urlencode(self::REDIRECT_URI)
            ))
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGrantTypeAuthorizationCode2()
    {
        $response = $this->post(
            $this->urlFor(AuthRouteConfig::ROUTE_AUTH_AUTHORIZE, array(), array(
                'response_type' => 'code',
                'grant_type' => 'password',
                'client_id' => self::PUBLIC_CLIENT_ID,
                'redirect_uri' => self::REDIRECT_URI,
                'username' => self::USERNAME,
                'password' => self::PASSWORD
            ))
        );

        $this->assertEquals(302, $response->getStatusCode());

        $redirectLocation = $response->getHeaderLine('Location');
        list($requestUri, $queryString) = explode('?', $redirectLocation);
        $this->assertSame(self::REDIRECT_URI, $requestUri);

        parse_str($queryString, $params);
        $this->assertArrayHasKey('code', $params);
        $this->assertNotEmpty($params['code']);
    }

    public function testGrantTypeAuthorizationCode3()
    {
        $response = $this->post($this->urlFor(AuthRouteConfig::ROUTE_AUTH_TOKEN), array(
            'grant_type' => 'authorization_code',
            'client_id' => self::PUBLIC_CLIENT_ID,
            'code' => self::AUTHORIZATION_CODE,
            'redirect_uri' => self::REDIRECT_URI
        ));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testProtectedResourceAccessAllowed()
    {
        $response = $this->get(
            $this->urlFor(AuthRouteConfig::ROUTE_AUTH_PROTECTED_RESOURCE),
            array(),
            array(
                'Authorization' => 'Bearer ' . self::ACCESS_TOKEN
            )
        );

        $result = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::USER_ID, $result->user_id);
    }

    public function testProtectedResourceAccessForbidden()
    {
        $response = $this->get($this->urlFor(AuthRouteConfig::ROUTE_AUTH_PROTECTED_RESOURCE), array(
            'access_token' => 'invalid_token'
        ));

        $this->assertEquals(401, $response->getStatusCode());
    }

    protected function getDataSet()
    {
        return new ArrayDataSet(array(
            'oauth_clients' => array(
                array(
                    'client_id' => self::PUBLIC_CLIENT_ID,
                    'client_secret' => self::PUBLIC_CLIENT_SECRET,
                    'redirect_uri' => self::REDIRECT_URI
                ),
                array(
                    'client_id' => self::PRIVATE_CLIENT_ID,
                    'client_secret' => self::PRIVATE_CLIENT_SECRET,
                    'redirect_uri' => self::REDIRECT_URI
                ),
            ),
            'oauth_access_tokens' => array(),
            'users' => array(
                array(
                    'id' => self::USER_ID,
                    'email' => self::USERNAME,
                    'password' => self::$passwordHash,
                    'email_confirmed' => 1
                )
            ),
            'oauth_access_tokens' => array(
                array(
                    'access_token' => self::ACCESS_TOKEN,
                    'client_id' => self::PUBLIC_CLIENT_ID,
                    'user_id' => self::USER_ID,
                    'expires' => Carbon::now()->addDay()->toDateTimeString(),
                    'scope' => self::SCOPE
                )
            ),
            'oauth_refresh_tokens' => array(
                array(
                    'refresh_token' => self::REFRESH_TOKEN,
                    'client_id' => self::PUBLIC_CLIENT_ID,
                    'user_id' => self::USER_ID,
                    'expires' => Carbon::now()->addMonth()->toDateTimeString()
                )
            ),
            'oauth_authorization_codes' => array(
                array(
                    'authorization_code' => self::AUTHORIZATION_CODE,
                    'client_id' => self::PUBLIC_CLIENT_ID,
                    'user_id' => self::USER_ID,
                    'redirect_uri' => self::REDIRECT_URI,
                    'expires' => Carbon::now()->addMinute()->toDateTimeString(),
                    'scope' => null
                )
            ),
            'oauth_scopes' => array(
                array(
                    'scope' => self::SCOPE
                )
            )
        ));
    }
}
