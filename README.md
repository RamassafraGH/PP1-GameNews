# GameNews - Portal de Noticias sobre Videojuegos

Proyecto desarrollado con Symfony 6.4 para la gestión de noticias sobre videojuegos.

## 🎮 Características

### Casos de Uso Implementados
- **CU00**: Página Principal
- **CU01**: Registrarse
- **CU02**: Autenticarse
- **CU04**: Editar perfil
- **CU05**: Explorar noticias
- **CU06**: Buscar contenido
- **CU07**: Comentar contenido
- **CU09**: Votar comentario
- **CU10**: Votar noticia
- **CU11**: Suscribirse al boletín
- **CU12**: Gestión de noticias
- **CU13**: Editar categorías
- **CU14**: Editar etiquetas
- **CU20**: Ver noticia

## 🛠️ Tecnologías Utilizadas

- PHP 8.2+
- Symfony 6.4
- MySQL 8.0
- Bootstrap 5.3
- Bootstrap Icons
- Doctrine ORM
- Twig Templates

## 📋 Requisitos Previos

- Laragon 6.0 (incluye PHP, MySQL, Apache)
- Composer
- Git

## 🚀 Instalación

### 1. Clonar el repositorio
git clone https://github.com/RamassafraGH/PP1-GameNews.git
cd PP1-GameNews


### 2. Instalar dependencias
composer install

### 3. Configurar la base de datos
Editar el archivo `.env` con tus credenciales:
DATABASE_URL="mysql://root:@127.0.0.1:3306/gamenews?serverVersion=8.0&charset=utf8mb4"

### 4. Crear la base de datos y ejecutar migraciones
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

### 5. Cargar datos de prueba (opcional)
php bin/console doctrine:fixtures:load

## 👤 Usuarios de Prueba

Si cargaste los fixtures, puedes usar estos usuarios:

### Administrador
- Email: `admin@gamenews.com`
- Contraseña: `Admin123`

### Editor de Contenido
- Email: `editor@gamenews.com`
- Contraseña: `Editor123`

### Usuario Normal
- Email: `usuario@gamenews.com`
- Contraseña: `Usuario123`

## 🎯 Uso

### Crear usuario administrador manualmente
php bin/console app:create-admin

### Iniciar servidor de desarrollo
symfony server:start
# o con PHP
php -S localhost:8000 -t public

Accede a: `http://localhost:8000`

## 📁 Estructura del Proyecto
```
src/
├── Command/          # Comandos de consola
├── Controller/       # Controladores
│   ├── Admin/       # Controladores del panel admin
│   └── ...
├── Entity/          # Entidades Doctrine
├── Form/            # Formularios Symfony
├── Repository/      # Repositorios Doctrine
└── DataFixtures/    # Datos de prueba

templates/
├── admin/           # Plantillas del panel admin
├── home/            # Página principal
├── news/            # Noticias
├── profile/         # Perfil de usuario
├── registration/    # Registro
├── security/        # Login/Logout
└── base.html.twig   # Plantilla base

public/
└── uploads/         # Imágenes subidas
    ├── profiles/    # Fotos de perfil
    └── news/        # Imágenes de noticias
```

## 🔐 Roles y Permisos

- **ROLE_USER**: Usuario registrado (puede comentar, votar, editar perfil)
- **ROLE_EDITOR**: Editor de contenido (puede gestionar noticias, categorías y etiquetas)
- **ROLE_ADMIN**: Administrador (acceso completo)

## 📝 Reglas de Negocio Implementadas

### Registro (CU01)
- Email único en el sistema
- Contraseña mínimo 8 caracteres, incluir mayúsculas, minúsculas y números
- Nombre de usuario: 3-20 caracteres, solo alfanuméricos, guiones y guiones bajos

### Perfil (CU04)
- Imágenes: JPG/PNG, máximo 2MB, resolución máxima 500x500px
- Nombres de usuario únicos

### Noticias (CU12)
- Toda noticia debe tener al menos una categoría
- Estados: borrador (solo visible para editores) o publicado
- Imágenes: JPG/PNG, máximo 2MB

### Votación (CU10)
- Un usuario solo puede votar una vez por noticia
- Puntuaciones: 1-5 estrellas

### Comentarios (CU07, CU09)
- Solo usuarios autenticados pueden comentar
- Comentarios requieren aprobación de moderador
- Los usuarios pueden dar "me gusta" o "no me gusta"
- Un voto por comentario, puede cambiar o retirar su voto

### Categorías y Etiquetas (CU13, CU14)
- No se pueden eliminar si tienen noticias asociadas
- Nombres únicos

## 🔄 Flujo de Trabajo Git
# Crear nueva funcionalidad
git checkout -b feature/nombre-funcionalidad

# Hacer commits
git add .
git commit -m "feat: descripción del cambio"

# Subir cambios
git push origin feature/nombre-funcionalidad

### Archivos NUEVOS creados:
src/Controller/Admin/ModerationController.php
src/Controller/Admin/NewsletterController.php
src/Controller/Admin/UserManagementController.php
src/Controller/ReportController.php
src/Form/ReportFormType.php
templates/admin/moderation/index.html.twig
templates/admin/moderation/review.html.twig
templates/admin/users/index.html.twig
templates/report/comment.html.twig

### Archivos ACTUALIZADOS:
src/Controller/Admin/DashboardController.php
src/Controller/NewsController.php (método vote)
src/Entity/NewsRating.php (agregar método)
src/Entity/Comment.php (constructor)
src/Repository/NewsRepository.php
templates/admin/dashboard/index.html.twig (reescribir completamente)
templates/admin/moderation/index.html.twig (actualizar responsive)
templates/admin/moderation/review.html.twig (reescribir)
templates/news/show.html.twig (sistema de votación)
templates/news/index.html.twig (cards de noticias)
templates/home/index.html.twig (cards destacadas)
templates/base.html.twig
templates/admin/newsletter/subscribers.html.twig
templates/admin/newsletter/send.html.twig


## 🐛 Solución de Problemas

### Error: "No route found"
Limpiar caché:
php bin/console cache:clear

### Error de permisos en uploads
chmod -R 777 public/uploads

### Error de base de datos
Verificar que MySQL esté corriendo en Laragon y las credenciales sean correctas.

## 👥 Equipo de Desarrollo

- Taiel Giuliano
- Agustin Ifran Sanchez
- Ramiro Massafra
- Benjamin Zurbriggen

## 📄 Licencia

Este proyecto es parte de un trabajo académico.

## 🔗 Enlaces

- Repositorio: https://github.com/RamassafraGH/PP1-GameNews.git
- Documentación Symfony: https://symfony.com/doc/current/index.html


