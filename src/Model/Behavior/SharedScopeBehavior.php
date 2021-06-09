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
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Table;
use MultiTenant\Core\MTApp;
use MultiTenant\Error\DataScopeViolationException;

class SharedScopeBehavior extends Behavior
{

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
        'global_value' => 0,
        'foreign_key_field' => 'account_id',
    ];

/**
 * Constructor
 *
 *
 * @param \Cake\ORM\Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
    public function __construct(Table $table, array $config = [])
    {
        $config = array_merge(MTApp::getConfig('scopeBehavior'), $config);
        parent::__construct($table, $config);
    }

/**
 * beforeFind callback
 *
 * inject where condition if context is 'tenant'
 *
 * @param \Cake\Event\Event $event The afterSave event that was fired.
 * @param \Cake\ORM\Query $query The query.
 * @return void
 */
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, bool $primary)
    {
        if (MTApp::getContext() == 'tenant') {
            $query->where([$this->_table->getAlias() . '.' . $this->getConfig('foreign_key_field') => MTApp::tenant()->id]);
        }
    }

/**
 * beforeSave callback
 *
 * Prevent saving if the context is not global
 *
 * @param \Cake\Event\Event $event The beforeSave event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        if (MTApp::getContext() !== 'tenant') {
            return;
        }

        $field = $this->getConfig('foreign_key_field');
        if ($entity->isNew()) {
            //blind overwrite, preventing user from providing explicit value
            $entity->set($field, MTApp::tenant()->id);

        } else {
            //paranoid check of ownership
            if ($entity->get($field) !== MTApp::tenant()->id) { //current tenant is NOT owner
                throw new DataScopeViolationException('Tenant->id:' . MTApp::tenant()->id . ' does not own ' . $this->_table->getAlias() . '->id:' . $entity->id);
            }
        }
    }

/**
 * beforeDelete callback
 *
 * Prevent delete if the context is not global
 *
 * @param \Cake\Event\Event $event The beforeDelete event that was fired.
 * @param \Cake\ORM\Entity $entity The entity that was saved.
 * @return void
 */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        if (MTApp::getContext() !== 'tenant') {
            return;
        }

        $field = $this->getConfig('foreign_key_field');

        //paranoid check of ownership
        if ($entity->get($field) !== MTApp::tenant()->id) {
            //current tenant is NOT owner
            throw new DataScopeViolationException('Tenant->id:' . MTApp::tenant()->id . ' does not own ' . $this->_table->getAlias() . '->id:' . $entity->id);
        }
    }
}
