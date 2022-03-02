<?php

namespace App\EventSubscriber;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateAnArticleFormSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData'
        ];
    }

    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();

        $userRoles = $form->getConfig()->getOption('user_roles');

        if (in_array('ROLE_ADMIN', $userRoles, true)) {
            $form->add('save', SubmitType::class, [
                'label' => 'Sauvegarder cet article en brouillon',
                'attr'  => [
                    'class' => 'btn btn-warning'
                ]
            ]);
        }
    }
}
