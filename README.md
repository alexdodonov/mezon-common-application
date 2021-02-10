## Common application class

### Intro

This class provides simple application routine with more complex rendering and error handling. 

### Extended routes processing

In [Application](https://github.com/alexdodonov/mezon/tree/master/Application) class routes may return only strings. But CommonApplication class allows you to return arrays of string which will be placed in the template placeholders.

Simple example:

```PHP
class           ExampleApplication extends CommonApplication
{
	/**
	 * Constructor.
	 */
	function			__construct( $template )
	{
		parent::__construct( $template );
	}

    function            actionSimplePage()
    {
        return [ 
            'title' => 'Route title' , 
            'main' => 'Route main'
        ];
    }
}
```

Here route's handler generates two parts of the page /simple-page/ - 'title' and 'main'. These two part will be inserted into {title} and {main} placeholders respectively.

More complex example:

```PHP
class           ExampleApplication extends CommonApplication
{
	/**
	 * Constructor.
	 */
	function			__construct($template)
	{
		parent::__construct($template);
	}

    function            actionSimplePage()
    {
        return [ 
            'title' => 'Route title' , 
            'main' => new View('Generated main content')
        ];
    }
}
```

Here we pass instance of the class View (or any class derived from View) to the application page compilator. It will call View::render method which must return compiled html content.

### Routes config

You can also keep al routes in configs. You can use json configs:

```JS
[
	{
		"route": "/route1/",
		"callback": "route1",
		"method": "GET"
	},
	{
		"route": "/route2/",
		"callback": "route2",
		"method": ["GET","POST"]
	}
]
```

This data must be stored in the './conf/' dir of your project. Or load configs explicitly as shown below (using method loadRoutesFromConfig).

And we also need these methods in the application class.

```PHP
class           ExampleApplication extends CommonApplication
{
	/**
	 * Constructor.
	 */
	function			__construct($template)
	{
		parent::__construct($template);

		// loading config on custom path
		$this->loadRoutesFromConfig('./my-routes.json');
	}

    function            route1()
    {
        return [ 
            // here result
        ];
    }

    function            route2()
    {
        return [ 
            // here result
        ];
    }
}
```

Note that you can load multiple configs with one call of the method loadRoutesFromConfigs

```PHP
function			__construct($template)
	{
		parent::__construct($template);

		$this->loadRoutesFromConfigs(['./conf/my/routes.json', './conf/my-routes.php']);
	}
```

Or the same:

```PHP
function			__construct($template)
	{
		parent::__construct($template);

		$this->loadRoutesFromDirectory('./conf');
	}
```

