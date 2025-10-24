# 📚 Documentación Técnica Completa - GameNews

## Índice
1. [Introducción](#1-introducción)
2. [Arquitectura del Sistema](#2-arquitectura-del-sistema)
3. [Base de Datos](#3-base-de-datos)
4. [Módulos y Componentes](#4-módulos-y-componentes)
5. [Sistema de Seguridad](#5-sistema-de-seguridad)
6. [Funcionalidades Principales](#6-funcionalidades-principales)
7. [Frontend y UX](#7-frontend-y-ux)
8. [Despliegue y Mantenimiento](#8-despliegue-y-mantenimiento)

---

## 1. Introducción

### 1.1 Descripción General

GameNews es un Content Management System (CMS) especializado en noticias sobre videojuegos. La aplicación está construida siguiendo el patrón arquitectónico MVC (Modelo-Vista-Controlador) utilizando el framework Symfony 6.4.

**Objetivo Principal:** Proporcionar una plataforma completa para que editores publiquen noticias sobre videojuegos y los usuarios puedan leerlas, comentarlas y valorarlas.

### 1.2 Tecnologías Utilizadas

**Backend:**
- PHP 8.2+
- Symfony 6.4 (Framework MVC)
- Doctrine ORM (Mapeo Objeto-Relacional)
- MySQL 8.0 (Base de datos)

**Frontend:**
- Twig (Motor de plantillas)
- Bootstrap 5.3 (Framework CSS)
- JavaScript Vanilla (Interactividad)
- Bootstrap Icons (Iconografía)

**Herramientas de Desarrollo:**
- Composer (Gestor de dependencias PHP)
- Symfony CLI (Herramientas de línea de comandos)
- Git (Control de versiones)


## 2. Arquitectura del Sistema

### 2.1 Patrón MVC en Symfony
```
┌─────────────────────────────────────────────────┐
│                   USUARIO                        │
└───────────────────┬─────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────┐
│              CONTROLADOR                         │
│  (src/Controller/)                              │
│  - Recibe peticiones HTTP                       │
│  - Procesa lógica de negocio                    │
│  - Llama a servicios y repositorios             │
└───────────┬──────────────────────┬───────────────┘
            │                      │
            ▼                      ▼
┌───────────────────┐    ┌────────────────────────┐
│      MODELO       │    │        VISTA           │
│  (src/Entity/)    │    │  (templates/)          │
│  - Entidades      │    │  - Plantillas Twig     │
│  - Repositorios   │    │  - HTML generado       │
│  - Lógica datos   │    └────────────────────────┘
└───────────────────┘
            │
            ▼
┌───────────────────────────────────────────────────┐
│              BASE DE DATOS (MySQL)                │
└───────────────────────────────────────────────────┘
```

### 2.2 Estructura de Directorios
```
PP1-GameNews/
├── bin/                    # Scripts ejecutables
│   ├── console            # CLI de Symfony
│   └── migrate-images.php # Script de migración
├── config/                # Configuración de la aplicación
│   ├── packages/          # Configuración de bundles
│   └── routes/            # Definición de rutas
├── public/                # Archivos públicos accesibles
│   ├── index.php          # Front controller
│   └── uploads/           # Archivos subidos
├── src/
│   ├── Command/           # Comandos de consola
│   ├── Controller/        # Controladores
│   ├── Entity/            # Entidades (modelos)
│   ├── Form/              # Formularios
│   ├── Repository/        # Repositorios de datos
│   └── Service/           # Servicios personalizados
├── templates/             # Plantillas Twig
│   ├── admin/             # Panel de administración
│   ├── home/              # Página principal
│   ├── news/              # Noticias
│   └── base.html.twig     # Plantilla base
├── var/                   # Archivos temporales
│   ├── cache/             # Caché de la aplicación
│   └── log/               # Registros (logs)
└── vendor/                # Dependencias de Composer
```

### 2.3 Flujo de una Petición HTTP
```
1. Usuario hace clic en "Noticias"
   ↓
2. Navegador envía GET /noticias
   ↓
3. public/index.php (Front Controller) recibe la petición
   ↓
4. Router de Symfony identifica la ruta: app_news_index
   ↓
5. Ejecuta NewsController::index()
   ↓
6. Controller consulta NewsRepository
   ↓
7. Repository hace query a la base de datos vía Doctrine
   ↓
8. Se obtienen las entidades News
   ↓
9. Controller pasa datos a la plantilla Twig
   ↓
10. Twig renderiza templates/news/index.html.twig
   ↓
11. HTML generado se envía al navegador
   ↓
12. Usuario ve la página de noticias
```
### 3.2 Descripción de Entidades Principales
```
```
### User (Usuario)

Representa a los usuarios del sistema con diferentes roles.

Campos:
id: Identificador único
email: Email único del usuario
username: Nombre de usuario único (3-20 caracteres)
password: Contraseña hasheada con bcrypt
roles: Array JSON de roles (ROLE_USER, ROLE_EDITOR, ROLE_ADMIN)
profileImage: Nombre del archivo de imagen de perfil
isActive: Estado de la cuenta (activo/suspendido)
isSubscribedToNewsletter: Suscripción al boletín
failedLoginAttempts: Intentos fallidos de login
blockedUntil: Fecha hasta la que está bloqueado
createdAt: Fecha de registro
lastLoginAt: Último inicio de sesión

Relaciones:

1:N con Comment (autor de comentarios)
1:N con News (autor de noticias)
1:N con NewsRating (valoraciones de noticias)
1:N con CommentVote (votos en comentarios)
1:N con Report (denuncias realizadas)

### News (Noticia)

Contenido principal de la aplicación.

Campos:
id: Identificador único
title: Título de la noticia (máx. 255 caracteres)
subtitle: Subtítulo opcional
body: Contenido completo (TEXT)
slug: URL amigable única
featuredImage: Nombre del archivo de imagen destacada
status: Estado (draft, published)
viewCount: Contador de visualizaciones
averageRating: Promedio de valoraciones (0-5)
ratingCount: Cantidad de valoraciones
publishedAt: Fecha de publicación
createdAt: Fecha de creación
updatedAt: Última actualización

Relaciones:

N:1 con User (autor)
M:N con Category (categorías)
M:N con Tag (etiquetas)
1:N con Comment (comentarios)
1:N con NewsRating (valoraciones)

Reglas de negocio:
Debe tener al menos una categoría
Solo las publicadas son visibles para usuarios
El slug se genera automáticamente del título

### Comment (Comentario)

Comentarios de usuarios en noticias.

Campos:
id: Identificador único
content: Contenido del comentario (TEXT)
likesCount: Cantidad de "me gusta"
dislikesCount: Cantidad de "no me gusta"
isApproved: Estado de aprobación (true por defecto)
createdAt: Fecha de creación

Relaciones:

N:1 con User (autor)
N:1 con News (noticia comentada)
1:N con CommentVote (votos recibidos)
1:N con Report (denuncias recibidas)

Reglas de negocio:
Solo usuarios autenticados pueden comentar
Se publican inmediatamente (sin moderación previa)
Un usuario puede votar solo una vez por comentario

### Category (Categoría)

Clasificación principal de noticias.

Campos:
id: Identificador único
name: Nombre único (máx. 100 caracteres)
description: Descripción opcional
slug: URL amigable única

Ejemplos: Noticias, Análisis, Guías, eSports, Hardware

Reglas de negocio:
No se puede eliminar si tiene noticias asociadas
El nombre debe ser único

### Tag (Etiqueta)

Clasificación secundaria más específica.

Campos:
id: Identificador único
name: Nombre único
description: Descripción opcional
synonyms: Sinónimos separados por comas (para búsqueda)
slug: URL amigable única

Ejemplos: PS5, Xbox Series X, Nintendo Switch, RPG, FPS
Reglas de negocio:

No se puede eliminar si tiene noticias asociadas
Los sinónimos mejoran la búsqueda


### 4. Módulos y Componentes

4.1 Módulo de Autenticación

Ubicación: src/Controller/SecurityController.php
Funcionalidades:

### Login (CU02)

Autenticación con email y contraseña
Verificación de credenciales
Bloqueo temporal tras 5 intentos fallidos (5 minutos)
"Recordarme" (cookie de 7 días)


### Registro (CU01)

Validación de email único
Validación de username único (3-20 caracteres)
Contraseña segura (8+ caracteres, mayúsculas, minúsculas, números)
Aceptación de términos y condiciones
Hash de contraseña con bcrypt


### Recuperación de Contraseña (CU03)

Envío de enlace por email
Token de un solo uso válido por 24 horas
Restablecimiento seguro
```


Código clave:

Archivo: src/Controller/SecurityController.php

<details>
  <summary>📄 Controlador completo: <code>SecurityController.php</code></summary>

```php
<?php
#[Route('/login', name: 'app_login')]
public function login(AuthenticationUtils $authenticationUtils): Response
{
    // Obtener error de autenticación si existe
    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('security/login.html.twig', [
        'last_username' => $lastUsername,
        'error' => $error,
    ]);
}
</details> ```


Configuración de seguridad:

Archivo: config/packages/security.yaml

<details>
  <summary>Ver configuración YAML</summary>

```yaml
security:
  password_hashers:
    Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
  providers:
    app_user_provider:
      entity:
        class: App\Entity\User
        property: email
  firewalls:
    main:
      form_login:
        login_path: app_login
        check_path: app_login
</details> ```