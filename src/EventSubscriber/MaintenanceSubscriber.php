<?php

namespace App\EventSubscriber;

use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    private CONST AUTHORIZED_IP = '127.0.0.1';

    private Environment $twig;
    private string $maintenanceON;

    public function __construct(Environment $twig, string $maintenanceON)
    {
        $this->twig = $twig;
        $this->maintenanceON = $maintenanceON;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $userIp = $event->getRequest()->getClientIp();

        if (!file_exists($this->maintenanceON) || $userIp === self::AUTHORIZED_IP) {
            return;
        }

        $maintenanceTemplate = $this->twig->render('maintenance/site_under_maintenance.html.twig');

        $response = new Response($maintenanceTemplate, Response::HTTP_SERVICE_UNAVAILABLE);

        $event->setResponse($response);

        $event->stopPropagation();
    }
}
