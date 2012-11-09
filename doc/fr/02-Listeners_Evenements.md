# Listeners et Évènements

Une Application Core n'est ni plus ni moins qu'un conteneur qui émet des évènements tout le long du cycle d'une requête HTTP. 
Ces évènements sont ensuite traités par différents *Listeners* qui se chargent d'orchestrer le déroulement (runtime) de l'application.

Le Listener principal est ```Fwk\Core\Listener```. Il est responsable du comportement général de l'application et génère a son tour d'autres évènements. Il est possible de changer ce comportement, totalement ou en partie, si le développeur le désire. 

Les Listeners sont configurés dans le Descripteur de l'application (fwk.xml) de la manière suivante:

**IMPORTANT**: ```Fwk\Core\Listener``` doit être le premier indiqué (si on souhaite l'utiliser). L'ordre des listeners peut éventuellement avoir un impact. 

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<fwk id="MyApp" version="1.0-dev">
    <listener class="Fwk\Core\CoreListener" />
    <listener class="MyApp\runtime\CustomListener" />
    <!-- ... -->
</fwk>
```

Un Listener est une classe dont les fonctions correspondant aux noms des évènements, préfixés par "on", seront exécutés au déclenchement du dit évènement. Pour plus d'informations, se référer à la documentation du package [Events](http://github.com/fwk/Events). 

Par exemple, le Listener suivant affichera le texte "Hello Listener" lors du *boot* de l'application:

``` php
class HelloListener
{
    public function onBoot(\Fwk\Core\CoreEvent $event)
    {
        echo "Hello Listener";
    }
}
```

# Evènements

Les évènements principaux d'un application, lorsque ```Fwk\Core\Listener``` est utilisé, sont les suivants:

* **boot** : Notifié lorsque l'application démarre
* **request** : Notifié lors de la requête utilisateur
* **dispatch** : Notifié lorsque aucune Action n'a été trouvée pour satisfaire la requête
* **init** : Notifié juste avant l'intanciation de la classe Action
* **actionLoaded** : Notifié lorsque la classe Action à été chargée
* **actionSuccess** : Notifié lorsque l'Action à été exécutée
* **result** : Notifié lors du résultat de l'Action
* **response** : Notifié lorsque la réponse à l'Action à été définie
* **finalResponse** : Notifié lorsque la réponse finale à la requête est définie
* **end** : Notifié lors de la fin du traitement de la requête
* **error** : Notifié lorsqu'une erreur survient

Certains de ces évènements peuvent paraîtres répétitifs mais ils nous seront utiles par la suite. Un tel découpage permet d'intervenir à n'importe quel moment du runtime.

# Listeners principaux

Core est packagé avec un certain nombre de listeners qui fournissent diverses possibilités pour le développeur. Voici le détail du fonctionnement de chacun de ces listeners.

## PropertiesListener

Ce listener fourni un moyen simple de préciser des paramètres (de configuration par exemple) à l'application par le biais du fichier fwk.xml.

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<fwk id="MyApp" version="1.0-dev">
    <!-- ... -->
    <listener class="Fwk\Core\Components\PropertiesListener" />
    <!-- ... -->

    <properties>
        <property name="db.dsn">localhost</property>
	<property name="db.username">dbuser</property>
        <property name="db.password">dbpass</property>
    </properties>
</fwk>
```
**NOTE**: Il peut être judicieux de créer des pseudo-namespaces lors de la création de paramètres, par exemple ```myapp.propName```. Ceci afin d'éviter qu'ils soient écrasés/modifiés par une autre application. 

Ces paramètres sont ensuite accessibles via la methode ```get($propName, $default = null)``` de l'objet ```Fwk\Core\Application```. 

**NOTE**: Les paramètres peuvent être repris entre-eux en utilisant des inflecteurs (:paramName). Par exemple:

``` xml
<property name="templates.basedir">:packageDir/templates</property>
<property name="templates.themedir">:templates.basedir/mytheme</property>
```

(Le paramètre ```packageDir``` correspond au chemin complet vers le dossier de l'application. Il est tout le temps disponible)

## BootstrapListener

Ce listener permet comme son nom l'indique de "bootstrapper" son application. 
Le seul Bootstrapper qui est fourni avec Core est ```Fwk\Core\Components\Bootstrap\ClassBootstrapper```. Il exécutera chaque méthode d'une classe donnée commençant par "register". Le seul paramètre de ces méthodes est l'instance ```Fwk\Core\Application``` de notre application.

C'est généralement l'endroit idéal pour instancier nos Services (DI Container), Connexions à la base de donnée etc.

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<fwk id="MyApp" version="1.0-dev">
    <!-- ... -->
    <listener class="Fwk\Core\Components\Bootstrap\BootstrapListener" />
    <!-- ... -->

    <bootstrap type="Fwk\Core\Components\Bootstrap\ClassBootstrapper">
        MyApp\Bootstrap
    </bootstrap>
</fwk>
```

Voici un exemple de Bootstrapper:

``` php
namespace MyApp;

class Bootstrap
{
    public function registerDatabase(\Fwk\Core\Application $app)
    {
	$app->set('db', new PDO($app->get("db.dsn"))); // utilisation des paramètres définis dans fwk.xml
    }
}
```

**NOTE**: Un Bootstrapper est une interface: ```Fwk\Core\Components\Bootstrap\Bootstrapper```. Ceci permet au développeur de pouvoir créer le type de bootstrapper qu'il souhaite (stream http, gestionnaire de config, ...)

## UrlRewriterListener

Ce listener permet de réécrire les URLs pointant vers les Actions de notre application. Par défaut, les Actions s'attaquent via le schéma d'URL suivant:

```
http://myapp.localhost/index.php/<Action>.action
http://myapp.localhost/index.php/<Action>.action?name=John
```

En utilisant ce listener, nous pourront ainsi revenir à des schémas plus classiques:

```
http://myapp.localhost/index.php/hello
http://myapp.localhost/index.php/hello/John
```

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<fwk id="MyApp" version="1.0-dev">
    <!-- ... -->
    <listener class="Fwk\Core\Components\UrlRewriter\UrlRewriterListener" />
    <!-- ... -->

    <url-rewrite>
        <url route="/hello/:name$" action="Hello">
            <param name="name" required="[true|false]" regex="[a-zA-Z0-9]" />
        </url>
    </url-rewrite>
</fwk>
```

**NOTE**: L'ordre de définition des routes peut avoir un impact sur le fonctionnement de l'application.

Le paramètres *route* est interprété comme une expression régulière. Les paramètres cependant fonctionnent comme des inflecteurs classiques. Chaque paramètre doit être décrit dans une balise ```<param />``` reprenant son nom, s'il est requis ou non (```required```) et l'expression régulière (```regex```) qui le valide ou l'invalide. Généralement, les noms des paramètres correspondent aux noms de propriétés de l'Action que nous visons.

**NOTE**: Afin d'éviter d'avoir à reprendre *index.php* dans le préfixe des URLs il vous faudra passer par le moteur de réécriture classique de votre serveur web. Pour [Apache](http://httpd.apache.org/) et *mod_rewrite* bien souvent, un fichier ```.htaccess``` à la racine de votre webroot résoud ce problème. Voici un exemple:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -s [OR]
RewriteCond %{REQUEST_FILENAME} -l [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^.*$ - [NC,L]
#RewriteRule \.(js|ico|txt|gif|jpg|png|css)$ - [NC,L]
RewriteRule ^.*$ index.php [NC,L]
```

## ResultTypeListener

Un des listeners les plus puissants. Ce dernier permet de traiter le résultat d'une Action et de déterminer quelle en est la réponse adéquate. C'est par exemple un bon moyen pour utiliser un moteur de templates.

### Déclaration des Types

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<fwk id="MyApp" version="1.0-dev">
    <!-- ... -->
    <listener class="Fwk\Core\Components\ResultType\ResultTypeListener" />
    <!-- ... -->

    <result-types>
        <result-type
            name="template"
            class="Fwk\Core\Components\ResultType\Types\PhpFile">
            <param name="templatesDir">:packageDir/templates</param>
        </result-type>
    </result-types>
</fwk>
```

Chaque balise ```<result-type />``` peut être configurée via des balises ```<param />``` fonctionnant exactement (inflecteurs compris) comme les properties de ```PropertiesListener```. Dans cet exemple, nous utilisons le type ```Fwk\Core\Components\ResultType\Types\PhpFile``` qui reprends PHP comme language de templates. 

### Utilisation

Une fois les Types configurés, il ne nous reste plus qu'à les implémenter au sein de nos Actions, toujours dans fwk.xml.

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<fwk id="MyApp" version="1.0-dev">
    <!-- ... -->
    
    <actions>
        <action name="Hello" class="MyApp\actions\Hello" method="show">
            <result name="success" type="template">
                 <param name="file">hello.phtml</param>
            </result>
            <result name="error" type="template">
                 <param name="file">error.phtml</param>
            </result>
        </action>
    </actions>
</fwk>
```

Ainsi, lorsque l'action retournera "success", le template ```hello.phtml``` sera chargé en reprenant tous les attributs de notre Action *Hello*. Les paramètres utilisés dans les ```<result />``` dépendent entièrement du Type utilisé. En l'occurence, notre Type ```PhpFile``` requiert un paramètre *file* représentant le fichier-template a utiliser.

