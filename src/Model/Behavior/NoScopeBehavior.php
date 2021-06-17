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

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;

class NoScopeBehavior extends Behavior
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
        $this->__tenant = Configure::read('MultiTenant.tenant');
        $config = array_merge(Configure::read('MultiTenant.scopeBehavior'), $config);
        parent::__construct($table, $config);
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
    public function beforeSave(Event $event, Entity $entity, $options)
    {
        if ($this->__tenant->context !== 'tenant') {
            return;
        }

        $field = $this->getConfig('foreign_key_field');
        if ($entity->isNew() && $entity->has($field) && $entity->get($field) === null) {
            $entity->set($field, $this->__tenant->id);
        }
    }
}
