iCoordinator REST Services
===========================

[![Build Status](https://magnum.travis-ci.com/designtech/icoordinator-new-api.svg?token=gT9E9x9w53RsB9LBz8Ke)](https://magnum.travis-ci.com/designtech/icoordinator-new-api)

System Requirements
-------------------
* Apache / Nginx
* PHP 5.5.3+
* MySQL 5.5.6+ / MariaDB
* NodeJS v0.10.21+


Getting Started
---------------
Get latest sources from GitHub:
```
git clone git@github.com:designtech/icoordinator-new-api.git
```
Change working directory:
```
cd icoordinator-new-api
```
Install all required NodeJS modules:
```
npm install
```
Install Grunt globally on the server:
```
sudo npm install -g grunt-cli
```
Run Grunt task to initialize project configuration and all dependencies:
```
grunt init
```
Setup database configuration:
```
nano application/config/db.local.php
```
Update dependencies and database structure:
```
grunt update
```
Create your first portal:
```
grunt portal:create:<portal_subdomain_part>
```
For example:
```
grunt portal:create:portal123
```

### Install git hooks:

pre-commit:
```
#!/bin/sh
grunt precommit
```

Application Services
--------------------

### Obtaining app object inside controller
```
$app = $this->getApp();
```

### Getting application folder full path
```
$applicationPath = $app->config('applicationPath');
```

### Obtaining main entity manager

Using $app object:
```
/** @var \Doctrine\ORM\EntityManager $em */
$em = $app->mainEntityManager;
```
Inside controller:
```
$em = $this->getEntityManager(EntityManagerFactory::ENTITY_MANAGER_TYPE_MAIN);
```

### Obtaining portal entity manager

Using $app object:
```
/** @var \Doctrine\ORM\EntityManager $em */
$em = $app->portalEntityManager;
```
Inside controller:
```
$em = $this->getEntityManager();
```

### Obtaining OAuth server
```
/** @var \OAuth2\Server $oauth */
$oauth = $app->OAuthServer;
```

### Obtaining authentication service

Using $app object:
```
/** @var \Zend\Authentication\AuthenticationService */
$auth = $app->auth;
```
Inside controller:
```
$auth = $this->getAuth();
```

### Obtaining ACL

Using $app object:
```
/** @var \iCoordinator\Permissions\Acl $acl */
$acl = $app->acl;
```
Inside controller:
```
$acl = $this->getAcl();
```

Important Grunt Tasks
---------------------
### Create new portal
```
grunt portal:create:<portal_subdomain_part>
```
For example:
```
grunt portal:create:portal123
```
### Destroy existing portal
```
grunt portal:destroy:<portal_subdomain_part>
```
For example:
```
grunt portal:destroy:portal123
```
### Update databases of ALL portals
```
grunt db:migrate-portals-db
```

Unit Testing
------------
Run all unit tests:
```
grunt test
```
Run unit tests of specific class:
```
grunt test -filter AclTest
```
Run unit tests of specific method
```
grunt test -filter testUnauthorizedRoleAcl
```


Documentation Links
-------------------
1. Slim Framework - http://docs.slimframework.com/
2. Doctrine ORM - http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/
3. PHPUnit - http://phpunit.de/manual/current/en/index.html
4. Grunt - http://gruntjs.com/getting-started


Heroku Deployment
--------------

###Dev server

Deployments to dev server are automatic. New feature just needs to be merged to `dev` branch. If all Travis CI tests are successfult - Heroku will automatically deploy new version

###Staging server

1) New release needs to be created from the stable snapshot of `dev` branch. This can be done manually or using [gitflow](https://github.com/nvie/gitflow):

```
git flow release start <release> [<base>]
```

Where `<release>` is release number and `<base>` is branch you create release from. Should be set to `dev` by default. Release version has [symantic versioning](http://semver.org) format.

2) Once new release branch created you should immediately "bump" version of your codebase using following code:

Bump patch number:
```
grunt bump
```

Bump minor version:
```
grunt bump:minor
```

Bump major version:
```
grunt bump:major
```

More info you can find in [grunt-bump documentation](https://github.com/vojtajina/grunt-bump).

3) After that release can be finished (if no additional changes required). This will automatically merge new release into `master` branch and create proper tags.
```
git flow release finish
```

4) New changes and tags need to be pushed to GitHub:
```
git push origin master
git push origin --tags
```

5) After that Travis CI will start bulding and unit testing for `master` branch.

6) Once Travis CI build is completed new version needs to be installed into `ic-airborne-api` -> `ic-airborne-api-staging` app on Heroku. This can be done inside "Deploy" -> "GitHub" -> "Manual Deploy" -> Select "master" branch -> "Deploy".

7) Now new version is deployed to staging server.
