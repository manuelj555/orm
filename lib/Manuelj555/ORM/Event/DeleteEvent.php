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

/**
 * Description of DeleteEvent
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class DeleteEvent extends Event
{

    protected $model;

    function __construct($model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

}
