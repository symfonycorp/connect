# SymfonyConnect SDK

## About

This is the official SDK for the SymfonyConnect API. It works for the public
API or with a registered OAuth application. To register an application, please
go to your [SymfonyConnect Account](https://connect.symfony.com).

## Installation

To install the SDK, run the command below and you will get the latest version:

```bash
composer require symfonycorp/connect
```

## Usage

### OAuth

This part will show you how to include OAuth authentication within a Silex App.

Warning: We take for granted that you already have registered your app on
[SymfonyConnect](https://connect.symfony.com) and that you're in
possession of your `application_id`, `application_secret` and `scope`.

1. Configure your silex app with the data we gave us at app registration.

    ```php
    // index.php
    use SymfonyCorp\Connect\Api\Api;
    use SymfonyCorp\Connect\OAuthConsumer;
    
    $app = new Silex\Application();
    $app['connect_id'] = 'application_id';
    $app->register(new Silex\Provider\UrlGeneratorServiceProvider());
    $app->register(new Silex\Provider\SessionServiceProvider());
    
    $app['connect_secret'] = 'application_secret';
    // List of scope copy-pasted from your application page on SymfonyConnect
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

   The first controller goal is to redirect the user to SymfonyConnect in
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

    The second controller is the one that will welcome the user after
    SymfonyConnect redirected him to your application. When registering your
    client, you'll have to provide the exact absolute URL that points to this
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
   SymfonyConnect button:

    ```html
    <a href="#" class="connect-with-symfonyconnect">
        <span>Connect With SymfonyConnect</span>
    </a>
    ```

   And include the following CSS file: `https://connect.symfony.com/css/sln.css`

Et voilà! Your application can now use SymfonyConnect as an authentication
method!

### The API

The SymfonyConnect API is RESTFul and (tries to) conforms to the HATEOAS
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
    $clubs = $root->getClubs('symfony');
    ```

2. Create a new club

    ```php
    // Create Club
    $club = new \SymfonyCorp\Connect\Api\Entity\Club();
    $club->setName("Symfony France");
    $club->setSlug("symfony-api-users");
    $club->setType(\SymfonyCorp\Connect\Api\Entity\Club::TYPE_USER_GROUP);
    $club->setEmail('foobar@example.com');
    $club->setDescription('This is the best description i found.');
    $club->setCity("Paris");
    $club->setCountry("France");
    $club->setUrl('http://symfony.com');
    
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

## License

This library is licensed under the MIT license.
