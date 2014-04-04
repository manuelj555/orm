<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM\Listener;

use Manuelj555\ORM\Event\Event;
use Manuelj555\ORM\Event\InsertEvent;
use Manuelj555\ORM\Event\UpdateEvent;
use Manuelj555\ORM\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Description of ObjectCallbacksListener
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class ObjectCallbacksListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_INSERT => 'preInsert',
            Events::POST_INSERT => 'postInsert',
            Events::PRE_UPDATE => 'preUpdate',
            Events::POST_UPDATE => 'postUpdate',
            Events::PRE_DELETE => 'preDelete',
            Events::POST_DELETE => 'postDelete',
        );
    }

    protected function call($event, $method)
    {
        if (method_exists($event->getModel(), $method)) {
            if(false === call_user_func(array($event->getModel(), $method))){
                $event->stopProccess();
            }
        }
    }

    public function preInsert(InsertEvent $event)
    {
        $this->call($event, __FUNCTION__);
    }

    public function postInsert(InsertEvent $event)
    {
        $this->call($event, __FUNCTION__);
    }

    public function preUpdate(UpdateEvent $event)
    {
        $this->call($event, __FUNCTION__);
    }

    public function postUpdate(UpdateEvent $event)
    {
        $this->call($event, __FUNCTION__);
    }

    public function preDelete(UpdateEvent $event)
    {
        $this->call($event, __FUNCTION__);
    }

    public function postDelete(UpdateEvent $event)
    {
        $this->call($event, __FUNCTION__);
    }

}
