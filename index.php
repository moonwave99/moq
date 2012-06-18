<?php

/**
*	Part of moq - mock RESTful service.
*	@author Diego Caponera <diego.caponera@gmail.com>
*	@link https://github.com/moonwave99/moq
*	@copyright Copyright 2012 Diego Caponera
*	@license http://www.opensource.org/licenses/mit-license.php MIT License
*/

require __DIR__ ."/src/moq.php";

$moq = new Moq(
	'routes.yml'				// replace this with desired routes file
);

$moq -> route();