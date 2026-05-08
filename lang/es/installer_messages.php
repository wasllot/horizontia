<?php

return [

    /**
     *
     * Shared translations.
     *
     */
    'title' => 'Instalador de Lernen',
    'next' => 'Siguiente Paso',
    'finish' => 'Instalar',


    /**
     *
     * Home page translations.
     *
     */
    'welcome' => [
        'title'   => 'Bienvenido al Instalador de Lernen',
        'message' => 'Bienvenido al asistente de configuración.',
    ],


    /**
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'title' => 'Requisitos',
        'message' => 'Antes de continuar, por favor asegúrate de que tu <br > servidor cumple con los siguientes requisitos.',
    ],


    /**
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'title' => 'Permisos',
        'message' => 'Por favor, asegúrate de que Lernen tiene los permisos necesarios <br /> para acceder a las siguientes carpetas para continuar.',
    ],

    /**
     *
     * Database Seeders.
     *
     */
    'seeders' => [
        'title' => 'Migrar e Importar Contenido Demo',
        'migrate_title' => 'Migración de Base de Datos',
        'migrate_desc' => 'Migrar el esquema y estructura de la base de datos para Lernen.',
        'general_title' => 'Importación de Configuración del Sitio',
        'general_desc' => 'Importa la configuración básica para tu sitio.',
        'pages_title' => 'Importación de Páginas y Diseños',
        'pages_desc' => 'Importa todas las páginas predeterminadas y sus diseños para tu sitio.',
        'students_title' => 'Importación de Datos de Estudiantes de Ejemplo',
        'students_desc' => 'Importa perfiles y datos de estudiantes de demostración para pruebas.',
        'tutors_title' => 'Importación de Datos de Tutores de Ejemplo',
        'tutors_desc' => 'Importa perfiles y datos de tutores de demostración para pruebas.',
        'tooltip_text' => 'Haz clic en el botón Siguiente para comenzar la importación',
        'migrate_tooltip_text' => 'Haz clic en el botón Siguiente para comenzar la migración',
        'import_text' => 'Vamos a migrar la base de datos e importar contenido de demostración a tu sitio web <br /> para ayudarte a explorar y entender nuestro producto.',
    ],


    /**
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'title' => 'Configuración del Entorno',
        'message' => 'Por favor ingresa las credenciales de tu base de datos para continuar.',
        'save' => 'Guardar .env',
        'success' => 'La configuración de tu archivo .env ha sido guardada.',
        'errors' => 'No se pudo guardar el archivo .env, Por favor créalo manualmente.',
    ],
    
    'install' => 'Instalar',


    /**
     *
     * Final page translations.
     *
     */
    'final' => [
        'title' => 'Finalizado',
        'finished' => 'La aplicación se ha instalado correctamente.',
        'exit' => 'Haz clic aquí para salir',
    ],
    'checkPermissionAgain' => 'Verificar Permisos Nuevamente'
];
