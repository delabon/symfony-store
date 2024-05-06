<?php

namespace App\Form;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\String\Slugger\SluggerInterface;

class FormListenerFactory
{
    public function __construct(
        private readonly Security $security,
        private readonly SluggerInterface $slugger
    )
    {
    }

    public function autoSlug(string $field): callable
    {
        return function (PreSubmitEvent $event) use ($field) {
            $data = $event->getData();

            if (empty($data['slug'])) {
                $data['slug'] = $this->slugger->slug(strtolower($data[$field]));
            }

            $event->setData($data);
        };
    }

    public function timestamps(): callable
    {
        return function (PostSubmitEvent $event) {
            $object = $event->getData();
            $object->setUpdatedAt(new \DateTimeImmutable());

            if ($object->getId() === null) {
                $object->setCreatedAt(new \DateTimeImmutable());
            }
        };
    }

    public function setOwner(): callable
    {
        return function (PostSubmitEvent $event) {
            $object = $event->getData();

            if ($object->getId() === null) {
                $object->setUser($this->security->getUser());
            }
        };
    }
}