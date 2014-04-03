<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM;

/**
 * Description of Events
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
final class Events
{

    const CONNECT = 'orm.connect';
    const QUERY = 'orm.query';
    const PRE_INSERT = 'orm.pre_insert';
    const POST_INSERT = 'orm.post_insert';
    const PRE_UPDATE = 'orm.pre_update';
    const POST_UPDATE = 'orm.post_update';
    const PRE_DELETE = 'orm.pre_delete';
    const POST_DELETE = 'orm.post_delete';

}
