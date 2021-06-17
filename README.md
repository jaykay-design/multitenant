# MultiTenant

MultiTenant CakePHP Plugin - Use this plugin to easily build SaaS enabled web applications.

Forked from https://github.com/pronique/multitenant

## Version notice

This plugin only supports CakePHP >= 4.x

The project is currently in development and is considered experimental at this stage.

## Introduction

The MultiTenant plugin is best to implement when you begin developing your application, but with some work
you should be able to adapt an existing application to use the Plugin.

The plugin currently implements the following multi-tenancy architecures (Strategy).

### Domain Strategy

* Shared Database, Shared Schema
* Single Application Instance
* Subdomain per Tenant


### Tenants

The plugin introduces the concept of Tenants, a tenant represents each customer account.

A tenant can have it's own users, groups, and records.  Data is scoped to the tenant, tenants are represented
in the database as Accounts (or any Model/Table you designate by configuration).

### Contexts

The MultiTenant plugin introduces the concept of Contexts, context is an intregal part of
how the multitenant plugin works.  There are two predefined contexts, 'tenant' and 'global'.

#### 'global' Context

By default, 'global' maps to www.mydomain.com and is where you will implement non-tenant parts of your
application. ie signup/register code.

#### 'tenant' Context

'tenant' context represents this tenant.  When the user is accessing the application at the subdomain, this is 
considered the 'tenant' context.

### Scopes

Each of your application's Models can implement one of five Scope Behaviors that will dictate 
what data is accessible based on the context.  These scopes are implemented as CakePHP Behaviors.

#### GlobalScope

The data provided in a Global Scoped Model can be queried by any tenant but insert/update/delete operations
are not allowed in the 'tenant' Context.

#### TenantScope

The data provided in a Tenant Scoped Model can only queried by the owner (tenant).  Insert operations are 
scoped to the current tenant.  Update and Delete operations enfore ownership, so that Tenant1 cannot 
update/delete Tenant2's records.

#### MixedScope

Mixed Scope Models provide both Global records as well as Tenant scoped records in the same table.  When 
a tenant queries the table (in the 'tenant' context), that tenant's records are returned along with the
global records that exist in the table.

Any records the tenant inserts are scoped to the tenant.  Tenants cannot update/delete global 
records that exist in the table.  And of course tenants cannot select/insert/update/delete other tenant's
records in the table. 

#### SharedScope

Shared Scope Models act as a community data table.  Tenants can query all records in the table, including other
tenant's records.  Insert operations are scoped to the current tenant.  Tenants cannot update/delete other 
tenant's records.

#### NoScope

No Scope Models add scoping to the Model, it is a verbose way to express that a Model is not scoped at all.
If the table has an account_id field, the inserting tenant's id is used to notate who inserted the record.
Since scope is not enfored, any tenant can delete any record.

## Installation

### composer

The recommended installation method for this plugin is by using composer. Just add this to your `composer.json` configuration:

```json
{
	"require" : {
		"jaykaydesign/multitenant": "master-dev"
	}
}
```

### git clone

Alternatively you can just `git clone` the code into your application

```
git clone git://github.com/jaykaydesign/multitenant.git app/Plugin/MultiTenant
```

With this option you will have to update your autoload section in composer.json with
```
    "autoload": {
        "psr-4": {
			...
            "MultiTenant\\": "./plugins/MultiTenant/src"
			...
        }
    },
```
### git submodule

Or add it as a git module, this is recommended over `git clone` since it’s easier to keep up to date with development that way

```
git submodule add git://github.com/jaykaydesign/multitenant.git app/Plugin/MultiTenant
```

With this option you will have to update your autoload section in composer.json with
```
    "autoload": {
        "psr-4": {
			...
            "MultiTenant\\": "./plugins/MultiTenant/src"
			...
        }
    },
```

## Configuration

Add the following to your `config/bootstrap.php`

```php
<?php
Plugin::load('MultiTenant', ['bootstrap' => true, 'routes' => false]);
?>
```

Add the following to the bottom of your application's config\app.php

```php
/**
 * MultiTenant Plugin Configuration
 *
 *
 * ## Options
 *
 * - `strategy` - 'domain' is currently the only implemented strategy
 * - `primaryDomain` - The domain for the main application
 * - `tenantDomainSuffix` - Remove this suffix from the domain to get the tenant name
 * - `model` - The model that represents the tenant, usually 'Accounts'
 * - `scopeBehavior` - Application wide defaults for the Behaviors
 *
 */
	'MultiTenant' => [
		'strategy' => 'domain',
		'primaryDomain' => 'www.example.com',
        'tenantDomainSuffix' => '.example.com',
		'model' => [
		  'className'=>'Tenants',
		  'field' => 'domain', //field of model that holds subdomain/domain tenants
		  'conditions' => ['is_active' => 1] //query conditions to match active accounts
		],
		'scopeBehavior'=>[
			'global_value' => 1, //global records are matched by this value
			'foreign_key_field' => 'account_id' //the foreign key field that associates records to tenant model
		]
	]
```

Note:  don't forget to add the , to the bottom config section when pasting the above configuration.  A syntax error in config\app.php is a silent failure (blank page). 

Update your `src/application.php`
```php
use MultiTenant\Routing\Middleware\MultiTenantMiddleware;

...

    public function bootstrap(): void
    {
		...
        $this->addPlugin('MultiTenant');
		...
	}
```


## Usage

### Accessing the tenant

The current tenant is store dint he application configuration.
```php
//Returns an entity of the current tenant
$tenant = Configure::read('MultiTenant.tenant');
echo $tenant->id;
//output 1

//Or the same thing in a single line;
echo Configure::read('MultiTenant.tenant')->id;
//output 1

//Another Example, you can reference any field in the underlying model
echo Configure::read('MultiTenant.tenant')->name;
//output Acme Corp.
```


### Behavior usage examples

#### TenantScopeBehavior
```php
class SomeTenantTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.TenantScope');
		...
	}
	...
}
```

#### MixedScopeBehavior
```php
class SomeMixedTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.MixedScope');
		...
	}
	...
}
```

#### GlobalScopeBehavior
```php
class SomeCommonTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.GlobalScope');
		...
	}
	...
}
```

#### SharedScopeBehavior
```php
class SomeSharedTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.SharedScope');
		...
	}
	...
}
```

#### NoScopeBehavior
```php
class JustARegularTable extends Table {
	
	public function initialize(array $config) {
		...
		$this->addBehavior('MultiTenant.NoScope');
		...
	}
	...
}
```

# Bugs

If you happen to stumble upon a bug, please feel free to create a pull request with a fix, and a description
of the bug and how it was resolved.

# Features

Pull requests are the best way to propose new features.
