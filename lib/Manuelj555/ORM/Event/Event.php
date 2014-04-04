<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM\Event;

use Symfony\Component\EventDispatcher\Event as BaseEvent;

/**
 * Description of Event
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class Event extends BaseEvent
{

    protected $stopped = false;

    public function isStopped()
    {
        return $this->stopped;
    }

    public function stopProccess()
    {
        $this->stopped = true;
        $this->stopPropagation();
    }

}
