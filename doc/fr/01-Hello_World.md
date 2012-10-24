# Hello World !

Comme tout framework d'applications, l'exemple du Hello World s'impose :) 
Nous allons donc développer de A à Z une application répondant "Hello World" sur
la page d'accueil. 

## Préparation du système de fichiers

Dans un premier temps, nous allons créer le répertoire principal de l'application.
Pour cet exemple, le "package" de l'application sera *HelloWorld* et le dossier
(webroot) de l'application sera *public*. Ces deux dossiers seront stockés dans 
le répertoire *app*.

```
[neiluj @fwk:~]$ mkdir -p app && mkdir -p app/HelloWorld app/public
[neiluj @fwk:~]$ cd app
```

Nous allons ensuite télécharger Composer et installer les dépendances requises pour
notre application.

```
[neiluj @fwk:~/app]$ curl -s http://getcomposer.org/installer | php
[neiluj @fwk:~/app]$ php composer.phar init
```

Cette dernière commande crééra un fichier "composer.json" qu'il faudra éditer 
pour ajouter les dépendances (requires) et spécifier le dossier de l'application
pour le chargement des classes (autoload).

``` javascript

{
    "name": "neiluj/fwk-helloworld",
    "minimum-stability": "dev",
    "authors": [
        {
            "name": "Julien",
            "email": "julien@nitronet.org"
        }
    ],
    "require": {
        "fwk/core": "dev-master"
    },
    "autoload": {
        "psr-0": {
            "HelloWorld": ""
        }
    }
}
```

Ensuite, il suffit de finaliser l'installation:

```
[neiluj @fwk:~/app]$ php composer.phar install
```

Si tout s'est bien déroulé, un dossier *vendor* est venu s'ajouter dans notre
répertoire. Il contient les dépendances et l'autoloader de notre application.


## index.php

Les requêtes provenant du web arriveront vers un fichier *index.php* placé
dans notre répertoire racine *public*

``` php
<?php
require_once __DIR__ .'/../vendor/autoload.php';

use Fwk\Core\Application,
    Fwk\Core\Descriptor;

$desc = new Descriptor(__DIR__ .'/../HelloWorld/fwk.xml');
$app = Application::autorun($desc);
```

## fwk.xml

Le fichier *fwk.xml* localisé à la racine du répertoire *HelloWorld* permet de 
configurer notre application. C'est la seule forte dépendance du framework. 

C'est ce que nous appelons la "description" de notre application. 

Voici son contenu:

``` xml

<?xml version="1.0" encoding="UTF-8"?>
<fwk id="HelloWorld" version="1.0-dev">
    <listener class="Fwk\Core\CoreListener" />
    <actions>
        <action name="Hello" class="HelloWorld\actions\Hello" method="show" />
    </actions>
</fwk>
```  

Notre application étant simplissime, ce fichier est très simple à comprendre:

* On souhaite utiliser CoreListener, qui est le chef d'orchestre général du framework.
* On déclare une seule action (nom, classe et méthode), celle qui dit "bonjour"

## Hello.action

Nous devons maintenant créer le dossier *actions* ou nous mettrons nos "Controllers",
ici "Hello".

```
[neiluj @fwk:~/app]$ mkdir HelloWorld/actions && cd HelloWorld/actions
[neiluj @fwk:~/app/HelloWorld/actions]$ touch Hello.php
```

Le contenu de notre classe Hello est lui aussi très simple:

``` php

<?php
namespace HelloWorld\actions;

class Hello
{
    public function show()
    {
        return "Hello World";
    }
}
```

## Premier test

Notre action "Hello" est maintenant disponible à l'URL suivante 
(en fonction de la configuration de votre environnement):

```
http://helloworld.localhost/index.php/Hello.action
```

Ceci devrait afficher le texte "Hello World" !

## URL-Rewritting

Core dispose d'un listener permettant de réécrire les URLs de manière simple et 
efficaces. Pour des raisons de brièveté, nous n'allons pas en détailler son usage
en détail (@todo documentation). Tout se passe dans le fichier *fwk.xml* ...

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<fwk id="HelloWorld" version="1.0-dev">
    <listener class="Fwk\Core\CoreListener" />
    <listener class="Fwk\Core\Components\UrlRewriter\UrlRewriterListener" />
    <actions>
        <!-- ... -->
    </actions>

    <url-rewrite>
        <url route="/" action="Hello" />
    </url-rewrite>
</fwk>
```

à présent, notre application répond à l'adresse suivante

```
http://helloworld.localhost/
```

## Hello-who?

Maintenant que notre application est en place, il est temps de l'améliorer un
petit peu. Nous allons préciser à l'aide d'un paramètre de requête le nom de
la personne qui souhaite être saluée.

On commence donc par éditer notre action *Hello* 

``` php
<?php
namespace HelloWorld\actions;

class Hello
{ 
    protected $name = "World";

    public function show()
    {
        return "Hello ". htmlspecialchars($this->name);
    }

    public function getName() 
    {
        return $this->name;
    }

    public function setName($name) 
    {
        $this->name = $name;
    }
}
```

Notre paramètre ```$name``` est accessible depuis la requête HTTP, par exemple:

```
http://helloworld.localhost/index.php/Hello.action?name=John
```

Ajoutons maintenant une règle de réécriture dans notre fichier *fwk.xml* 

``` xml
<!-- http://helloworld.localhost/index.php/hello/john -->
<url route="/hello/:name" action="Hello">
    <param name="name" required="true" />
</url>
```

