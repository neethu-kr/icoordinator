<?php

namespace iCoordinator\Controller;

use OAuth2\Server;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthController extends AbstractRestController
{

    private function getBrand()
    {
        return getenv('BRAND');
    }
    private function unparseWithQuery($parsed_url, $new_query)
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
    public function tokenAction(Request $request, Response $response, $args)
    {
        /** @var Server $server */
        $server = $this->getContainer()->get('OAuthServer');

        // Workaround to prevent zapier refresh token from expiring
        $data = $request->getParsedBody();
        if ($data['client_id'] == 'zapier' || $_REQUEST['client_id'] == 'zapier') {
            $server->setConfig('always_issue_new_refresh_token', true);
        }

        /* @var \OAuth2\Response */
        $oResp = $server->handleTokenRequest(\OAuth2\Request::createFromGlobals());

        foreach ($oResp->getHttpHeaders() as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response->withJson($oResp->getParameters(), $oResp->getStatusCode());
    }

    public function signUpAction(Request $request, Response $response, $args)
    {
    }
    public function authorizeAction(Request $request, Response $response, $args)
    {
        $state = "";
        /** @var Server $server */
        $server = $this->getContainer()->get('OAuthServer');

        $oReq = \OAuth2\Request::createFromGlobals();
        if (!count($oReq->query)) {
            $oReq->query = $oReq->request;
        }
        if (!count($oReq->request)) {
            $oReq->request = $oReq->query;
        }
        $oResp = new \OAuth2\Response();

        if ($request->isPost()) {
            $is_authorized = false;
            if (!$server->grantAccessToken($oReq, $oRespUserPwd = new \OAuth2\Response())) {
                return $response->withJson($oRespUserPwd->getParameters(), $oRespUserPwd->getStatusCode());
            } else {
                $is_authorized = true;
                $userService = $this->getContainer()->get('UserService');
                $user = $userService->getUserByEmail($oReq->request('username'));
            }
            if ($server->validateAuthorizeRequest($oReq, $oResp)) {
                $server->handleAuthorizeRequest($oReq, $oResp, $is_authorized, $user->getId());
            }

            foreach ($oResp->getHttpHeaders() as $key => $value) {
                $response = $response->withHeader($key, $value);
            }

            return $response->withJson($oResp->getParameters(), $oResp->getStatusCode());
        } else {
            if (!isset($_GET['client_id'])) {
                $oResp->setError(400, 'invalid_client', "No client id supplied");
                return $response->withJson($oResp->getParameters(), $oResp->getStatusCode());
            }
            if (!isset($_GET['redirect_uri'])) {
                $oResp->setError(400, 'invalid_redirect_uri', "No redirect uri supplied");
                return $response->withJson($oResp->getParameters(), $oResp->getStatusCode());
            }
            if (!isset($_GET['response_type'])) {
                $oResp->setError(400, 'invalid_response_code', "No response code supplied");
                return $response->withJson($oResp->getParameters(), $oResp->getStatusCode());
            }
            if (isset($_GET['state'])) {
                $state = $_GET['state'];
            }

            $body = $response->getBody();
            $body->write(
                '<!DOCTYPE html>
<html>
	<head>
		<title>'.getenv('BRAND_NAME').'</title>
		<meta charset="UTF-8">
		<meta content="user-scalable=yes, width=device-width, initial-scale=1.0" name="viewport">
		<link type="image/png" href="'.getenv('WEB_BASE_URL').'/resources/images/favicon_'.
                $this->getBrand().'.png" rel="shortcut icon">
		<style>
			html {
				height: 100%;
			}
			body {
				background-image: url(\''.getenv('WEB_BASE_URL').'/resources/images/ic_login_background.jpg\');
				background-position: center top;
				background-repeat: no-repeat;
				background-size: cover;
				font-family: helvetica, arial, verdana, sans-serif;
				font-size: 16px;
				font-weight: 300;
				line-height: 20px;
			}
			div#form-container {
				background: #2a6c82;
				color: #ffffff;
				height: 470px;
				left: 50%;
				margin: -235px auto auto -185px;
				padding: 15px;
				position: fixed;
				top: 50%;
				width: 370px;
			}
			div#form-container form {
				width: 95%;
			}
			div#form-container input.ic-field {
				margin: 3px 0 45px 0;
				padding: 3px 6px 2px 6px;
				width: 100%;
			}
			div#image-block {
				font-size: 24px;
				line-height: 48px;
				margin: 50px 0;
				text-align: center;
				width: 100%;
			}
			div#image-block img {
				height: 75px;
				width: 300px;
			}
			input.ic-button {
				background-color: #b16a1f;
    			border: 1px solid #b16a1f;
    			border-radius: 3px;
				color: #ffffff;
				font-size: 16px;
				height: 38px;
				left: 275px;
				position: relative;
				text-align: center;
				text-decoration: none;
				width: 89px;
			}
		</style>
	</head>
	<body>
		<div>
			<div id="form-container">
				<div id="image-block">
					<img src="'.getenv('WEB_BASE_URL').'/resources/images/logos/'.$this->getBrand().
                '/logo_white.svg" alt="'.getenv('BRAND_NAME').'" /><br/>
					Welcome, please log in
				</div>
				<form action="/auth/authorize" method="post">
					<input type="hidden" name="client_id" value="'.$_GET['client_id'].'">
					<input type="hidden" name="grant_type" value="password">
					<input type="hidden" name="redirect_uri" value="'.$_GET['redirect_uri'].'">
					<input type="hidden" name="response_type" value="'.$_GET['response_type'].'">'.
                    ($state != "" ? '
                    <input type="hidden" name="state" value="'.$state.'">':'').'
					<label for="ic-username-field">E-mail:</label>
					<input class="ic-field" id="ic-username-field" type="text" name="username" value="">
					<label for="ic-password-field">Password:</label>
					<input class="ic-field" id="ic-password-field" type="text" name="password" value="">
					<input class="ic-button" type="submit" value="Log in">
				</form>
			</div>
		</div>
  </body>
</html>'
            );
            return $body;
        }
    }

    public function protectedAction(Request $request, Response $response, $args)
    {
        $auth = $this->getContainer()->get('auth');
        return $response->withJson(array('user_id' => $auth->getIdentity()));
    }
}
