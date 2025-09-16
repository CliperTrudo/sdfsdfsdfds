<?php

use TutoriasBooking\Infrastructure\Activator;
use TutoriasBooking\Infrastructure\Loader;

register_activation_hook(TB_PLUGIN_FILE, [Activator::class, 'activate']);

Activator::maybe_upgrade();

Loader::init();
