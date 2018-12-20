<?php

namespace Pintushi\Bundle\ConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ConfigManagerScopeIdUpdateEvent extends Event
{
    const EVENT_NAME = 'pintushi_config.config_manager_scope_id_change';
}
