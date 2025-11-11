# Casos de Uso - PP1-GameNews (Detallado con Código Real)

---

## CU01: Registrarse

*Archivos involucrados:*
- Controller: src/Controller/RegistrationController.php
- Form: src/Form/RegistrationFormType.php
- Entity: src/Entity/User.php
- Repository: src/Repository/UserRepository.php
- Template: templates/registration/register.html.twig

*Flujo y código:*
```php
// src/Controller/RegistrationController.php
#[Route('/register', name: 'app_register')]
public function register(Request $request): Response
{
    // CU01: Registro de Usuario
    $user = new User();
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Hashear la contraseña antes de guardar
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
        );
        
        // Asignar rol básico y fecha de creación
        $user->setRoles(['ROLE_USER']);
        $user->setCreatedAt(new \DateTimeImmutable());
        
        // Persistir usuario
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Redireccionar a login
        return $this->redirectToRoute('app_login');
    }

    return $this->render('registration/register.html.twig', [
        'registrationForm' => $form,
    ]);
}
```

*Explicación de comandos clave:*
1. $this->createForm(RegistrationFormType::class, $user) → Crea instancia del formulario vinculada a la entidad User.
2. $form->handleRequest($request) → Procesa datos POST/GET del formulario.
3. $form->isSubmitted() && $form->isValid() → Verifica si fue enviado y cumple validaciones (email único, contraseña válida).
4. $this->passwordHasher->hashPassword() → Encripta la contraseña usando bcrypt.
5. $user->setRoles(['ROLE_USER']) → Asigna rol básico de usuario.
6. $entityManager->persist($user) → Marca la entidad para persistencia.
7. $entityManager->flush() → Ejecuta INSERT en base de datos.
8. $this->redirectToRoute('app_login') → Redirecciona a página de login.

*Plantilla asociada:*
```twig
{# templates/registration/register.html.twig #}
<form method="post">
    {{ form_start(registrationForm) }}
        {{ form_row(registrationForm.email) }}
        {{ form_row(registrationForm.plainPassword) }}
        {{ form_row(registrationForm.agreeTerms) }}
        <button type="submit">Registrarse</button>
    {{ form_end(registrationForm) }}
</form>
```

---

## CU02: Autenticarse

*Archivos involucrados:*
- Controller: src/Controller/SecurityController.php
- Entity: src/Entity/User.php
- Repository: src/Repository/UserRepository.php
- Template: templates/security/login.html.twig
- Configuración: config/security.yaml (UserProvider)

*Flujo y código:*

```php
// src/Controller/SecurityController.php
#[Route('/login', name: 'app_login')]
public function login(AuthenticationUtils $authenticationUtils): Response
{
    // CU02: Autenticación
    // Obtener error de autenticación si existe
    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('security/login.html.twig', [
        'last_username' => $lastUsername,
        'error' => $error,
    ]);
}

// Logout (parte del CU02)
#[Route('/logout', name: 'app_logout')]
public function logout(): void
{
    // Symfony maneja automáticamente la invalidación de sesión
}
```

*Explicación de comandos clave:*
1. $authenticationUtils->getLastAuthenticationError() → Recupera el último error de autenticación (si las credenciales fallaron).
2. $authenticationUtils->getLastUsername() → Obtiene el último nombre de usuario ingresado (para pre-rellenar el formulario).
3. $this->render('security/login.html.twig', [...])  → Renderiza la plantilla de login con las variables de error y usuario.
4. Symfony Security maneja internamente: verificación de credenciales, creación de sesión, tokens de seguridad (UserProvider en UserRepository).

*Plantilla asociada:*
```twig
{# templates/security/login.html.twig #}
{% if error %}
    <div class="alert alert-danger">
        {{ error.messageKey|trans(error.messageData, 'security') }}
    </div>
{% endif %}

<form method="post">
    <label for="username">Email:</label>
    <input type="email" id="username" name="_username" value="{{ last_username }}" required>
    
    <label for="password">Contraseña:</label>
    <input type="password" id="password" name="_password" required>
    
    <button type="submit">Ingresar</button>
</form>
```

---

## CU04: Editar perfil

*Archivos involucrados:*
- Controller: src/Controller/ProfileController.php
- Form: src/Form/ProfileFormType.php
- Entity: src/Entity/User.php
- Template: templates/profile/edit.html.twig

*Flujo y código:*

```php
// src/Controller/ProfileController.php
#[Route('/profile/edit', name: 'app_profile_edit')]
#[IsGranted('ROLE_USER')]
public function edit(Request $request): Response
{
    // CU04: Editar Perfil
    $user = $this->getUser(); // Obtiene el usuario autenticado
    $form = $this->createForm(ProfileFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Procesar imagen de perfil si se subió una nueva
        $profileImage = $form->get('profileImage')->getData();
        if ($profileImage) {
            $newFilename = $this->uploadProfileImage($profileImage);
            $user->setProfileImage($newFilename);
        }
        
        // Actualizar fecha de última modificación
        $user->setUpdatedAt(new \DateTimeImmutable());
        
        // Persistir cambios
        $this->entityManager->flush();
        $this->addFlash('success', 'Perfil actualizado correctamente');
        
        return $this->redirectToRoute('app_profile_index');
    }

    return $this->render('profile/edit.html.twig', [
        'profileForm' => $form,
    ]);
}

#[Route('/profile', name: 'app_profile_index')]
#[IsGranted('ROLE_USER')]
public function index(): Response
{
    // CU04: Ver perfil
    $user = $this->getUser();
    return $this->render('profile/index.html.twig', [
        'user' => $user,
    ]);
}
```

