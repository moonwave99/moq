#moq - the one-touch mock RESTful webserver.

You need a webserver to test your client/mobile/whatever app right now.
You want it to understand HTTP methods and to return proper HTTP responses.
You want it to be setup in a minute, you want it bad.

Here it is!

You just need a basic **Apache/PHP** environment to run it - even on a remote server.

See it in action [here](http://moonwave99.webfactional.com/moq/), or performing HTTP requests such as:

	GET		http://moonwave99.webfactional.com/moq/users
	POST	http://moonwave99.webfactional.com/moq/users/17

##Installation and Configuration

Just clone this repository into desired webserver path:

	$ git clone https://github.com/moonwave99/moq

or unzip in same location if you prefer. Then look at ```index.php```:

	$moq = new Moq(
		'http://localhost/moq/',	// replace this with desired path
		'routes.yml'				// replace this with desired routes file
	);

and just change those two params as desired.

##Usage

Point your browser / HTTP client at the path you entered before:

	GET http://localhost/moq

You should get this response:

	200 OK moq server working.

Then look at ```routes.yml```, you'll see a list of demo URIs you may edit on purpose:

	-   url:        '/users'
	    method:     GET
	    responses:
	        200:    [{ id: 1, firstName: 'foo', lastName: 'bar', username : 'foobar'}]
	        403:    Access denied baby.

	-   url:        '/users'
	    method:     POST
	    responses:
	        201:    null
	        400:    This is a bad request baby.

	-   url:        '/users/:id'
	    method:     GET
	    responses:
	        200:    { id: :id, firstName: 'foo', lastName: 'bar', username : 'foobar'}

	-   url:        '/users/:id'
	    method:     PUT
	    responses:
	        200:    { id: :id, firstName: 'bar', lastName: 'foo', username : 'foobar'}

		...

each one composed of:

* a **pattern**, which may contain *colon-placeholders* [ex. ```:id```];
* a HTTP **method** [```GET```/```POST```/```PUT```/```DELETE```];
* a list of HTTP **responses** based on HTTP **statuses**.

So if you ask the server for:

	GET /users

You should get:

	200 OK [{ id: 1, firstName: 'foo', lastName: 'bar', username : 'foobar'}]

Placeholders in routes may be used in response body too. Asking for:

	GET /users/17

gives you:

	200 OK { id: 17, firstName: 'foo', lastName: 'bar', username : 'foobar'}

---

Usually you want to test the server for different behaviors, and being this a mock server you can't define business logic in order to replicate them. But you may ask **moq** for a specific status:

	GET /users?_status=403

and you'll get associated response:

	403 Forbidden "Access denied baby."

##Copyright and license

Copyright (c) 2012 MWLabs

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

##Acknowledgements

Many thanks to brilliant [spyc](http://code.google.com/p/spyc/) YAML parser library.

##Author

[www.diegocaponera.com](http://www.diegocaponera.com/) - Just another coder.