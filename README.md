# PHRouter - PHP Routing Library

PHRouter is a simple yet powerful PHP routing library that helps you manage and handle HTTP requests in your web application. It allows you to define routes, apply middleware, and execute custom actions based on the requested URL and HTTP method.

## Features

- Define and handle routes for various HTTP methods (GET, POST, PUT, DELETE, etc.).
- Middleware support for common tasks like authentication, logging, and input validation.
- Group routes together and apply middleware to route groups.
- Route parameters with constraints, making it easy to validate and process dynamic parts of URLs.
- Clean and flexible API for building RESTful APIs or web applications.

## Installation

1. Clone the repository:

   ```shell
   git clone https://github.com/koushik/phrouter.git
   ```

2. Include the `Router.php` and `Response.php` files in your PHP project.

3. Create an instance of the `Router` class and define your routes.

4. Start the router to handle incoming requests.

## Usage

Here's a basic example of how to use PHRouter:

```php
// Include the Router and Response classes
require_once 'Router.php';
require_once 'Response.php';

use PHRouter\Router;
use PHRouter\Response;

// Create a Response object
$response = new Response();

// Create a Router instance
$router = new Router($response);

// Define routes here...

// Start the router
$router->start();
```

## Contributing

If you'd like to contribute to this project, please follow these guidelines:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Make your changes and commit them.
4. Push your changes to your fork.
5. Submit a pull request to the main repository.


## License

This project is licensed under `The Unlicense` License - see the [LICENSE](https://en.wikipedia.org/wiki/Unlicense) file for details.


## Acknowledgments
- PHRouter is inspired by various PHP routing libraries and frameworks.
- Special thanks to the PHP community for their contributions and support.
