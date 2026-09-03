<?php

namespace Drupal\user_crud\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserCrudLoginSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 20],
        ];
    }

    /**
     * Redirect logged-in Admin1 away from the login page to User CRUD.
     */
    public function onRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $current_user = \Drupal::currentUser();

        // Only target authenticated user 'Admin1'.
        if ($current_user->isAnonymous() || $current_user->getAccountName() !== 'Admin1') {
            return;
        }

        // Check if the current route is the user login route.
        $route_name = \Drupal::routeMatch()->getRouteName();
        if ($route_name === 'user.login' || \Drupal::service('path.current')->getPath() === '/user/login') {
            $url = Url::fromRoute('user_crud.list')->toString();
            $response = new RedirectResponse($url);
            $event->setResponse($response);
        }
    }
}