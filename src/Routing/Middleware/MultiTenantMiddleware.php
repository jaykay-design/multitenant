<?php
declare (strict_types = 1);

namespace MultiTenant\Routing\Middleware;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\ORM\TableRegistry;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handles detecting current tenant.
 *
 * The current tenant is stored in Configure: 'MultiTenant.tenant'
 */
class MultiTenantMiddleware implements MiddlewareInterface
{
    use InstanceConfigTrait;

    protected $_defaultConfig = [
        'cacheProfile' => 'default',
    ];

    /**
     *
     * Constructor.
     *
     * @param array $options The options to use
     */
    public function __construct(array $options = [])
    {
        $this->setConfig($options);
    }

    /**
     * Detect and store current tenant.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $host = $request->getUri()->getHost();

        $tenant = $this->__findTenant($host);

        if ($tenant === null) {
            throw new Exception('Missing tenant');
        }

        Configure::write('MultiTenant.tenant', $tenant);

        return $handler->handle($request);
    }

    private function __findTenant(string $host)
    {
        //get tenant qualifier
        $qualifier = self::__getTenantQualifier($host);

        //Read entity from cache if it exists
        $tenant = Cache::read('MultiTenant.tenants.' . $qualifier, $this->getConfig('cacheProfile'));
        if ($tenant !== null) {
            return $tenant;
        }

        //load model
        $modelConf = Configure::read('MultiTenant.model');
        $tbl = TableRegistry::getTableLocator()->get($modelConf['className']);
        $conditions = array_merge([$modelConf['field'] => $qualifier], $modelConf['conditions']);

        //Query model and store in cache
        $tenant = $tbl
            ->find('all', ['skipTenantCheck' => true])
            ->where($conditions)
            ->first();

        if ($tenant != null) {
            Cache::write('MultiTenant.tenants.' . $qualifier, $tenant, $this->getConfig('cacheProfile'));
        }

        return $tenant;
    }

    private static function __getTenantQualifier(string $host)
    {
        if (Configure::read('MultiTenant.strategy') === 'domain') {

            if (Configure::read('MultiTenant.primaryDomain') === $host) {
                return '';
            }

            return str_replace(Configure::read('MultiTenant.tenantDomainSuffix'), '', $host);
        }

        throw new Exception('Missing tenant detection stategy');
    }

}
