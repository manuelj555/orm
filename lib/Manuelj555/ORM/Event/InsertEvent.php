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
 * Description of InsertEvent
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class InsertEvent extends Event
{

    protected $model;

    /**
     *
     * @var array
     */
    protected $data;

    public function __construct($model, array $data)
    {
        $this->model = $model;
        $this->data = $data;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

}
