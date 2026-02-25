@echo off
setlocal enabledelayedexpansion
title Deploy to Render - Step by Step
color 0A
chcp 65001 >nul

echo ╔═══════════════════════════════════════════════════════════════╗
echo ║                                                               ║
echo ║   🚀 АВТОМАТИЧЕСКИЙ ДЕПЛОЙ НА RENDER.COM                      ║
echo ║                                                               ║
echo ╚═══════════════════════════════════════════════════════════════╝
echo.
echo ✅ Git репозиторий создан
echo ✅ Все файлы закоммичены
echo ✅ Готово к деплою
echo.
echo ════════════════════════════════════════════════════════════════
echo.
echo [ШАГ 1/3] Создание GitHub репозитория
echo ════════════════════════════════════════════════════════════════
echo.

REM Check if gh CLI is installed
where gh >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo ✅ GitHub CLI обнаружен
    echo.
    echo Хотите создать репозиторий через GitHub CLI? (быстрее)
    echo.
    echo 1. Да, создать через CLI (автоматически)
    echo 2. Нет, я создам через веб-интерфейс (вручную)
    echo.
    choice /c 12 /n /m "Выберите (1 или 2): "

    if errorlevel 2 goto :manual
    if errorlevel 1 goto :auto_gh
) else (
    echo ℹ️  GitHub CLI не установлен
    echo    Будем использовать веб-интерфейс
    echo.
    goto :manual
)

:auto_gh
echo.
echo Создаю приватный репозиторий через GitHub CLI...
echo.
gh repo create catalog-admin-tool --private --source=. --remote=origin --push

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ Репозиторий создан и код загружен!
    echo.
    for /f "tokens=*" %%i in ('gh repo view --json url -q .url') do set REPO_URL=%%i
    echo 📦 Репозиторий: !REPO_URL!
    echo.
    goto :render_setup
) else (
    echo.
    echo ❌ Не удалось создать репозиторий через CLI
    echo    Попробуем вручную...
    echo.
    timeout /t 2 /nobreak >nul
    goto :manual
)

:manual
echo.
echo ════════════════════════════════════════════════════════════════
echo   Создание репозитория вручную
echo ════════════════════════════════════════════════════════════════
echo.
echo 1. Сейчас откроется браузер на github.com/new
echo 2. Создайте новый репозиторий:
echo    - Название: catalog-admin-tool
echo    - Приватность: Private (рекомендуется)
echo    - НЕ добавляйте README, .gitignore или LICENSE
echo 3. Нажмите "Create repository"
echo 4. СКОПИРУЙТЕ URL репозитория (например: https://github.com/username/catalog-admin-tool.git)
echo.
timeout /t 3 /nobreak >nul
start https://github.com/new

echo.
echo Нажмите Enter когда создадите репозиторий...
pause >nul

echo.
echo Введите URL вашего GitHub репозитория:
echo (Например: https://github.com/username/catalog-admin-tool.git)
echo.
set /p REPO_URL="URL: "

if "!REPO_URL!"=="" (
    echo [ERROR] URL не введен
    pause
    exit /b 1
)

echo.
echo Добавляю remote и отправляю код...
echo.

git remote add origin "!REPO_URL!" 2>nul
git branch -M main
git push -u origin main

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Не удалось отправить код
    echo.
    echo Возможно нужна авторизация. Попробуйте:
    echo.
    echo cd d:\monorepa\monorepa\projects\catalog-microservice\api\web\admin
    echo git push -u origin main
    echo.
    pause
    exit /b 1
)

echo.
echo ✅ Код загружен на GitHub!
echo.

:render_setup
echo ════════════════════════════════════════════════════════════════
echo [ШАГ 2/3] Регистрация на Render.com
echo ════════════════════════════════════════════════════════════════
echo.
echo Сейчас откроется Render.com
echo.
echo 1. Нажмите "Get Started"
echo 2. Войдите через GitHub
echo 3. Разрешите доступ к репозиториям
echo.
timeout /t 3 /nobreak >nul
start https://render.com

echo.
echo Нажмите Enter когда зарегистрируетесь...
pause >nul

echo.
echo ════════════════════════════════════════════════════════════════
echo [ШАГ 3/3] Создание Web Service на Render
echo ════════════════════════════════════════════════════════════════
echo.
echo Сейчас откроется страница создания сервиса
echo.
timeout /t 2 /nobreak >nul
start https://dashboard.render.com/select-repo?type=web

echo.
echo ╔═══════════════════════════════════════════════════════════════╗
echo ║                                                               ║
echo ║   📋 НАСТРОЙКИ ДЛЯ RENDER                                     ║
echo ║                                                               ║
echo ╚═══════════════════════════════════════════════════════════════╝
echo.
echo 1. Найдите репозиторий: catalog-admin-tool
echo 2. Нажмите: Connect
echo.
echo 3. Настройте сервис:
echo    ┌─────────────────────────────────────────────────────────┐
echo    │ Name:              catalog-admin                        │
echo    │ Region:            Oregon                               │
echo    │ Branch:            main                                 │
echo    │ Runtime:           Docker                               │
echo    │ Dockerfile Path:   ./Dockerfile.render                  │
echo    │ Instance Type:     Free                                 │
echo    └─────────────────────────────────────────────────────────┘
echo.
echo 4. Environment Variables (нажмите "Add Environment Variable"):
echo    ┌─────────────────────────────────────────────────────────┐
echo    │ DB_HOST      = reporting.dba.yallasvc.net               │
echo    │ DB_PORT      = 5432                                     │
echo    │ DB_NAME      = catalog_microservice                     │
echo    │ DB_USER      = ekaterina_miroshnik                      │
echo    │ DB_PASSWORD  = jY8I8cRwaSlXHoR75j2tpQGDkqgxVcqf         │
echo    └─────────────────────────────────────────────────────────┘
echo.
echo 5. Нажмите: "Create Web Service"
echo.
echo ════════════════════════════════════════════════════════════════
echo.
echo Render начнет деплой (5-10 минут)
echo.
echo После завершения ваш инструмент будет доступен по адресу:
echo    https://catalog-admin.onrender.com/ads-param-matcher.html
echo.
echo ════════════════════════════════════════════════════════════════
echo.
echo ✅ ВСЁ ГОТОВО К ДЕПЛОЮ!
echo.
echo Дождитесь завершения деплоя в Render dashboard
echo и отправьте URL пользователям!
echo.
echo ════════════════════════════════════════════════════════════════
echo.
pause
