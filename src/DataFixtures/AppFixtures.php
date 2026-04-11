<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Comment;
use App\Entity\Like;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // ── 1. ADMIN ─────────────────────────────────────────────
        $admin = new User();
        $admin->setFirstname('Admin');
        $admin->setLastname('EbookShare');
        $admin->setPseudo('admin');
        $admin->setEmail('admin@ebookshare.com');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        $manager->persist($admin);

        // ── 2. USERS ──────────────────────────────────────────────
        $users      = [$admin];
        $usedPseudos = ['admin'];

        for ($i = 0; $i < 20; $i++) {
            $user = new User();

            $firstname = $faker->firstName();
            $lastname  = $faker->lastName();

            $basePseudo = strtolower(
                preg_replace('/[^a-zA-Z0-9_\-]/', '', $firstname . $lastname)
            );
            $pseudo = $basePseudo;
            $suffix = 1;
            while (in_array($pseudo, $usedPseudos, true)) {
                $pseudo = $basePseudo . $suffix++;
            }
            $usedPseudos[] = $pseudo;

            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setPseudo($pseudo);
            $user->setEmail($faker->unique()->safeEmail());
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $user->setIsVerified($faker->boolean(80));
            $manager->persist($user);

            $users[] = $user;
        }

        // ── 3. BOOKS ─────────────────────────────────────────────
        $bookTitles = [
            'Le Secret des étoiles', 'Voyage au centre du code', 'L\'Ombre du vent numérique',
            'Mémoires d\'un lecteur curieux', 'La Forêt des mots perdus', 'Chroniques du futur proche',
            'L\'Art de la pensée critique', 'Histoires du bout du monde', 'Le Philosophe et la machine',
            'Contes modernes', 'La Révolution silencieuse', 'Épistémologie pour tous',
            'Le Dernier Bibliothécaire', 'Fragments d\'une vie ordinaire', 'Le Grand Livre du rien',
            'Poèmes pour demain', 'Atlas des émotions', 'Le Cube de verre',
            'Nouvelles du bout de la nuit', 'Traité de l\'imperfection', 'L\'Encyclopédie du vide',
            'Vers une nouvelle conscience', 'Le Passeur de mondes', 'Carnet de route',
            'L\'Homme qui lisait les nuages', 'Demain, peut-être', 'Les Racines du ciel numérique',
            'Petites vérités importantes', 'La Cité des rêveurs', 'Anthologie poétique',
        ];

        $books = [];

        foreach ($bookTitles as $title) {
            $book = new Book();
            $book->setTitle($title);
            $book->setDescription(mb_substr($faker->paragraphs(2, true), 0, 252) . '...');
            $book->setOwner($faker->randomElement($users));
            $book->setUploadetat(
                \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now'))
            );
            // filepath nullable — simulate that some books have a file uploaded
            $book->setFilepath($faker->boolean(70) ? 'books/' . $faker->uuid() . '.pdf' : null);

            $manager->persist($book);
            $books[] = $book;
        }

        // ── 4. COMMENTS ──────────────────────────────────────────
        foreach ($books as $book) {
            $commentCount = $faker->numberBetween(0, 8);
            for ($c = 0; $c < $commentCount; $c++) {
                $comment = new Comment();
                $comment->setContent(mb_substr($faker->realText($faker->numberBetween(40, 200)), 0, 255));
                $comment->setAuthor($faker->randomElement($users));
                $comment->setBook($book);
                $comment->setCreatedat(
                    \DateTimeImmutable::createFromMutable(
                        $faker->dateTimeBetween($book->getUploadetat()->format('Y-m-d H:i:s'), 'now')
                    )
                );
                $manager->persist($comment);
            }
        }

        // ── 5. LIKES ─────────────────────────────────────────────
        foreach ($books as $book) {
            // pick a random subset of users who liked this book (no duplicates)
            $likers = $faker->randomElements($users, $faker->numberBetween(0, min(15, count($users))));
            foreach ($likers as $liker) {
                $like = new Like();
                $like->setOwner($liker);
                $like->setBook($book);
                $manager->persist($like);
            }
        }

        $manager->flush();
    }
}

