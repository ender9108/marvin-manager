<?php
namespace EnderLab\MarvinManagerBundle\Reference;


use EnderLab\ToolsBundle\Service\EnumToArrayTrait;

enum ManagerMessageReference: string
{
    use EnumToArrayTrait;

    case REQUEST_INSTALL_DOCKER = 'request_install_docker';
    case REQUEST_DECLARE_DOCKER = 'request_declare_docker';
    case REQUEST_UPDATE_DOCKER = 'request_update_docker';
    case REQUEST_DELETE_DOCKER = 'request_delete_docker';
    case REQUEST_START_DOCKER = 'request_start_docker';
    case REQUEST_RESTART_DOCKER = 'request_restart_docker';
    case REQUEST_STOP_DOCKER = 'request_stop_docker';
    case REQUEST_BUILD_DOCKER = 'request_build_docker';
    case REQUEST_EXECUTE_COMMAND_DOCKER = 'request_execute_command_docker';
    case REQUEST_DISCOVER_DOCKER = 'request_discover_docker';
    case RESPONSE_INSTALL_DOCKER = 'response_install_docker';
    case RESPONSE_DECLARE_DOCKER = 'response_declare_docker';
    case RESPONSE_UPDATE_DOCKER = 'response_update_docker';
    case RESPONSE_DELETE_DOCKER = 'response_delete_docker';
    case RESPONSE_START_DOCKER = 'response_start_docker';
    case RESPONSE_RESTART_DOCKER = 'response_restart_docker';
    case RESPONSE_STOP_DOCKER = 'response_stop_docker';
    case RESPONSE_BUILD_DOCKER = 'response_build_docker';
    case RESPONSE_EXECUTE_COMMAND_DOCKER = 'response_execute_command_docker';
}
