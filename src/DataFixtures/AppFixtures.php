<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\News;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private SluggerInterface $slugger;

    public function __construct(UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger)
    {
        $this->passwordHasher = $passwordHasher;
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        // Crear usuario administrador
        $admin = new User();
        $admin->setEmail('admin@gamenews.com');
        $admin->setUsername('admin');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123'));
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        // Crear editor
        $editor = new User();
        $editor->setEmail('editor@gamenews.com');
        $editor->setUsername('editor');
        $editor->setPassword($this->passwordHasher->hashPassword($editor, 'Editor123'));
        $editor->setRoles(['ROLE_EDITOR']);
        $manager->persist($editor);

        // Crear usuario normal
        $user = new User();
        $user->setEmail('usuario@gamenews.com');
        $user->setUsername('usuario');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Usuario123'));
        $manager->persist($user);

        // Crear categorías
        $categories = [
            ['name' => 'Noticias', 'description' => 'Últimas noticias del mundo gamer'],
            ['name' => 'Análisis', 'description' => 'Reviews y análisis de juegos'],
            ['name' => 'Guías', 'description' => 'Guías y tutoriales'],
            ['name' => 'eSports', 'description' => 'Noticias de deportes electrónicos'],
            ['name' => 'Hardware', 'description' => 'Noticias sobre hardware gaming'],
        ];

        $categoryEntities = [];
        foreach ($categories as $cat) {
            $category = new Category();
            $category->setName($cat['name']);
            $category->setDescription($cat['description']);
            $category->setSlug($this->slugger->slug($cat['name'])->lower());
            $manager->persist($category);
            $categoryEntities[] = $category;
        }

        // Crear etiquetas
        $tags = [
            ['name' => 'PS5', 'synonyms' => 'PlayStation 5, PlayStation5'],
            ['name' => 'Xbox Series X', 'synonyms' => 'Xbox, Microsoft Xbox'],
            ['name' => 'Nintendo Switch', 'synonyms' => 'Switch, Nintendo'],
            ['name' => 'PC Gaming', 'synonyms' => 'PC, Computadora'],
            ['name' => 'RPG', 'synonyms' => 'Rol, Juego de Rol'],
            ['name' => 'FPS', 'synonyms' => 'Shooter, Disparos'],
            ['name' => 'Acción', 'synonyms' => 'Action'],
            ['name' => 'Aventura', 'synonyms' => 'Adventure'],
        ];

        $tagEntities = [];
        foreach ($tags as $t) {
            $tag = new Tag();
            $tag->setName($t['name']);
            $tag->setSynonyms($t['synonyms']);
            $tag->setSlug($this->slugger->slug($t['name'])->lower());
            $manager->persist($tag);
            $tagEntities[] = $tag;
        }

        // Crear noticias de ejemplo
        $newsData = [
            [
                'title' => 'GTA 6: Todo lo que sabemos sobre el juego más esperado',
                'subtitle' => 'Rockstar Games prepara el lanzamiento más ambicioso de la década',
                'body' => "Grand Theft Auto VI está en desarrollo y promete revolucionar la industria del videojuego. Según fuentes cercanas a Rockstar Games, el juego contará con un mapa más grande que nunca, incorporando múltiples ciudades y áreas rurales extensas.\n\nLa historia se centrará en dos protagonistas que podrán ser controlados alternadamente, similar al sistema visto en GTA V. El juego aprovechará al máximo las capacidades de las consolas de nueva generación.\n\nSe espera que incluya mejoras significativas en física, inteligencia artificial y gráficos, estableciendo un nuevo estándar para los juegos de mundo abierto.",
                'categories' => [$categoryEntities[0]],
                'tags' => [$tagEntities[3], $tagEntities[6]],
                'image' => 'gta6.jpg',
            ],
            [
                'title' => 'The Legend of Zelda: Tears of the Kingdom - Análisis Completo',
                'subtitle' => 'Nintendo vuelve a demostrar por qué es el rey de las aventuras',
                'body' => "The Legend of Zelda: Tears of the Kingdom es la secuela directa de Breath of the Wild y supera a su predecesor en prácticamente todos los aspectos. La mecánica de construcción añade una nueva dimensión al gameplay.\n\nLa historia es más profunda y emotiva, con momentos que quedarán grabados en la memoria de los jugadores. Los puzzles son ingeniosos y desafiantes, requiriendo pensamiento creativo.\n\nGráficamente, el juego es impresionante considerando las limitaciones del hardware de Switch. La banda sonora es épica y memorable. Un must-play para cualquier gamer.",
                'categories' => [$categoryEntities[1]],
                'tags' => [$tagEntities[2], $tagEntities[7]],
                'image' => 'zelda.jpg',
            ],
            [
                'title' => 'PlayStation 5 Pro: Especificaciones y fecha de lanzamiento',
                'subtitle' => 'Sony anuncia la versión mejorada de su consola estrella',
                'body' => "Sony ha confirmado oficialmente el lanzamiento de PlayStation 5 Pro para finales de 2025. La consola contará con un procesador mejorado y soporte nativo para 8K.\n\nLas mejoras incluyen ray-tracing más avanzado, carga más rápida de juegos y compatibilidad total con realidad virtual de última generación. El precio será de $599 USD.\n\nLos desarrolladores ya están trabajando en parches para mejorar juegos existentes y aprovechar el nuevo hardware. Se espera que títulos como Spider-Man 2 y God of War Ragnarok reciban actualizaciones significativas.",
                'categories' => [$categoryEntities[0], $categoryEntities[4]],
                'tags' => [$tagEntities[0], $tagEntities[3]],
                'image' => 'ps5pro.jpg',
            ],
            [
                'title' => 'Guía completa de Elden Ring: Cómo vencer a Malenia',
                'subtitle' => 'Estrategias y consejos para derrotar al jefe más difícil del juego',
                'body' => "Malenia, Blade of Miquella, es considerada uno de los jefes más difíciles en la historia de los videojuegos. Esta guía te ayudará a vencerla.\n\nPrimero, necesitas un build adecuado. Se recomienda nivel 150 como mínimo. Las armas con sangrado son muy efectivas. Usa el escudo Barricade Shield para bloquear sus ataques.\n\nLa clave está en aprender sus patrones de ataque. Su combo Waterfowl Dance es letal, pero puede evitarse con el timing correcto. Sé paciente y aprende de cada intento.",
                'categories' => [$categoryEntities[2]],
                'tags' => [$tagEntities[4], $tagEntities[6]],
                'image' => 'eldenring.jpg',
            ],
            [
                'title' => 'Los mejores juegos indie de 2025',
                'subtitle' => 'Joyas ocultas que no puedes perderte este año',
                'body' => "La escena indie continúa sorprendiéndonos con propuestas únicas y creativas. Este año destaca por la variedad y calidad de sus lanzamientos.\n\nEntre los títulos más destacados encontramos 'Hollow Knight: Silksong', que finalmente ve la luz después de años de espera. También 'Hades II' que mejora la fórmula del original.\n\n'Sea of Stars' es un RPG que rinde homenaje a los clásicos de los 90. Y 'Cocoon' ofrece una experiencia de puzzles única. Todos ellos demuestran que no necesitas presupuestos AAA para crear experiencias memorables.",
                'categories' => [$categoryEntities[0], $categoryEntities[1]],
                'tags' => [$tagEntities[3], $tagEntities[7]],
                'image' => 'indie2025.jpg',
            ],
        ];

        foreach ($newsData as $index => $newsItem) {
            $news = new News();
            $news->setTitle($newsItem['title']);
            $news->setSubtitle($newsItem['subtitle']);
            $news->setBody($newsItem['body']);
            $slug = $this->slugger->slug($newsItem['title'])->lower();
            $news->setSlug($slug . '-' . uniqid());
            $news->setStatus('published');
            $news->setAuthor($editor);
            $news->setPublishedAt(new \DateTime('-' . ($index * 2) . ' days'));
            $news->setViewCount(rand(100, 5000));
            $news->setAverageRating(number_format(rand(35, 50) / 10, 2));
            $news->setRatingCount(rand(10, 200));
            $news->setPredefinedImage($newsItem['image']);

            foreach ($newsItem['categories'] as $category) {
                $news->addCategory($category);
            }

            foreach ($newsItem['tags'] as $tag) {
                $news->addTag($tag);
            }

            $manager->persist($news);
        }

        $manager->flush();
    }
}