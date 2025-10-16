<?php
namespace EnderLab\MarvinManagerBundle\Reference;


use EnderLab\ToolsBundle\Service\EnumToArrayTrait;

enum ManagerActionReference: string
{
    use EnumToArrayTrait;

    case ACTION_START_DOCKER = 'action_start_docker';
    case ACTION_RESTART_DOCKER = 'action_restart_docker';
    case ACTION_STOP_DOCKER = 'action_stop_docker';
    case ACTION_BUILD_DOCKER = 'action_build_docker';
    case ACTION_EXECUTE_COMMAND_DOCKER = 'action_execute_command_docker';
}
