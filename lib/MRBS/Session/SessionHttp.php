<?php
namespace MRBS\Session;

use MRBS\User;

// Get user identity using the HTTP basic authentication


class SessionHttp extends SessionWithLogin
{

  public function authGet(?string $target_url=null, ?string $returl=null, ?string $error=null, bool $raw=false) : void
  {
    global $auth;

    header("WWW-Authenticate: Basic realm=\"$auth[realm]\"");
    header("HTTP/1.0 401 Unauthorized");
  }


  public function getCurrentUser() : ?User
  {
    global $server;

    if (!isset($server['PHP_AUTH_USER']))
    {
      return null;
    }

    // Trim any whitespace because PHP_AUTH_USER can contain it.
    $php_auth_user = trim($server['PHP_AUTH_USER']);

    if ($php_auth_user === '')
    {
      return null;
    }

    if (\MRBS\auth()->validateUser($php_auth_user, self::getAuthPassword()) === false)
    {
      return null;
    }

    return \MRBS\auth()->getUser($php_auth_user);
  }


  public function getLogoffFormParams() : ?array
  {
    // Just return null - you can't logoff
    // (well, there are ways of achieving a logoff but we haven't implemented them)
    return null;
  }


  private static function getAuthPassword() : ?string
  {
    global $server;

    return (isset($server['PHP_AUTH_PW'])) ? $server['PHP_AUTH_PW'] : null;
  }
}
