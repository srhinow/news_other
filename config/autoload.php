<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Models
	'Contao\NewsOtherModel'      => 'system/modules/news_other/models/NewsOtherModel.php',

	// Modules
	'Contao\ModuleNewsOtherList' => 'system/modules/news_other/modules/ModuleNewsOtherList.php',

	// Class
	'Contao\NewsOtherHooks'      => 'system/modules/news_other/class/NewsOtherHooks.php',
));