*Explicación de comandos clave:*
1. #[IsGranted('ROLE_USER')] → Anotación que verifica permisos; solo usuarios autenticados acceden.
2. $this->getUser() → Obtiene la entidad User del usuario autenticado desde la sesión.
3. $this->createForm(ProfileFormType::class, $user) → Crea formulario vinculado al usuario actual.
4. $form->handleRequest($request) → Procesa cambios del formulario.
5. $this->uploadProfileImage($profileImage) → Maneja subida de archivo (genera nombre único, guarda en servidor).
6. $user->setUpdatedAt(new \DateTimeImmutable()) → Registra fecha de última edición.
7. $entityManager->flush() → Guarda cambios (UPDATE en BD).
8. $this->addFlash('success', '...') → Añade mensaje a sesión para mostrar notificación.

*Plantilla asociada:*
twig
{# templates/profile/edit.html.twig #}
<form method="post" enctype="multipart/form-data">
    {{ form_start(profileForm) }}
        {{ form_row(profileForm.email) }}
        {{ form_row(profileForm.username) }}
        {{ form_row(profileForm.profileImage) }}
        <button type="submit">Actualizar Perfil</button>
    {{ form_end(profileForm) }}
</form>


---

## CU05: Explorar noticias

*Archivos involucrados:*
- Controller: src/Controller/HomeController.php, src/Controller/NewsController.php
- Repository: src/Repository/NewsRepository.php
- Entity: src/Entity/News.php, src/Entity/Category.php
- Template: templates/home/index.html.twig, templates/news/index.html.twig

*Flujo y código:*

```php
// src/Controller/NewsController.php
#[Route('/news', name: 'app_news_index')]
public function index(Request $request, PaginatorInterface $paginator): Response
{
    // CU05: Explorar noticias
    $page = $request->query->getInt('page', 1);
    $category = $request->query->get('category');
    
    // Construir query base
    $queryBuilder = $this->newsRepository->createQueryBuilder('n')
        ->where('n.status = :status')
        ->setParameter('status', 'published')
        ->orderBy('n.publishedAt', 'DESC');
    
    // Filtrar por categoría si se proporciona
    if ($category) {
        $queryBuilder->innerJoin('n.categories', 'c')
            ->andWhere('c.slug = :categorySlug')
            ->setParameter('categorySlug', $category);
    }
    
    // Paginar resultados (10 noticias por página)
    $news = $paginator->paginate(
        $queryBuilder->getQuery(),
        $page,
        10
    );
    
    return $this->render('news/index.html.twig', [
        'news' => $news,
        'current_category' => $category,
    ]);
}

// Método auxiliar en NewsRepository
// src/Repository/NewsRepository.php
public function findPublishedByCategory(?string $categorySlug = null, int $limit = 10): array
{
    $query = $this->createQueryBuilder('n')
        ->where('n.status = :status')
        ->setParameter('status', 'published')
        ->orderBy('n.publishedAt', 'DESC')
        ->setMaxResults($limit);
    
    if ($categorySlug) {
        $query->innerJoin('n.categories', 'c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $categorySlug);
    }
    
    return $query->getQuery()->getResult();
}
```

*Explicación de comandos clave:*
1. $request->query->getInt('page', 1) → Obtiene parámetro ?page= de URL (por defecto 1).
2. $this->newsRepository->createQueryBuilder('n') → Crea QueryBuilder para construir consulta SQL dinámica.
3. ->where('n.status = :status') → Filtra solo noticias publicadas.
4. ->innerJoin('n.categories', 'c') → Une tabla de categorías para filtrado.
5. ->orderBy('n.publishedAt', 'DESC') → Ordena por fecha descendente (más recientes primero).
6. $paginator->paginate(...) → Divide resultados en páginas (10 por página).
7. $this->render('news/index.html.twig', [...]) → Pasa datos a plantilla.

*Plantilla asociada:*
twig
{# templates/news/index.html.twig #}
<h1>Noticias sobre Videojuegos</h1>

<div class="news-list">
    {% for article in news %}
        <div class="news-card">
            <h2><a href="{{ path('app_news_show', {slug: article.slug}) }}">{{ article.title }}</a></h2>
            <p>{{ article.excerpt }}</p>
            <small>Por {{ article.author.username }} - {{ article.publishedAt|date('d/m/Y') }}</small>
        </div>
    {% endfor %}
</div>

{# Paginación #}
<nav>
    {{ knp_pagination_render(news) }}
</nav>


---

## CU06: Buscar contenido

*Archivos involucrados:*
- Form: src/Form/SearchFormType.php
- Controller: src/Controller/NewsController.php
- Repository: src/Repository/NewsRepository.php
- Template: templates/news/index.html.twig, incluido en templates/base.html.twig

*Flujo y código:*

```php
// src/Controller/NewsController.php
#[Route('/', name: 'app_news_index')]
    public function index(Request $request): Response
{
    // CU06: Buscar contenido
    $searchTerm = $request->query->get('q', '');
    $category = $request->query->get('category');
    $tag = $request->query->get('tag');
    $from = $request->query->get('from');
    $to = $request->query->get('to');

    $results = [];

    // Buscar si hay término válido o filtros activos
    if (strlen($searchTerm) >= 3 || $category || $tag || $from || $to) {
        $results = $this->newsRepository->searchByFilters($searchTerm, $category, $tag, $from, $to);
    }

    return $this->render('news/search_results.html.twig', [
        'searchTerm' => $searchTerm,
        'category' => $category,
        'tag' => $tag,
        'from' => $from,
        'to' => $to

// Método en NewsRepository
// src/Repository/NewsRepository.php
public function searchNews(
    ?string $query = null,
    ?Category $category = null,
    ?Tag $tag = null,
    ?\DateTime $dateFrom = null,
    ?\DateTime $dateTo = null
): array {
    $qb = $this->createQueryBuilder('n')
        ->leftJoin('n.tags', 't')
        ->leftJoin('n.categories', 'c')
        ->where('n.status = :status')
        ->setParameter('status', 'published');

    if ($term && strlen($term) >= 3) {
        $qb->andWhere(
            'n.title LIKE :term 
            OR n.body LIKE :term 
            OR t.name LIKE :term 
            OR c.name LIKE :term'
        )->setParameter('term', '%'.$term.'%');
    }

    if ($category) {
        $qb->andWhere('c.name = :category')
           ->setParameter('category', $category);
    }

    if ($tag) {
        $qb->andWhere('t.name = :tag')
           ->setParameter('tag', $tag);
    }

    if ($from) {
        $qb->andWhere('n.publishedAt >= :from')
           ->setParameter('from', new \DateTime($from));
    }

    if ($to) {
        $qb->andWhere('n.publishedAt <= :to')
           ->setParameter('to', new \DateTime($to));
    }

    return $qb->orderBy('n.publishedAt', 'DESC')
              ->getQuery()
              ->getResult();
}
```

*Explicación de comandos clave:*
1. $request->query->get('q', '') → Obtiene parámetro de búsqueda ?q=texto de la URL.
2. strlen($searchTerm) >= 3 → Valida longitud mínima para evitar búsquedas vacías.
3. $this->newsRepository->searchByTerm($searchTerm) → Ejecuta búsqueda en repositorio.
4. ->leftJoin('n.tags', 't') → Incluye tags pero sin requerir que existan (búsqueda opcional).
5. 'n.title LIKE :term OR n.body LIKE :term ...' → Busca en múltiples campos.
6. ->setParameter('term', '%'.$term.'%') → Parámetro preparado para prevenir SQL injection.

*Plantilla asociada (incluida en base):*
twig
{# templates/base.html.twig (en navbar) #}
<form method="GET" action="{{ path('app_search') }}">
    <input type="text" name="q" placeholder="Buscar noticias..." value="{{ app.request.query.get('q', '') }}">
    <button type="submit">Buscar</button>
</form>


---

## CU07: Comentar contenido

*Archivos involucrados:*
- Form: src/Form/CommentFormType.php
- Controller: src/Controller/CommentController.php
- Entity: src/Entity/Comment.php, src/Entity/News.php
- Repository: src/Repository/CommentRepository.php
- Template: templates/news/show.html.twig

*Flujo y código:*

```php
// src/Controller/CommentController.php
#[Route('/news/{slug}/comment', name: 'app_comment_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function create(Request $request, News $news): Response
{
    // CU07: Comentar contenido
    $comment = new Comment();
    $form = $this->createForm(CommentFormType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Asociar comentario a la noticia y al usuario
        $comment->setNews($news);
        $comment->setUser($this->getUser());
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setVisible(true); // Visibilidad por defecto
        
        // Persistir comentario
        $this->entityManager->persist($comment);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Comentario publicado');
        return $this->redirectToRoute('app_news_show', ['slug' => $news->getSlug()]);
    }

    return $this->redirectToRoute('app_news_show', ['slug' => $news->getSlug()]);
}
```

*Explicación de comandos clave:*
1. #[IsGranted('ROLE_USER')] → Solo usuarios autenticados pueden comentar.
2. News $news → Symfony inyecta automáticamente la entidad News por su slug (ParamConverter).
3. new Comment() → Crea nueva instancia de comentario.
4. $comment->setNews($news) → Establece relación many-to-one con News.
5. $comment->setUser($this->getUser()) → Asigna al usuario actual como autor.
6. $entityManager->persist($comment) → Marca para inserción.
7. $entityManager->flush() → Ejecuta INSERT en BD.
8. return $this->redirectToRoute(...) → Redirecciona de vuelta a la noticia.

*Plantilla asociada (en show.html.twig):*
twig
{# templates/news/show.html.twig #}
<section class="comments">
    <h3>Comentarios ({{ news.comments|length }})</h3>
    
    {% for comment in news.comments %}
        <div class="comment">
            <strong>{{ comment.user.username }}</strong>
            <small>{{ comment.createdAt|date('d/m/Y H:i') }}</small>
            <p>{{ comment.content }}</p>
        </div>
    {% endfor %}
    
    {% if is_granted('ROLE_USER') %}
        <form method="POST" action="{{ path('app_comment_create', {slug: news.slug}) }}">
            {{ form_start(commentForm) }}
                {{ form_row(commentForm.content) }}
                <button type="submit">Comentar</button>
            {{ form_end(commentForm) }}
        </form>
    {% endif %}
</section>


---

## CU08: Denunciar

*Archivos involucrados:*
- Form: src/Form/ReportFormType.php
- Controller: src/Controller/ReportController.php
- Entity: src/Entity/Report.php, src/Entity/Comment.php
- Repository: src/Repository/ReportRepository.php
- Template: templates/report/comment.html.twig

*Flujo y código:*

```php
// src/Controller/ReportController.php

// Ruta para denunciar (reportar) un comentario específico
// El parámetro {id} identifica el comentario a denunciar
// Solo usuarios autenticados (ROLE_USER) pueden acceder
#[Route('/comment/{id}/report', name: 'app_report_comment', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function reportComment(Request $request, Comment $comment): Response
{
    
    // Este método permite a un usuario enviar una denuncia
    // sobre un comentario que considere inapropiado.
    // Se crea una entidad Report asociada al comentario y al usuario actual.

    // 1. Crear una nueva instancia del reporte
    $report = new Report();

    // 2. Crear el formulario de denuncia usando ReportFormType
    //    Este formulario contendrá los campos "reason" y "description"
    $form = $this->createForm(ReportFormType::class, $report);

    // 3. Procesar la solicitud HTTP (POST)
    //    Symfony asocia los datos del formulario enviados por el usuario
    //    a la entidad $report.
    $form->handleRequest($request);

    // 4. Validar si el formulario fue enviado y sus datos son válidos
    if ($form->isSubmitted() && $form->isValid()) {

        // --- Asociaciones lógicas del reporte ---

        // Enlazar el reporte con el comentario denunciado
        $report->setTargetComment($comment);

        // Enlazar el reporte con el usuario autenticado que lo envía
        $report->setReporter($this->getUser());

        // Guardar el motivo seleccionado en el formulario
        $report->setReason($form->get('reason')->getData());

        // Guardar la descripción detallada de la denuncia
        $report->setDescription($form->get('description')->getData());

        // Registrar la fecha y hora en que se crea la denuncia
        $report->setCreatedAt(new \DateTimeImmutable());

        // Estado inicial del reporte: aún no resuelto
        $report->setResolved(false);

        // --- Persistencia en base de datos ---

        // Registrar la nueva entidad Report en el EntityManager
        $this->entityManager->persist($report);

        // Ejecutar la escritura en la base de datos
        $this->entityManager->flush();

        // --- Respuesta al usuario ---

        // Agregar un mensaje flash que se mostrará en la interfaz
        $this->addFlash('info', 'Denuncia enviada a moderación');

        // Redirigir al usuario de vuelta a la noticia donde estaba el comentario
        return $this->redirectToRoute('app_news_show', [
            'slug' => $comment->getNews()->getSlug()
        ]);
    }

    // Si el formulario no es válido o no se envió correctamente,
    // simplemente redirigimos al detalle de la noticia sin guardar nada.
    return $this->redirectToRoute('app_news_show', [
        'slug' => $comment->getNews()->getSlug()
    ]);
}
```

*Explicación de comandos clave:*
1. #[IsGranted('ROLE_USER')] → Solo usuarios registrados pueden denunciar.
2. Comment $comment → ParamConverter inyecta el comentario por su ID.
3. $report->setTargetComment($comment) → Vincula el reporte al comentario denunciado.
4. $report->setReporter($this->getUser()) → Registra quién hace la denuncia.
5. $report->setResolved(false) → Marca como pendiente de revisión.
6. $entityManager->persist($report) y flush() → Guarda en BD.

---

## CU09: Votar comentario

*Archivos involucrados:*
- Controller: src/Controller/CommentController.php
- Entity: src/Entity/CommentVote.php, src/Entity/Comment.php
- Repository: src/Repository/CommentVoteRepository.php
- Template: templates/news/show.html.twig

*Flujo y código:*

```php
// src/Controller/CommentController.php
#[Route('/comment/{id}/vote', name: 'app_comment_vote', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function vote(Request $request, Comment $comment): Response
{
    // CU09: Votar comentario
    $value = $request->request->getInt('value'); // +1 o -1
    
    // Buscar voto existente
    $existingVote = $this->commentVoteRepository->findOneBy([
        'user' => $this->getUser(),
        'comment' => $comment,
    ]);

    if ($existingVote) {
        // Actualizar voto existente
        $existingVote->setValue($value);
    } else {
        // Crear nuevo voto
        $vote = new CommentVote();
        $vote->setUser($this->getUser());
        $vote->setComment($comment);
        $vote->setValue($value);
        $this->entityManager->persist($vote);
    }

    $this->entityManager->flush();

    // Recalcular puntuación total del comentario
    $totalScore = $this->commentVoteRepository->calculateCommentScore($comment);
    $comment->setScore($totalScore);
    $this->entityManager->flush();

    return $this->redirectToRoute('app_news_show', ['slug' => $comment->getNews()->getSlug()]);
}

// Método auxiliar en CommentVoteRepository
// src/Repository/CommentVoteRepository.php
public function calculateCommentScore(Comment $comment): int
{
    $result = $this->createQueryBuilder('cv')
        ->select('SUM(cv.value) as totalScore')
        ->where('cv.comment = :comment')
        ->setParameter('comment', $comment)
        ->getQuery()
        ->getOneOrNullResult();

    return $result['totalScore'] ?? 0;
}
```

*Explicación de comandos clave:*
1. $request->request->getInt('value') → Obtiene el valor del voto (+1 o -1) del POST.
2. $this->commentVoteRepository->findOneBy([...]) → Busca si el usuario ya votó este comentario.
3. Condición: si existe, actualiza; si no, crea nuevo voto.
4. $vote->setValue($value) → Establece valor del voto.
5. $this->commentVoteRepository->calculateCommentScore() → Suma todos los votos.
6. $comment->setScore($totalScore) → Actualiza puntuación acumulada.

---

## CU10: Votar noticia

*Archivos involucrados:*
- Controller: src/Controller/NewsController.php
- Entity: src/Entity/NewsRating.php, src/Entity/News.php
- Repository: src/Repository/NewsRatingRepository.php
- Template: templates/news/show.html.twig

*Flujo y código:*

```php
// src/Controller/NewsController.php
#[Route('/news/{id}/rate', name: 'app_news_rate', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function rate(Request $request, News $news): Response
{
    // CU10: Votar noticia
    $score = $request->request->getInt('score'); // 1-5
    
    // Validar que el score esté en rango
    if ($score < 1 || $score > 5) {
        return $this->json(['error' => 'Score inválido'], 400);
    }

    // Buscar rating existente
    $existingRating = $this->newsRatingRepository->findOneBy([
        'user' => $this->getUser(),
        'news' => $news,
    ]);

    if ($existingRating) {
        // Actualizar rating
        $existingRating->setScore($score);
    } else {
        // Crear nuevo rating
        $rating = new NewsRating();
        $rating->setUser($this->getUser());
        $rating->setNews($news);
        $rating->setScore($score);
        $this->entityManager->persist($rating);
    }

    $this->entityManager->flush();

    // Recalcular promedio de calificaciones
    $averageScore = $this->newsRatingRepository->calculateAverageScore($news);
    $news->setAverageRating($averageScore);
    $this->entityManager->flush();

    return $this->json(['success' => true, 'averageRating' => $averageScore]);
}

// Método auxiliar en NewsRatingRepository
// src/Repository/NewsRatingRepository.php
public function calculateAverageScore(News $news): float
{
    $result = $this->createQueryBuilder('nr')
        ->select('AVG(nr.score) as avgScore')
        ->where('nr.news = :news')
        ->setParameter('news', $news)
        ->getQuery()
        ->getOneOrNullResult();

    return $result['avgScore'] ? (float) $result['avgScore'] : 0;
}
```

*Explicación de comandos clave:*
1. $request->request->getInt('score') → Obtiene calificación (1-5) del POST.
2. Validación: if ($score < 1 || $score > 5) → Comprueba rango válido.
3. $this->newsRatingRepository->findOneBy([...]) → Busca si el usuario ya calificó.
4. Similar a votos de comentarios: actualiza o crea rating.
5. $this->newsRatingRepository->calculateAverageScore() → Calcula promedio.
6. return $this->json([...]) → Retorna JSON (respuesta AJAX).

---

## CU11: Suscribirse al boletín

*Archivos involucrados:*
- Controller: src/Controller/Admin/NewsletterController.php
- Form: src/Form/SubscriberFormType.php (si aplica)
- Template: templates/admin/newsletter/subscribers.html.twig, templates/admin/newsletter/send.html.twig
- Configuración de Mailer: config/packages/messenger.yaml

*Flujo y código:*

```php
// src/Controller/Admin/NewsletterController.php
#[Route('/admin/newsletter/subscribers', name: 'app_newsletter_subscribers')]
#[IsGranted('ROLE_ADMIN')]
public function subscribers(Request $request): Response
{
    // CU11: Gestionar suscriptores
    $subscribers = $this->subscriberRepository->findAll();

    return $this->render('admin/newsletter/subscribers.html.twig', [
        'subscribers' => $subscribers,
    ]);
}

#[Route('/admin/newsletter/subscribe', name: 'app_newsletter_subscribe', methods: ['POST'])]
public function subscribe(Request $request): Response
{
    // CU11: Suscribirse al boletín (público)
    $email = $request->request->get('email');

    // Validar que no esté ya suscrito
    $existing = $this->subscriberRepository->findOneBy(['email' => $email]);
    if ($existing) {
        $this->addFlash('info', 'Ya estás suscrito');
        return $this->redirectToRoute('app_home');
    }

    // Crear suscriptor
    $subscriber = new Subscriber();
    $subscriber->setEmail($email);
    $subscriber->setSubscribedAt(new \DateTimeImmutable());

    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

    $this->addFlash('success', 'Suscripción confirmada');
    return $this->redirectToRoute('app_home');
}

#[Route('/admin/newsletter/send', name: 'app_newsletter_send', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function send(Request $request, MailerInterface $mailer): Response
{
    // CU11: Enviar boletín
    $subject = $request->request->get('subject');
    $content = $request->request->get('content');
    $subscribers = $this->subscriberRepository->findAll();

    foreach ($subscribers as $subscriber) {
        $email = (new Email())
            ->from('newsletter@gamenews.com')
            ->to($subscriber->getEmail())
            ->subject($subject)
            ->html($this->renderView('admin/newsletter/send.html.twig', [
                'content' => $content,
                'subscriber' => $subscriber,
            ]));

        $mailer->send($email);
    }

    $this->addFlash('success', sprintf('Boletín enviado a %d suscriptores', count($subscribers)));
    return $this->redirectToRoute('app_newsletter_subscribers');
}
```

*Explicación de comandos clave:*
1. $this->subscriberRepository->findAll() → Obtiene lista de todos los suscriptores.
2. $this->subscriberRepository->findOneBy(['email' => $email]) → Verifica si ya está suscrito.
3. new Subscriber() → Crea nueva entidad suscriptor.
4. $this->entityManager->persist() y flush() → Guarda en BD.
5. MailerInterface $mailer → Inyecta servicio de correo de Symfony.
6. $mailer->send($email) → Envía correo a cada suscriptor.
7. $this->renderView('admin/newsletter/send.html.twig', [...]) → Renderiza plantilla HTML para el email.

---

## CU12: Gestión de noticias (CRUD)

*Archivos involucrados:*
- Controller: src/Controller/Admin/NewsManagementController.php
- Form: src/Form/NewsFormType.php
- Entity: src/Entity/News.php, src/Entity/Category.php, src/Entity/Tag.php
- Repository: src/Repository/NewsRepository.php
- Template: templates/admin/news/index.html.twig, new.html.twig, edit.html.twig

*Flujo y código (CREATE):*

```php
// src/Controller/Admin/NewsManagementController.php
#[Route('/admin/news', name: 'app_news_admin_index')]
#[IsGranted('ROLE_EDITOR')]
public function index(Request $request, PaginatorInterface $paginator): Response
{
    // CU12: Listar noticias del usuario
    $queryBuilder = $this->newsRepository->createQueryBuilder('n')
        ->where('n.author = :author')
        ->setParameter('author', $this->getUser())
        ->orderBy('n.createdAt', 'DESC');

    $news = $paginator->paginate($queryBuilder->getQuery(), $request->query->getInt('page', 1), 10);

    return $this->render('admin/news/index.html.twig', ['news' => $news]);
}

#[Route('/admin/news/new', name: 'app_news_new')]
#[IsGranted('ROLE_EDITOR')]
public function new(Request $request): Response
{
    // CU12: Crear nueva noticia
    $news = new News();
    $form = $this->createForm(NewsFormType::class, $news);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Asignar autor y fechas
        $news->setAuthor($this->getUser());
        $news->setCreatedAt(new \DateTimeImmutable());
        $news->setStatus('draft'); // Estado inicial: borrador

        // Generar slug automático
        $news->setSlug($this->generateSlug($news->getTitle()));

        // Procesar imagen destacada si se subió
        $featuredImage = $form->get('featuredImage')->getData();
        if ($featuredImage) {
            $newFilename = $this->uploadImage($featuredImage);
            $news->setFeaturedImage($newFilename);
        }

        // Persistir
        $this->entityManager->persist($news);
        $this->entityManager->flush();

        $this->addFlash('success', 'Noticia creada en borrador');
        return $this->redirectToRoute('app_news_edit', ['id' => $news->getId()]);
    }

    return $this->render('admin/news/new.html.twig', ['form' => $form]);
}

#[Route('/admin/news/{id}/edit', name: 'app_news_edit')]
#[IsGranted('ROLE_EDITOR')]
public function edit(Request $request, News $news): Response
{
    // CU12: Editar noticia (verificar propiedad)
    if ($news->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
        throw $this->createAccessDeniedException('No tienes permiso para editar esta noticia');
    }

    $form = $this->createForm(NewsFormType::class, $news);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $news->setUpdatedAt(new \DateTimeImmutable());

        // Procesar nueva imagen si se subió
        $featuredImage = $form->get('featuredImage')->getData();
        if ($featuredImage) {
            $newFilename = $this->uploadImage($featuredImage);
            $news->setFeaturedImage($newFilename);
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Noticia actualizada');
        return $this->redirectToRoute('app_news_edit', ['id' => $news->getId()]);
    }

    return $this->render('admin/news/edit.html.twig', [
        'news' => $news,
        'form' => $form,
    ]);
}

#[Route('/admin/news/{id}/delete', name: 'app_news_delete', methods: ['POST'])]
#[IsGranted('ROLE_EDITOR')]
public function delete(Request $request, News $news): Response
{
    // CU12: Eliminar noticia
    if ($news->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
        throw $this->createAccessDeniedException();
    }

    if ($this->isCsrfTokenValid('delete'.$news->getId(), $request->request->get('_token'))) {
        $this->entityManager->remove($news);
        $this->entityManager->flush();
        $this->addFlash('success', 'Noticia eliminada');
    }

    return $this->redirectToRoute('app_news_admin_index');
}

#[Route('/admin/news/{id}/publish', name: 'app_news_publish', methods: ['POST'])]
#[IsGranted('ROLE_EDITOR')]
public function publish(Request $request, News $news): Response
{
    // CU12: Publicar noticia
    if ($news->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
        throw $this->createAccessDeniedException();
    }

    $news->setStatus('published');
    $news->setPublishedAt(new \DateTimeImmutable());
    $this->entityManager->flush();

    $this->addFlash('success', 'Noticia publicada');
    return $this->redirectToRoute('app_news_edit', ['id' => $news->getId()]);
}

// Método auxiliar para generar slug
private function generateSlug(string $title): string
{
    return strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', preg_replace('/[áéíóú]/', 
        ['a', 'e', 'i', 'o', 'u'], strtolower($title))), '-'));
}
```

*Explicación de comandos clave:*
1. #[IsGranted('ROLE_EDITOR')] → Solo editores o admins pueden acceder.
2. $news->setAuthor($this->getUser()) → Registra al usuario como autor.
3. $news->setStatus('draft') → Estado inicial de borrador.
4. $this->generateSlug() → Crea URL-friendly identifier.
5. $this->uploadImage() → Maneja subida de archivo destacado.
6. $news->setPublishedAt(new \DateTimeImmutable()) → Registra fecha de publicación.
7. $this->isCsrfTokenValid() → Protección contra CSRF en eliminación.

*Plantilla asociada:*
twig
{# templates/admin/news/new.html.twig #}
<h1>Nueva Noticia</h1>
<form method="post">
    {{ form_start(form) }}
        {{ form_row(form.title) }}
        {{ form_row(form.excerpt) }}
        {{ form_row(form.body) }}
        {{ form_row(form.categories) }}
        {{ form_row(form.tags) }}
        {{ form_row(form.featuredImage) }}
        <button type="submit">Guardar Borrador</button>
    {{ form_end(form) }}
</form>


---

## CU13: Editar categorías

*Archivos involucrados:*
- Controller: src/Controller/Admin/CategoryController.php
- Form: src/Form/CategoryFormType.php
- Entity: src/Entity/Category.php
- Repository: src/Repository/CategoryRepository.php
- Template: templates/admin/category/index.html.twig, new.html.twig, edit.html.twig

*Flujo y código:*

```php
// src/Controller/Admin/CategoryController.php
#[Route('/admin/categories', name: 'app_category_index')]
#[IsGranted('ROLE_ADMIN')]
public function index(): Response
{
    // CU13: Listar categorías
    $categories = $this->categoryRepository->findAll();

    return $this->render('admin/category/index.html.twig', [
        'categories' => $categories,
    ]);
}

#[Route('/admin/categories/new', name: 'app_category_new')]
#[IsGranted('ROLE_ADMIN')]
public function new(Request $request): Response
{
    // CU13: Crear categoría
    $category = new Category();
    $form = $this->createForm(CategoryFormType::class, $category);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Generar slug automático
        $category->setSlug($this->generateSlug($category->getName()));
        $category->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->addFlash('success', 'Categoría creada');
        return $this->redirectToRoute('app_category_index');
    }

    return $this->render('admin/category/new.html.twig', ['form' => $form]);
}

#[Route('/admin/categories/{id}/edit', name: 'app_category_edit')]
#[IsGranted('ROLE_ADMIN')]
public function edit(Request $request, Category $category): Response
{
    // CU13: Editar categoría
    $form = $this->createForm(CategoryFormType::class, $category);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $category->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->addFlash('success', 'Categoría actualizada');
        return $this->redirectToRoute('app_category_index');
    }

    return $this->render('admin/category/edit.html.twig', [
        'category' => $category,
        'form' => $form,
    ]);
}

#[Route('/admin/categories/{id}/delete', name: 'app_category_delete', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function delete(Request $request, Category $category): Response
{
    // CU13: Eliminar categoría
    if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
        // Verificar que no tenga noticias asociadas
        if ($category->getNews()->count() > 0) {
            $this->addFlash('error', 'No se puede eliminar una categoría con noticias asociadas');
        } else {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
            $this->addFlash('success', 'Categoría eliminada');
        }
    }

    return $this->redirectToRoute('app_category_index');
}
```

*Explicación de comandos clave:*
1. #[IsGranted('ROLE_ADMIN')] → Solo administradores pueden gestionar categorías.
2. $this->categoryRepository->findAll() → Obtiene todas las categorías.
3. $category->setSlug(...) → Genera identificador para URLs.
4. $category->getNews()->count() → Verifica relaciones antes de eliminar.

---

## CU14: Editar etiquetas

*Archivos involucrados:*
- Controller: src/Controller/Admin/TagController.php
- Form: src/Form/TagFormType.php
- Entity: src/Entity/Tag.php
- Repository: src/Repository/TagRepository.php
- Template: templates/admin/tag/index.html.twig, new.html.twig, edit.html.twig

*Flujo y código:*

```php
// src/Controller/Admin/TagController.php
#[Route('/admin/tags', name: 'app_tag_index')]
#[IsGranted('ROLE_ADMIN')]
public function index(): Response
{
    // CU14: Listar etiquetas
    $tags = $this->tagRepository->findAll();

    return $this->render('admin/tag/index.html.twig', ['tags' => $tags]);
}

#[Route('/admin/tags/new', name: 'app_tag_new')]
#[IsGranted('ROLE_ADMIN')]
public function new(Request $request): Response
{
    // CU14: Crear etiqueta
    $tag = new Tag();
    $form = $this->createForm(TagFormType::class, $tag);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $tag->setSlug($this->generateSlug($tag->getName()));
        $tag->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $this->addFlash('success', 'Etiqueta creada');
        return $this->redirectToRoute('app_tag_index');
    }

    return $this->render('admin/tag/new.html.twig', ['form' => $form]);
}

#[Route('/admin/tags/{id}/edit', name: 'app_tag_edit')]
#[IsGranted('ROLE_ADMIN')]
public function edit(Request $request, Tag $tag): Response
{
    // CU14: Editar etiqueta
    $form = $this->createForm(TagFormType::class, $tag);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $tag->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->addFlash('success', 'Etiqueta actualizada');
        return $this->redirectToRoute('app_tag_index');
    }

    return $this->render('admin/tag/edit.html.twig', ['tag' => $tag, 'form' => $form]);
}

#[Route('/admin/tags/{id}/delete', name: 'app_tag_delete', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function delete(Request $request, Tag $tag): Response
{
    // CU14: Eliminar etiqueta
    if ($this->isCsrfTokenValid('delete'.$tag->getId(), $request->request->get('_token'))) {
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
        $this->addFlash('success', 'Etiqueta eliminada');
    }

    return $this->redirectToRoute('app_tag_index');
}
```

*Explicación de comandos clave:*
- Similar a CU13 (categorías).
- Las etiquetas se usan en NewsFormType con CollectionType para relación many-to-many.

---

## CU16: Moderar comentarios

*Archivos involucrados:*
- Controller: src/Controller/Admin/ModerationController.php
- Repository: src/Repository/CommentRepository.php, src/Repository/ReportRepository.php
- Entity: src/Entity/Comment.php, src/Entity/Report.php
- Template: templates/admin/moderation/index.html.twig, review.html.twig

*Flujo y código:*

```php
// src/Controller/Admin/ModerationController.php

// Ruta para ver todos los reportes pendientes de moderación
#[Route('/admin/moderation', name: 'app_moderation_index')]
// Solo accesible para usuarios con rol ROLE_ADMIN
#[IsGranted('ROLE_ADMIN')]
public function index(Request $request, PaginatorInterface $paginator): Response
{
    // Este método muestra un listado paginado de todos los reportes
    // que aún no han sido resueltos por el equipo de moderación.

    // Construir la consulta para obtener solo reportes no resueltos
    $queryBuilder = $this->reportRepository->createQueryBuilder('r')
        ->where('r.resolved = :resolved')
        ->setParameter('resolved', false)
        ->orderBy('r.createdAt', 'DESC');

    // Paginar los resultados (10 reportes por página)
    $reports = $paginator->paginate(
        $queryBuilder->getQuery(),
        $request->query->getInt('page', 1),
        10
    );

    // Renderizar la vista de administración con los reportes
    return $this->render('admin/moderation/index.html.twig', [
        'reports' => $reports,
    ]);
}

// Ruta para revisar un reporte específico
#[Route('/admin/moderation/report/{id}', name: 'app_moderation_review')]
// Solo accesible para administradores
#[IsGranted('ROLE_ADMIN')]
public function review(Request $request, Report $report): Response
{
    // Muestra los detalles del reporte para que el administrador lo evalúe

    return $this->render('admin/moderation/review.html.twig', [
        'report' => $report,
    ]);
}

// Ruta para eliminar un comentario denunciado
#[Route('/admin/moderation/comment/{id}/delete', name: 'app_comment_delete_admin', methods: ['POST'])]
// Solo accesible para administradores
#[IsGranted('ROLE_ADMIN')]
public function deleteComment(Request $request, Comment $comment): Response
{
    // CU16: Eliminar comentario
    // Verifica el token CSRF para evitar acciones maliciosas
    if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
        // Obtener la noticia asociada (opcional, si se quiere redirigir o mostrar contexto)
        $news = $comment->getNews();

        // Eliminar el comentario de la base de datos
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        // Buscar todos los reportes no resueltos asociados al comentario
        $reports = $this->reportRepository->findBy([
            'targetComment' => $comment,
            'resolved' => false
        ]);

        // Marcar cada reporte como resuelto y registrar la razón
        foreach ($reports as $report) {
            $report->setResolved(true);
            $report->setResolutionReason('Comentario eliminado');
        }

        // Guardar los cambios en los reportes
        $this->entityManager->flush();

        // Mostrar mensaje de éxito
        $this->addFlash('success', 'Comentario eliminado');
    }

    // Redirigir al listado de moderación
    return $this->redirectToRoute('app_moderation_index');
}

// Ruta para aprobar un reporte (desestimarlo)
#[Route('/admin/moderation/report/{id}/approve', name: 'app_report_approve', methods: ['POST'])]
// Solo accesible para administradores
#[IsGranted('ROLE_ADMIN')]
public function approveReport(Request $request, Report $report): Response
{
    // CU16: Desestimar reporte (comentario válido)
    // Verifica el token CSRF para seguridad
    if ($this->isCsrfTokenValid('approve'.$report->getId(), $request->request->get('_token'))) {
        // Marcar el reporte como resuelto y registrar la razón
        $report->setResolved(true);
        $report->setResolutionReason('Aprobado - No viola normas');

        // Guardar los cambios
        $this->entityManager->flush();

        // Mostrar mensaje de éxito
        $this->addFlash('success', 'Reporte desestimado');
    }

    // Redirigir al listado de moderación
    return $this->redirectToRoute('app_moderation_index');
}

```

*Explicación de comandos clave:*
1. $this->reportRepository->createQueryBuilder('r')->where('r.resolved = :resolved', false) → Obtiene reportes pendientes.
2. $report->setResolved(true) → Marca reporte como atendido.
3. $this->reportRepository->findBy(['targetComment' => $comment, 'resolved' => false]) → Obtiene todos los reportes de un comentario.
4. $this->entityManager->remove($comment) → Elimina comentario permanentemente.

---

## CU20: Ver noticia

*Archivos involucrados:*
- Controller: src/Controller/NewsController.php
- Repository: src/Repository/NewsRepository.php, src/Repository/CommentRepository.php, src/Repository/NewsRatingRepository.php
- Entity: src/Entity/News.php, src/Entity/Comment.php, src/Entity/NewsRating.php
- Template: templates/news/show.html.twig

*Flujo y código:*

```php
// src/Controller/NewsController.php
#[Route('/news/{slug}', name: 'app_news_show')]
public function show(Request $request, News $news): Response
{
    // CU20: Ver noticia
    // Incrementar contador de vistas
    $news->incrementViews();
    $this->entityManager->flush();

    // Obtener comentarios visibles
    $comments = $this->commentRepository->findBy([
        'news' => $news,
        'visible' => true,
    ], ['createdAt' => 'DESC']);

    // Obtener calificación promedio
    $averageRating = $this->newsRatingRepository->calculateAverageScore($news);

    // Crear form de comentario si el usuario está autenticado
    $commentForm = null;
    if ($this->getUser()) {
        $comment = new Comment();
        $commentForm = $this->createForm(CommentFormType::class, $comment);
    }

    // Obtener el voto del usuario actual (si existe)
    $userRating = null;
    if ($this->getUser()) {
        $userRating = $this->newsRatingRepository->findOneBy([
            'user' => $this->getUser(),
            'news' => $news,
        ]);
    }

    return $this->render('news/show.html.twig', [
        'news' => $news,
        'comments' => $comments,
        'averageRating' => $averageRating,
        'commentForm' => $commentForm,
        'userRating' => $userRating,
    ]);
}
```

*Explicación de comandos clave:*
1. News $news → ParamConverter inyecta automáticamente por slug.
2. $news->incrementViews() → Incrementa contador de vistas (observador de entidad).
3. $this->commentRepository->findBy(['news' => $news, 'visible' => true]) → Obtiene solo comentarios visibles.
4. $this->newsRatingRepository->calculateAverageScore($news) → Calcula promedio de ratings.
5. $this->createForm(CommentFormType::class, $comment) → Prepara form de comentario si está autenticado.

*Plantilla asociada:*
twig
{# templates/news/show.html.twig #}
<article class="news-detail">
    <h1>{{ news.title }}</h1>
    <img src="{{ asset('images/' ~ news.featuredImage) }}" alt="{{ news.title }}">
    
    <div class="metadata">
        <small>Por {{ news.author.username }} - {{ news.publishedAt|date('d/m/Y') }}</small>
        <small>{{ news.views }} vistas</small>
    </div>

    <div class="content">{{ news.body|raw }}</div>

    <div class="rating">
        <strong>Puntuación: {{ averageRating|number_format(1) }}/5 ⭐</strong>
        {% if is_granted('ROLE_USER') %}
            <form method="POST" action="{{ path('app_news_rate', {id: news.id}) }}">
                {% for i in 1..5 %}
                    <input type="radio" name="score" value="{{ i }}" 
                        {% if userRating and userRating.score == i %}checked{% endif %}>
                {% endfor %}
                <button type="submit">Calificar</button>
            </form>
        {% endif %}
    </div>

    <section class="comments">
        <h3>Comentarios ({{ comments|length }})</h3>
        {% for comment in comments %}
            <div class="comment">
                <strong>{{ comment.user.username }}</strong>
                <small>{{ comment.createdAt|date('d/m/Y H:i') }}</small>
                <p>{{ comment.content }}</p>
            </div>
        {% endfor %}

        {% if commentForm and is_granted('ROLE_USER') %}
            <form method="POST" action="{{ path('app_comment_create', {slug: news.slug}) }}">
                {{ form_start(commentForm) }}
                    {{ form_row(commentForm.content) }}
                    <button type="submit">Comentar</button>
                {{ form_end(commentForm) }}
            </form>
        {% endif %}
    </section>
</article>
```

---

## Resumen de Comandos y Patrones Transversales

### EntityManager (Persistencia)
```php
$entityManager->persist($entity);     // Marca para inserción/actualización
$entityManager->flush();              // Ejecuta cambios en BD
$entityManager->remove($entity);      // Marca para eliminación
```

### Formularios
```php
$form = $this->createForm(FormType::class, $entity);
$form->handleRequest($request);       // Procesa POST/GET
if ($form->isSubmitted() && $form->isValid()) { /* guardar */ }
```

### Queries con QueryBuilder
```php
$queryBuilder = $repo->createQueryBuilder('alias')
    ->where('alias.field = :value')
    ->setParameter('value', $value)
    ->orderBy('alias.field', 'DESC')
    ->getQuery()
    ->getResult();
```

### Seguridad
```php
#[IsGranted('ROLE_USER')]                     // Verifica rol
$this->getUser()                              // Usuario actual
$this->isGranted('ROLE_ADMIN')               // Comprobación en código
$this->createAccessDeniedException()          // Lanza excepción 403
```

### Redirecciones y Respuestas
```php
return $this->redirectToRoute('route_name');
return $this->render('template.html.twig', ['var' => $value]);
return $this->json(['data' => $value]);
```

### Flash Messages
```php
$this->addFlash('success', 'Operación exitosa');
$this->addFlash('error', 'Ocurrió un error');
```