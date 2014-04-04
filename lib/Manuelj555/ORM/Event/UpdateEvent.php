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
 * Description of UpdateEvent
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class UpdateEvent extends InsertEvent
{

    protected $originalValues;

    public function __construct($model, array $values = array(), array $originalValues = array())
    {
        $this->originalValues = $originalValues;
        parent::__construct($model, $values);
    }

    public function getOriginalValues()
    {
        return $this->originalValues;
    }

    public function getChangedValues()
    {
        return $this->getData();
    }

    public function isChanged($name)
    {
        return array_key_exists($name, $this->getData());
    }

    public function restore($name)
    {
        if ($this->isChanged($name)) {
            unset($this->data[$name]);
        }
    }

}
