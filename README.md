# SensioLabs Connect SDK

## About

This is the official SDK for the SensioLabs Connect API. It works for the public
API or with a registered OAuth application. To register an application, please
go to your [SensioLabs Connect Account](https://connect.sensiolabs.com).

## Installation

To install the SDK, run the command below and you will get the latest version:

    composer require sensiolabs/connect

## Upgrade

### Version 4:

BC Break: As of version 4, Connect does not use [Buzz](https://github.com/kriswallsmith/Buzz) as HTTP client anymore. 
It now uses [Guzzle 3](https://github.com/guzzle/guzzle3) that is  compatible with Guzzle 4, but PHP 5.3 compatible.

## Usage

### OAuth

This part will show you how to include OAuth authentication within a Silex App.

Warning: We take for granted that you already have registered your app on
[SensioLabs Connect](https://connect.sensiolabs.com) and that you're in
possession of your `application_id`, `application_secret` and `scope`.

1. Configure your silex app with the data we gave us at app registration.

```php
// index.php
use SensioLabs\Connect\Api\Api;
use SensioLabs\Connect\OAuthConsumer;

$app = new Silex\Application();
$app['connect_id'] = 'application_id';
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());

$app['connect_secret'] = 'application_secret';
// List of scope copy-pasted from your application page on SensioLabs Connect
$app['connect_scope'] = array(
    'SCOPE_ADDITIONAL_EMAILS',
    'SCOPE_BIRTHDAY',
    'SCOPE_EMAIL',
    'SCOPE_LOCATION',
    'SCOPE_PRIVATE_MEMBERSHIPS',
    'SCOPE_PRIVATE_PROJECTS',
    'SCOPE_PUBLIC',
    'SCOPE_SSH_KEYS',
);

$app['connect_consumer'] = new OAuthConsumer(
    $app['connect_id'],
    $app['connect_secret'],
    implode(' ', $app['connect_scope']) // scope MUST be space separated
);
$app['connect_api'] = new Api();
```

This done. We can now move on to the second step.

2. We need to create two controllers to handle the OAuth2 Three-Legged worflow.

The first controller goal is to redirect the user to SensioLabs Connect in
order to ask him for the authorization that your app will use his data. This
controller will be bound to the `connect_auth` route. In your template,
you'll need to create a link to this route.

```php
// index.php
$app->get('/connect/new', function () use ($app) {
    $callback = $app['url_generator']->generate('connect_callback', array(), true);
    $url = $app['connect_consumer']->getAuthorizationUri($callback);
    
    return $app->redirect($url);
})->bind('connect_auth');
```

The second controller is the one that will welcome the user after SensioLabs
Connect redirected him to your application. When registering your client,
you'll have to provide the exact absolute URL that points to this
controller.

```php
$app->get('/connect/callback', function (Request $request) use ($app) {
    // There was an error during the workflow.
    if ($request->get('error')) {
        throw new \RuntimeException($request->get('error_description'));
    }

    // Everything went fine, you can now request an access token.
    try {
        $data = $app['connect_consumer']->requestAccessToken($app['url_generator']->generate('connect_callback', array(), true), $request->get('code'));
    } catch (OAuthException $e) {
        throw $e;
    }

    // At this point, we have an access token and we can use the SDK to request the API
    $app['connect_api']->setAccessToken($data['access_token']); // All further request will be done with this access token
    $root = $app['connect_api']->getRoot();
    $user = $root->getCurrentUser();
    $user->getBadges()->refresh();

    $app['session']->start();
    $app['session']->set('connect_access_token', $data['access_token']);
    $app['session']->set('connect_user', $user);

    return $app->redirect('/');
})->bind('connect_callback');
```

3. Create a link from your template

In a template, you can use the following snippet of code to render a
SensioLabsConnect button:

```html
<a href="#" class="connect-with-sensiolabs">
    <span>Connect With Sensiolabs</span>
</a>
```

And include the following CSS file: `https://connect.sensiolabs.com/css/sln.css`

Et voilà! Your application can now use SensioLabs Connect as an authentication
method!

### The API

The SensioLabs Connect API is RESTFul and (tries to) conforms to the HATEOAS
principle.

Here are some useful recipies.

1. Search

```php
$root = $api->getRoot();

// Will search for project with 'symfony' query
$projects = $root->getProjects('symfony');

// Same for users
$users = $root->getUsers('fab');

// Same for clubs
$clubs = $root->getClubs('sensio');
```

2. Create a new club

```php
// Create Club
$club = new \SensioLabs\Connect\Api\Entity\Club();
$club->setName("SensioLabs France");
$club->setSlug("sensiolabs-api-users");
$club->setType(\SensioLabs\Connect\Api\Entity\Club::TYPE_USER_GROUP);
$club->setEmail('foobar@example.com');
$club->setDescription('This is the best description i found.');
$club->setCity("Paris");
$club->setCountry("France");
$club->setUrl('http://sensiolabs.com');

// Save new Club
$app['connect_api']->setAccessToken($app['session']->get('connect_access_token'));
$root = $app['connect_api']->getRoot();
$response = $root->getClubs()->submitForm($club);
```

3. Edit authenticated user

```php
$app['connect_api']->setAccessToken($app['session']->get('connect_access_token'));
$root = $app['connect_api']->getRoot();
$user = $root->getCurrentUser();
$user->setBiography("I'm sexy and I know it.");
$user->submitForm();
```

As you can see by these examples, you always have to to go through the API Root
to make an action. This is because the API is discoverable and that the SDK
should not know anything beside the API's entry point.

### HTTP client customization

SensiolabsConnect relies on Guzzle HTTP client. It comes configured with an HTTP cache adapter and the backoff plugin.
However, you can disable these plugins and and yours:

All the example above use `SensioLabs\Connect\OAuthConsumer`. However, these options are also available using the
`SensioLabs\Connect\Api\Api`.

1. Disabling/Enabling plugins:

```php
use SensioLabs\Connect\OAuthConsumer;

$consumer = new OAuthConsumer($id, $secret, $scope, array(
   'cache_options' => false,                                   // disables the cache plugin
   'backoff_options' => false,                                 // disables the backoff plugin
   'plugins' => array(new Guzzle\Plugin\History\HistoryPlugin) // adds an array of plugins
));
```

2. Configure embedded plugins:

You can also configure the cache and backoff plugins:

```php
use SensioLabs\Connect\OAuthConsumer;

$consumer = new OAuthConsumer($id, $secret, $scope, array(
   'cache_options' => new DoctrineCacheAdapter(new FilesystemCache('/path/to/cache/files')), 
   'backoff_options' => array('max_retries' => 5),
));
```

Notes:
 - `cache_options` accepts any of the Guzzle Cache options, see https://github.com/guzzle/plugin-cache/blob/master/CachePlugin.php#L41-L50
 - `backoff_options` has three parameters: `max_retries`, `http_codes` and `curl_codes`.

3. Customize HTTP connection

There are three options to customize the HTTP connection: `timeout`, `connect_timeout` and `proxy`:

```php
use SensioLabs\Connect\OAuthConsumer;

$consumer = new OAuthConsumer($id, $secret, $scope, array(
   'timeout' => 4,                                        // maximum number of seconds to allow for an entire transfer to take place before timing out
   'connect_timeout' => 3,                                // maximum number of seconds to wait while trying to connect
   'proxy' => 'http://username:password@192.168.16.1:10', // specify an HTTP proxy
));
```

## License

This library is licensed under the MIT license.
