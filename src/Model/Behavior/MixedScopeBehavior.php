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

class MixedScopeBehavior extends Behavior
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
 * If events are specified - do *not* merge them with existing events,
 * overwrite the events to listen on
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
 * @param \Cake\Event\Event $event The beforeFind event that was fired.
 * @param \Cake\ORM\Query $query The query.
 * @return void
 */
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, bool $primary)
    {
        if (MTApp::getContext() !== 'tenant') {
            return;
        }

        $query->where(
            [
                $this->_table->getAlias() . '.' . $this->getConfig('foreign_key_field') . ' IN' => [
                    $this->getConfig('global_value'),
                    MTApp::tenant()->id,
                ],
            ]
        );
    }

/**
 * beforeSave callback
 *
 * Allow insert of tenant records if in tenant context
 * Allow insert of tenant records if in tenant context
 * Prevent update of records that are global
 * Prevent update if the record belongs to another tenant
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

        //insert operation
        if ($entity->isNew()) {
            //blind overwrite, preventing user from providing explicit value
            $entity->set($field, MTApp::tenant()->id);

        } else {
            //prevent tenant from updating global records if he is not the owner of the global tenant
            if ($entity->get($field) === $this->getConfig('global_value') &&
                MTapp::tenant()->id !== $this->getConfig('global_value')) {
                throw new DataScopeViolationException('Tenant cannot update global records');
            }

            //paranoid check of ownership
            if ($entity->get($field) !== MTApp::tenant()->id) { //current tenant is NOT owner
                throw new DataScopeViolationException('Tenant->id:' . MTApp::tenant()->id . ' does not own ' . $this->_table->getAlias() . '->id:' . $entity->id);
            }
        }
    }

/**
 * beforeDelete callback
 *
 * Prevent delete if the record is global
 * Prevent delete if the record belongs to another tenant
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

        //tenant cannot delete global records if he is not the onwer of the global tenant
        if ($entity->get($field) === $this->getConfig('global_value') &&
            MTapp::tenant()->id !== $this->getConfig('global_value')) {
            throw new DataScopeViolationException('Tenant cannot delete global records');
        }

        //paranoid check of ownership
        if ($entity->get($field) !== MTApp::tenant()->id) {
            //current tenant is NOT owner
            throw new DataScopeViolationException('Tenant->id:' . MTApp::tenant()->id . ' does not own ' . $this->_table->getAlias() . '->id:' . $entity->id);
        }

    }

}
