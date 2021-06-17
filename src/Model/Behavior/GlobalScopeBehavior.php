<?php
/**
 * MultiTenant Plugin
 * Copyright (c) PRONIQUE Software (http://pronique.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) PRONIQUE Software (http://pronique.com)
 * @link          http://github.com/pronique/multitenant MultiTenant Plugin Project
 * @since         0.5.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace MultiTenant\Model\Behavior;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use MultiTenant\Error\DataScopeViolationException;

class GlobalScopeBehavior extends Behavior
{

    private $__tenant;

    /**
     * Default config
     *
     * These are merged with user-provided config when the behavior is used.
     *
     *
     * @var array
     */
    protected $_defaultConfig = [
        'implementedFinders' => [],
        'implementedMethods' => [],
    ];

/**
 * Constructor
 *
 * If events are specified - do *not* merge them with existing events,
 * overwrite the events to listen on
 *
 * @param \Cake\ORM\Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
    public function __construct(Table $table, array $config = [])
    {
        $this->__tenant = Configure::read('MultiTenant.tenant');
        $config = array_merge(Configure::read('MultiTenant.scopeBehavior'), $config);
        parent::__construct($table, $config);
    }

    /**
     * beforeFind callback
     *
     * inject where condition if context is 'tenant'
     *
     * @param \Cake\Event\Event $event The beforeFind event that was fired.
     * @param \Cake\ORM\Query $query The query.
     * @return void
     */
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, bool $primary)
    {
        if ($this->__tenant->context === 'tenant') {
            throw new DataScopeViolationException('Tenant cannot query global records');
        }
    }

/**
 * beforeSave callback
 *
 * Prevent saving if the context is tenant
 *
 * @param \Cake\Event\Event $event The beforeSave event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        //Prevent saving records in the implementing table if this is the tenant context
        if ($this->__tenant->context === 'tenant') {
            throw new DataScopeViolationException('Tenant cannot save global records');
        }
    }

/**
 * beforeDelete callback
 *
 * Prevent delete in the tenant context
 *
 * @param \Cake\Event\Event $event The beforeDelete event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        if ($this->__tenant->context === 'tenant') {
            throw new DataScopeViolationException('Tenant cannot delete global records');
        }
    }
}
