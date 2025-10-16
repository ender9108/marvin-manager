<?php
namespace EnderLab\MarvinManagerBundle\Reference;


use EnderLab\ToolsBundle\Service\EnumToArrayTrait;

enum ManagerActionReference: string
{
    use EnumToArrayTrait;

    case ACTION_START = 'start';
    case ACTION_START_ALL = 'start_all';
    case ACTION_RESTART = 'restart';
    case ACTION_RESTART_ALL = 'restart_all';
    case ACTION_STOP = 'stop';
    case ACTION_STOP_ALL = 'stop_all';
    case ACTION_BUILD = 'build';
    case ACTION_BUILD_ALL = 'build_all';
    case ACTION_EXEC_CMD = 'exec_cmd';
}
