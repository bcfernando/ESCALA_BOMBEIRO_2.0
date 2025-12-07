@echo off
echo ============================================
echo  Restaurando schema mysql padrao do XAMPP
echo ============================================

rem parar o mysqld se estiver rodando
taskkill /IM mysqld.exe /F >nul 2>&1

rem renomear pasta mysql antiga
set DATA_DIR=C:\xampp\mysql\data
set BACKUP_DIR=C:\xampp\mysql\backup\mysql
set OLD_NAME=mysql_broken_%date:~6,4%%date:~3,2%%date:~0,2%

echo Renomeando "%DATA_DIR%\mysql" para "%DATA_DIR%\%OLD_NAME%"
ren "%DATA_DIR%\mysql" "%OLD_NAME%"

rem copiar schema mysql do backup padrão
echo Copiando arquivos de "%BACKUP_DIR%" para "%DATA_DIR%\mysql"
xcopy "%BACKUP_DIR%" "%DATA_DIR%\mysql" /E /I /Y

echo.
echo [OK] Pasta mysql restaurada. 
echo Agora abra o XAMPP e inicie o MySQL normalmente.
pause
