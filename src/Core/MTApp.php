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
namespace MultiTenant\Core;

use Cake\Core\StaticConfigTrait;
use Cake\ORM\TableRegistry;
use Exception;

//TODO Implement Singleton/Caching to eliminate sql query on every call
class MTApp
{
    use StaticConfigTrait;

    protected static $_cachedAccounts = [];

    /**
     * find the current context based on domain/subdomain
     *
     * @return String 'global', 'tenant', 'custom'
     *
     */
    public static function getContext()
    {
        //get tenant qualifier
        $qualifier = self::_getTenantQualifier();

        return $qualifier == '' ? 'global' : 'tenant';
    }

    /**
     *
     *
     */
    public static function isPrimary()
    {
        //get tenant qualifier
        $qualifier = self::_getTenantQualifier();

        return $qualifier == '';
    }
/**
 *
 * Can be used throughout Application to resolve current tenant
 * Returns tenant entity
 *
 * @returns Cake\ORM\Entity
 */
    public static function tenant()
    {
        //if tentant/_findTenant is called at the primary domain the plugin is being used wrong;
        if (self::isPrimary()) {
            throw new Exception('MTApp::tenant() cannot be called from primaryDomain context');
        }

        $tenant = static::_findTenant();

        //Check for inactive/nonexistant domain
        if ($tenant === null) {
            self::redirectInactive();
        }

        return $tenant;
    }

    protected static function _findTenant()
    {
        //if tentant/_findTenant is called at the primary domain the plugin is being used wrong;
        if (self::isPrimary()) {
            throw new Exception('MTApp::tenant() cannot be called from primaryDomain context');
        }

        //get tenant qualifier
        $qualifier = self::_getTenantQualifier();

        //Read entity from cache if it exists
        if (array_key_exists($qualifier, self::$_cachedAccounts)) {
            return self::$_cachedAccounts[$qualifier];
        }

        //load model
        $modelConf = self::getConfig('model');
        $tbl = TableRegistry::getTableLocator()->get($modelConf['className']);
        $conditions = array_merge([$modelConf['field'] => $qualifier], $modelConf['conditions']);

        //Query model and store in cache
        $tenant = $tbl
            ->find('all', ['skipTenantCheck' => true])
            ->where($conditions)
            ->first();

        if ($tenant === null) {
            return false;
        }

        self::$_cachedAccounts[$qualifier] = $tenant;

        return $tenant;
    }

    public static function redirectInactive()
    {
        $uri = self::getConfig('redirectInactive');

        if (strpos($uri, 'http') !== false) {
            $full_uri = $uri;
        } else {
            $full_uri = env('REQUEST_SCHEME') . '://' . self::getConfig('primaryDomain') . $uri;
        }

        header('Location: ' . $full_uri);
        exit;
    }

    protected static function _getTenantQualifier()
    {
        if (self::getConfig('strategy') == 'domain') {
            $host = env('SERVER_NAME');

            if (self::getConfig('primaryDomain') === $host) {
                return '';
            }

            return str_replace(self::getConfig('tenantDomainSuffix'), '', $host);
        }

        throw new Exception('Missing Tenant detection stategy');
    }
}
