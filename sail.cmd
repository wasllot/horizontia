@echo off
REM Horizontia Docker Development Script
REM Usage: sail.cmd [command]

setlocal enabledelayedexpansion

set "COMPOSE_FILE=%~dp0docker-compose.yml"

if "%~1"=="" (
    echo Usage: sail [command]
    echo.
    echo Commands:
    echo   up          - Start all containers
    echo   up -d       - Start all containers in detached mode
    echo   down        - Stop all containers
    echo   stop        - Stop all containers
    echo   restart     - Restart all containers
    echo   logs        - View logs
    echo   logs -f     - View and follow logs
    echo   shell       - Enter PHP container shell
    echo   mysql       - Connect to MySQL
    echo   redis       - Connect to Redis CLI
    echo   artisan     - Run artisan command
    echo   npm         - Run npm command
    echo   test        - Run tests
    echo   build       - Build containers
    echo   ps          - Show running containers
    goto :end
)

docker compose --env-file .env.docker %*
set EXIT_CODE=%ERRORLEVEL%

:end
endlocal & exit /b %EXIT_CODE%
